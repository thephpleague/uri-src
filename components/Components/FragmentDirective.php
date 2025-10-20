<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri\Components;

use Countable;
use IteratorAggregate;
use League\Uri\Components\Directives\Directive;
use League\Uri\Components\Directives\GenericDirective;
use League\Uri\Components\Directives\TextDirective;
use League\Uri\Contracts\FragmentInterface;
use League\Uri\Contracts\UriInterface;
use League\Uri\Encoder;
use League\Uri\Exceptions\OffsetOutOfBounds;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\Modifier;
use League\Uri\Uri;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;
use Throwable;
use Traversable;
use Uri\Rfc3986\Uri as Rfc3986Uri;
use Uri\WhatWg\Url as WhatWgUrl;

use function array_count_values;
use function array_filter;
use function array_keys;
use function array_map;
use function array_slice;
use function count;
use function filter_var;
use function implode;
use function in_array;
use function is_bool;
use function sprintf;
use function str_replace;

use const ARRAY_FILTER_USE_BOTH;
use const ARRAY_FILTER_USE_KEY;
use const FILTER_VALIDATE_INT;

/**
 * @implements IteratorAggregate<int, Directive>
 */
final class FragmentDirective implements FragmentInterface, IteratorAggregate, Countable
{
    public const DELIMITER = ':~:';
    public const SEPARATOR = '&';

    /** @var list<Directive> */
    private array $directives;

    public function __construct(Directive ...$directives)
    {
        $this->directives = array_values($directives);
    }

    public function count(): int
    {
        return count($this->directives);
    }

    public function value(): ?string
    {
        if ([] === $this->directives) {
            return null;
        }

        return self::DELIMITER.implode(self::SEPARATOR, array_map(fn (Directive $directive): string => $directive->toString(), $this->directives));
    }

    public function toString(): string
    {
        return (string) $this->value();
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }

    public function getUriComponent(): string
    {
        $fragment = $this->value();

        return (null === $fragment ? '' : '#').$fragment;
    }

    public function decoded(): ?string
    {
        if ([] === $this->directives) {
            return null;
        }

        return  str_replace('%20', ' ', (string) Encoder::decodeFragment($this->toString()));
    }

    public function getIterator(): Traversable
    {
        yield from $this->directives;
    }

    /**
     * Append one or more Directives to the fragment.
     */
    public function append(Directive ...$directives): self
    {
        return new self(...$this->directives, ...$directives);
    }

    /**
     * Prepend one or more Directives to the fragment.
     */
    public function prepend(Directive ...$directives): self
    {
        return new self(...$directives, ...$this->directives);
    }

    /**
     * Removes one or more Directives by offset from the fragment.
     */
    public function remove(int ...$keys): self
    {
        if ([] === $keys) {
            return $this;
        }

        $nbDirectives = count($this->directives);
        $options = ['options' => ['min_range' => - $nbDirectives, 'max_range' => $nbDirectives - 1]];
        $deletedKeys = [];
        foreach ($keys as $value) {
            /** @var false|int $offset */
            $offset = filter_var($value, FILTER_VALIDATE_INT, $options);
            false !== $offset || throw new OffsetOutOfBounds(sprintf('The key `%s` is invalid.', $value));

            if ($offset < 0) {
                $offset += $nbDirectives;
            }

            $deletedKeys[] = $offset;
        }

        $deletedKeys = array_keys(array_count_values($deletedKeys));
        $directives = array_filter(
            array: $this->directives,
            callback: fn ($key): bool => !in_array($key, $deletedKeys, true),
            mode: ARRAY_FILTER_USE_KEY
        );

        if ($directives === $this->directives) {
            return $this;
        }

        return new self(...$this->directives);
    }

    /**
     * Slices the fragment to remove Directives portions.
     */
    public function slice(int $offset, ?int $length = null): self
    {
        $nbDirectives = count($this->directives);
        ($offset >= -$nbDirectives && $offset <= $nbDirectives) || throw new OffsetOutOfBounds(sprintf('No diretive can be found at : `%s`.', $offset));

        $directives = array_slice($this->directives, $offset, $length);

        if ($directives === $this->directives) {
            return $this;
        }

        return new self(...$directives);
    }

