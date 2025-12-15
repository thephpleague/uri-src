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
use Exception;
use IteratorAggregate;
use League\Uri\Encoder;
use League\Uri\UriString;
use Traversable;

use function array_keys;
use function array_map;
use function count;
use function explode;
use function implode;
use function ltrim;
use function substr;

/**
 * @phpstan-type SerializedShape array{0: array{path: string}, 1: array{}}
 * @implements IteratorAggregate<int, string>
 */
final class PathSegments implements Countable, IteratorAggregate
{
    private readonly PathType $type;
    /** @var list<string> */
    private readonly array $segments;

    /**
     * The submitted string is the encoded path as returned by Url::getPath or Uri::getPath or Uri::getRawpath.
     *
     * @throws InvalidUriException If the path contains invalid characters
     */
    public function __construct(string $path)
    {
        (!str_contains($path, '?') && !str_contains($path, '#')) || throw new InvalidUriException('The path `'.$path.'` contains invalid URI path characters.');

        $decoder = fn (string $segment): string => (string) Encoder::decodeNecessary($segment);

        [$this->type, $this->segments] = match (true) {
            '' === $path => [PathType::Relative, []],
            '/' === $path => [PathType::Absolute, ['']],
            '/' === $path[0] => [PathType::Absolute, array_map($decoder, explode('/', substr($path, 1)))],
            default => [PathType::Relative, array_map($decoder, explode('/', $path))],
        };
    }

    public function getType(): PathType
    {
        return $this->type;
    }

    /**
     * The returned value be decoded.
     */
    public function get(int $index): ?string
    {
        return $this->segments[$index] ?? null;
    }

    /**
     * The returned decoded segments.
     *
     * @return list<string>
     */
    public function getAll(): array
    {
        return $this->segments;
    }

    /**
     * The Iterator version of getAll
     * Not sure if both methods are needed.
     *
     * @return Traversable<string>
     */
    public function getIterator(): Traversable
    {
        yield from $this->segments;
    }

    public function count(): int
    {
        return count($this->segments);
    }

    public function getFirst(): ?string
    {
        return $this->get(0);
    }

    public function getLast(): ?string
    {
        $index = array_key_last($this->segments);

        return null !== $index ? $this->get($index) : null;
    }

    public function has(string $segment): bool
    {
        return in_array($segment, $this->segments, true);
    }

    public function getIndexOf(string $segment): ?int
    {
        /** @var list<int> $res */
        $res = array_keys($this->segments, $segment, true);

        return $res[0] ?? null;
    }

    public function getLastIndexOf(string $segment): ?int
    {
        /** @var list<int> $res */
        $res = array_keys($this->segments, $segment, true);

        return $res[count($res) - 1] ?? null;
    }

    /**
     * The raw path.
     */
    public function toRawString(): string
    {
        return (PathType::Absolute === $this->type ? '/' : '').implode('/', array_map(Encoder::encodePath(...), $this->segments));
    }

    /**
     * The raw path normalize using the remove dot segments algorithm.
     */
    public function toString(): string
    {
        return UriString::removeDotSegments($this->toRawString());
    }

    /**
     * Returns a new instance with a new type.
     */
    public function withType(PathType $type): self
    {
        return match (true) {
            $this->type === $type => $this,
            PathType::Absolute === $type => new self('/'.$this->toRawString()),
            default => new self(ltrim($this->toRawString(), '/')),
        };
    }

    /**
     * Returns a new instance with the new segments.
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
        if (PathType::Absolute === $this->type) {
            if ('/' !== $path[0]) {
                $path = '/'.$path;
            }

            return new self(Encoder::encodePath($path));
        }

        if ('/' === $path[0]) {
            $path = ltrim($path, '/');
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

    /**
     * @return SerializedShape
     */
    public function __serialize(): array
    {
        return [['path' => $this->toRawString()], []];
    }

    /**
     * @param SerializedShape $data
     *
     * @throws Exception|InvalidUriException
     */
    public function __unserialize(array $data): void
    {
        [$properties] = $data;
        $path = new self($properties['path'] ?? throw new Exception('The `path` property is missing from the serialized object.'));

        $this->type = $path->type;
        $this->segments = $path->segments;
    }
}
