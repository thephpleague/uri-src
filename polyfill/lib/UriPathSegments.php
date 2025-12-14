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

namespace Uri;

use Countable;
use IteratorAggregate;
use League\Uri\Encoder;
use League\Uri\UriString;
use Traversable;

use function array_map;
use function count;
use function explode;
use function implode;
use function ltrim;
use function substr;

/**
 * @implements IteratorAggregate<int, string>
 */
final class UriPathSegments implements Countable, IteratorAggregate
{
    private readonly UriPathType $type;
    /** @var list<string>|array{} */
    private readonly array $segments;

    /**
     * The submitted string is the encoded path as returned by Url::getPath or Uri::getPath or Uri::getRawpath
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        [$this->type, $this->segments] = match (true) {
            '' === $path => [UriPathType::Relative, []],
            '/' === $path => [UriPathType::Absolute, ['']],
            '/' === $path[0] => [UriPathType::Absolute, array_map(Encoder::decodeNecessary(...), explode('/', substr($path, 1)))],
            default => [UriPathType::Relative, array_map(Encoder::decodeNecessary(...), explode('/', $path))],
        };
    }

    public function getType(): UriPathType
    {
        return $this->type;
    }

    /**
     * The returned decoded segments
     *
     * @return list<string>
     */
    public function getAll(): array
    {
        return $this->segments;
    }

    /**
     * The returned value be decoded
     */
    public function get(int $index): ?string
    {
        return $this->segments[$index] ?? null;
    }

    public function count(): int
    {
        return count($this->segments);
    }

    /**
     * The Iterator version of getAll
     * Not sure if both methods are needed
     *
     * @return Traversable<string>
     */
    public function getIterator(): Traversable
    {
        yield from $this->segments;
    }

    /**
     * The raw path
     */
    public function toRawString(): string
    {
        return ($this->type === UriPathType::Absolute ? '/' : '') . implode('/', array_map(Encoder::encodePath(...), $this->segments));
    }

    /**
     * The raw path normalize using the remove dot segments algorithm.
     */
    public function toString(): string
    {
        return UriString::removeDotSegments($this->toRawString());
    }

    /**
     * Returns a new instance with a new type
     */
    public function withType(UriPathType $type): self
    {
        return $type === $this->type ? $this : new self(match ($type) {
            UriPathType::Relative => ltrim('/', $this->toRawString()),
            UriPathType::Absolute => '/'.ltrim('/', $this->toRawString()),
        });
    }

    /**
     * Returns a new instance with the new segments
     *
     * @param list<string> $segments
     */
    public function withSegments(array $segments): self
    {
        $segments = array_map(fn (string $segment) => str_replace('/', '%2F', $segment), $segments);
        if ($segments === $this->segments) {
            return $this;
        }

        $path = implode('/', $segments);
        if ($this->type === UriPathType::Absolute) {
            if ($path[0] !== '/') {
                $path = '/'.$path;
            }

            return new self(Encoder::encodePath($path));
        }

        if ($path[0] === '/') {
            $path = substr($path, 1);
        }

        return new self(Encoder::encodePath($path));
    }

    public function __debugInfo(): array
    {
        return [
            'type' => $this->type,
            'segments' => $this->segments,
        ];
    }
}
