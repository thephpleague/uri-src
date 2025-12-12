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
use League\Uri\UriString;
use Traversable;
use Uri\Rfc3986\Uri;
use Uri\WhatWg\Url;

use function explode;
use function implode;
use function ltrim;
use function substr;

final class UriPath implements Countable, IteratorAggregate
{
    private readonly UriPathType $type;
    /** @var list<string> */
    private readonly array $segments;

    /**
     * Decoding SHOULD be taken into account
     * Unless I am wrong in both RFC3986 and WHATWG URL
     * Path encoding is the same so one object
     * should suffice
     */
    public function __construct(string $path)
    {
        if ($path === '') {
            $this->type = UriPathType::Relative;
            $this->segments = [];
        } else {
            $this->type = ($path[0] === '/') ? UriPathType::Absolute : UriPathType::Relative;
            $this->segments = explode('/', UriPathType::Absolute === $this->type ? substr($path, 1) : $path);
        }
    }

    public static function fromUri(Uri|Url $uri): self
    {
        return new self(match (true) {
            $uri instanceof Uri => $uri->getRawPath(),
            $uri instanceof Url => $uri->getPath(),
        });
    }

    public function getType(): UriPathType
    {
        return $this->type;
    }

    /**
     * The list returned SHOULD contained decoded segments
     *
     * @return list<string>
     */
    public function getAll(): array
    {
        return $this->segments;
    }

    /**
     * The returned value SHOULD be decoded
     */
    public function getSegment(int $index): ?string
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
     * Encoding SHOULD be taken into account
     */
    public function toRawString(): string
    {
        return ($this->type === UriPathType::Absolute ? '' : '/') . implode('/', $this->segments);
    }

    public function toString(): string
    {
        return UriString::removeDotSegments($this->toRawString());
    }

    public function withType(UriPathType $type): self
    {
        if ($type === $this->type) {
            return $this;
        }

        return new self(match ($type) {
            UriPathType::Relative => ltrim('/', $this->toRawString()),
            UriPathType::Absolute => '/'.ltrim('/', $this->toRawString()),
        });
    }

    /**
     * Added segments are
     * @param list<string> $segments
     */
    public function withSegments(array $segments): self
    {
        $path = implode('/', $segments);
        if ($path === implode('/', $this->segments)) {
            return $this;
        }

        if ($this->type === UriPathType::Absolute) {
            if ($path[0] !== '/') {
                $path = '/'.$path;
            }

            return new self('/'. $path);
        }

        if ($path[0] === '/') {
            $path = substr($path, 1);
        }

        return new self($path);
    }

    public function __debugInfo(): array
    {
        return [
            'type' => $this->type,
            'segments' => $this->segments,
        ];
    }
}