    /**
     * Filter the Directives to return a new instance containing a single type of Directives.
     */
    public function byName(string $name): self
    {
        return $this->filter(fn (Directive $directive): bool => $name === $directive->name());
    }

    /**
     * Filter the Directives to return a new instance based on the callback.
     *
     * @param callable(Directive, int=): bool $callback
     */
    public function filter(callable $callback): self
    {
        $directives = array_filter($this->directives, $callback, ARRAY_FILTER_USE_BOTH);

        if ($directives === $this->directives) {
            return $this;
        }

        return new self(...$directives);
    }

    /**
     * Replace the Directive define at a specific offset.
     * Negative offsets are supported.
     *
     * If no Directive is found to the specified offset, an exception is thrown
     */
    public function replace(int $offset, Directive $directive): self
    {
        $currentDirective = $this->nth($offset);

        null !== $currentDirective || throw new OffsetOutOfBounds(sprintf('The key `%s` is invalid.', $offset));
        if ($currentDirective->toString() === $directive->toString() && $directive::class === $currentDirective::class) {
            return $this;
        }

        if ($offset < 0) {
            $offset += count($this->directives);
        }

        $directives = $this->directives;
        $directives[$offset] = $directive;

        return new self(...$directives);
    }

    /**
     * Returns the Directive at a specified offset or null if none is defined.
     */
    public function nth(int $offset): ?Directive
    {
        if ($offset < 0) {
            $offset += count($this->directives);
        }

        return $this->directives[$offset] ?? null;
    }

    /**
     * The first Directive defined on the fragment or null if none are defined.
     */
    public function first(): ?Directive
    {
        return $this->nth(0);
    }

    /**
     * The last Directive defined on the fragment or null if none are defined.
     */
    public function last(): ?Directive
    {
        return $this->nth(-1);
    }

    /**
     * Create a new instance.
     *
     * @throws SyntaxError
     */
    public static function new(Stringable|string|null $value): self
    {
        if (null === $value) {
            return new self();
        }

        $value = (string) $value;
        str_starts_with($value, self::DELIMITER) || throw new SyntaxError('The value "'.$value.'" is not a valid fragment directive.');

        $value = substr($value, strlen(self::DELIMITER));
        $directives = [];
        foreach (explode(self::SEPARATOR, $value) as $directive) {
            $directives[] = str_starts_with($directive, 'text=') ? TextDirective::fromString($directive) : GenericDirective::fromString($directive);
        };

        return new self(...$directives);
    }


    public static function tryNew(Stringable|string|null $value): ?self
    {
        try {
            return self::new($value);
        } catch (Throwable $exception) {
            return null;
        }
    }

    /**
     *  Create a new instance from a URI string or object.
     */
    public static function fromUri(WhatWgUrl|Rfc3986Uri|Stringable|string $uri): self
    {
        if ($uri instanceof Modifier) {
            $uri = $uri->uri();
        }

        if ($uri instanceof Rfc3986Uri) {
            return self::new($uri->getRawFragment());
        }

        if ($uri instanceof WhatWgUrl) {
            return self::new($uri->getFragment());
        }

        if ($uri instanceof Psr7UriInterface) {
            $fragment = $uri->getFragment();

            return self::new('' === $fragment ? null : $fragment);
        }

        if (!$uri instanceof UriInterface) {
            $uri = Uri::new($uri);
        }

        return self::new($uri->getFragment());
    }

    final public function when(callable|bool $condition, callable $onSuccess, ?callable $onFail = null): static
    {
        if (!is_bool($condition)) {
            $condition = $condition($this);
        }

        return match (true) {
            $condition => $onSuccess($this),
            null !== $onFail => $onFail($this),
            default => $this,
        } ?? $this;
    }
}
