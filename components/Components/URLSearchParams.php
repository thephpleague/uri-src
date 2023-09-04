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

use ArgumentCountError;
use Closure;
use Countable;
use Iterator;
use IteratorAggregate;
use League\Uri\Contracts\QueryInterface;
use League\Uri\Contracts\UriComponentInterface;
use League\Uri\Contracts\UriInterface;
use League\Uri\KeyValuePair\Converter;
use League\Uri\QueryString;
use League\Uri\Uri;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;
use TypeError;

use function array_map;
use function array_reduce;
use function count;
use function func_get_arg;
use function func_num_args;
use function is_iterable;
use function is_string;
use function str_starts_with;

/**
 * @see https://url.spec.whatwg.org/#interface-urlsearchparams
 *
 * @implements IteratorAggregate<array{0:string, 1:string}>
 */
final class URLSearchParams implements Countable, IteratorAggregate, UriComponentInterface
{
    private QueryInterface $pairs;

    /**
     * New instance.
     *
     * A string, which will be parsed from application/x-www-form-urlencoded format. A leading '?' character is ignored.
     * A literal sequence of name-value string pairs, or any object with an iterator that produces a sequence of string pairs.
     * A record of string keys and string values. Note that nesting is not supported.
     */
    public function __construct(object|array|string|null $query = '')
    {
        $pairs = match (true) {
            $query instanceof self,
            $query instanceof QueryInterface => $query,
            $query instanceof UriComponentInterface => self::parsePairs($query->value()),
            is_iterable($query) => Query::fromPairs($query),
            $query instanceof Stringable, null === $query, is_string($query) => self::parsePairs(self::formatQuery($query)),
            default => self::yieldPairs($query),
        };

        $normalizer = fn (array $carry, array $pair): array => match (true) {
            null !== $pair[1] => [...$carry, $pair],
            '' !== $pair[0] => [...$carry, [$pair[0], '']],
            '' === $pair[0] => $carry,
        };

        $this->pairs = Query::fromPairs(array_reduce([...$pairs], $normalizer, []));
    }

    private static function parsePairs(string|null $query): array
    {
        return QueryString::parseFromValue($query, self::converter());
    }

    private static function yieldPairs(object|array $records): Iterator
    {
        foreach ($records as $key => $value) { /* @phpstan-ignore-line */
            yield [self::uvString($key), self::uvString($value)];
        }
    }

    private static function formatQuery(Stringable|string|null $value): string
    {
        return match (true) {
            null === $value => '',
            str_starts_with((string) $value, '?') => substr((string) $value, 1),
            default => (string) $value,
        };
    }

    /**
     * Normalizes type to UVString.
     *
     * @see https://webidl.spec.whatwg.org/#idl-USVString
     */
    private static function uvString(Stringable|string|float|int|bool|null $value): string
    {
        return match (true) {
            null === $value => 'null',
            false === $value => 'false',
            true === $value => 'true',
            default => (string) $value,
        };
    }

    /**
     * Encode/Decode string using The application/x-www-form-urlencoded parser rules.
     *
     * @see https://url.spec.whatwg.org/#urlencoded-parsing
     */
    private static function converter(): Converter
    {
        static $converter;
        $converter = $converter ?? Converter::new('&')
            ->withEncodingMap(['%20' => '+', '%2A' => '*']);

        return $converter;
    }

    /**
     * Returns a new instance from a string or a stringable object.
     *
     * The input will be parsed from application/x-www-form-urlencoded format.
     * The leading '?' character if present is ignored.
     */
    public static function new(Stringable|string|null $value): self
    {
        return new self(Query::fromPairs(QueryString::parseFromValue(self::formatQuery($value), self::converter())));
    }

    /**
     * Returns a new instance from a literal sequence of name-value string pairs,
     * or any object with an iterator that produces a sequence of string pairs.
     *
     * @param iterable<int, array{0:string, 1:string|null}> $pairs
     */
    public static function fromPairs(iterable $pairs): self
    {
        return new self(Query::fromPairs($pairs));
    }

    /**
     * Returns a new instance from a record of string keys and string values.
     *
     * Note that nesting is not supported.
     */
    public static function fromRecords(object|iterable $records): self
    {
        return new self(Query::fromPairs(self::yieldPairs($records)));
    }

    /**
     * Returns a new instance from a URI.
     */
    public static function fromUri(Stringable|string $uri): self
    {
        $query = match (true) {
            $uri instanceof Psr7UriInterface,
            $uri instanceof UriInterface => $uri->getQuery(),
            default => Uri::new($uri)->getQuery(),
        };

        return new self(Query::fromPairs(QueryString::parseFromValue($query, self::converter())));
    }

    /**
     * Returns a new instance from the result of PHP's parse_str.
     */
    public static function fromParameters(iterable $parameters): self
    {
        return new self(Query::fromParameters($parameters));
    }

    public function value(): string
    {
        return (string) QueryString::buildFromPairs($this->pairs, self::converter());
    }

    /**
     * Returns a query string suitable for use in a URL.
     */
    public function toString(): string
    {
        return $this->value();
    }

    public function __toString(): string
    {
        return $this->value();
    }

    public function jsonSerialize(): string
    {
        return $this->value();
    }

    public function getUriComponent(): string
    {
        $value = $this->value();

        return match ('') {
            $value => '',
            default => '?'.$value,
        };
    }

    /**
     * Returns an iterator allowing iteration through all keys contained in this object.
     *
     * @return iterable<string>
     */
    public function keys(): iterable
    {
        foreach ($this->pairs as [$key, $__]) {
            yield $key;
        }
    }

    /**
     * Returns an iterator allowing iteration through all values contained in this object.
     *
     * @return iterable<string>
     */
    public function values(): iterable
    {
        foreach ($this->pairs as [$__, $value]) {
            yield $value ?? '';
        }
    }

    /**
     * Tells whether the specified parameter is in the search parameters.
     *
     * The method requires at least one parameter as the pair name (string or null)
     * and an optional second and last parameter as the pair value (Stringable|string|float|int|bool|null)
     * <code>
     * $params = new URLSearchParams('a=b&c);
     * $params->has('c');      // return true
     * $params->has('a', 'b'); // return true
     * $params->has('a', 'c'); // return false
     * </code>
     */
    public function has(): bool
    {
        $name = match (true) {
            func_num_args() < 1 => throw new ArgumentCountError('The required name is missing.'),
            null === ($parameter = func_get_arg(0)) => 'null',
            is_string($parameter) => $parameter,
            default => throw new TypeError('The required name must be a string or null; '.gettype($parameter).' received.'),
        };

        return match (func_num_args()) {
            1 => $this->pairs->has($name),
            2 => $this->pairs->hasPair($name, self::uvString(func_get_arg(1))), /* @phpstan-ignore-line */
            default => throw new ArgumentCountError(__METHOD__.' requires at least one argument as the pair name and a second optional argument as the pair value.'),
        };
    }

    /**
     * Returns the first value associated to the given search parameter or null if none exists.
     */
    public function get(?string $name): ?string
    {
        return match (true) {
            $this->has($name) => $this->pairs->get(self::uvString($name)) ?? '',
            default => null,
        };
    }

    /**
     * Returns all the values associated with a given search parameter as an array.
     *
     * @return array<string>
     */
    public function getAll(?string $name): array
    {
        return array_map(fn (?string  $value): string => $value ?? '', $this->pairs->getAll(self::uvString($name)));
    }

    /**
     * Tells whether the instance has some parameters.
     */
    public function isNotEmpty(): bool
    {
        return ! $this->isEmpty();
    }

    /**
     * Tells whether the instance has no parameters.
     */
    public function isEmpty(): bool
    {
        return 0 === $this->size();
    }

    /**
     * Returns the total number of search parameter entries.
     */
    public function size(): int
    {
        return count($this->pairs);
    }

    /**
     * @see URLSearchParams::size()
     */
    public function count(): int
    {
        return $this->size();
    }

    /**
     * Allowing iteration through all key/value pairs contained in this object.
     *
     * The iterator returns key/value pairs in the same order as they appear in the query string.
     * The key and value of each pair are string objects.
     */
    public function entries(): Iterator
    {
        yield from $this->pairs;
    }

    /**
     * @see URLSearchParams::entries()
     */
    public function getIterator(): Iterator
    {
        return $this->entries();
    }

    /**
     * Allows iteration through all values contained in this object via a callback function.
     *
     * @param Closure(string $value, string $key): void $callback
     */
    public function each(Closure $callback): void
    {
        foreach ($this->pairs->pairs() as $key => $value) {
            $callback($value ?? '', $key);
        }
    }

    private function updateQuery(QueryInterface $query): void
    {
        if ($query->value() !== $this->pairs->value()) {
            $this->pairs = $query;
        }
    }

    /**
     * Sets the value associated with a given search parameter to the given value.
     *
     * If there were several matching values, this method deletes the others.
     * If the search parameter doesn't exist, this method creates it.
     */
    public function set(?string $name, Stringable|string|float|int|bool|null $value): void
    {
        $this->updateQuery($this->pairs->withPair(self::uvString($name), self::uvString($value)));
    }

    /**
     * Appends a specified key/value pair as a new search parameter.
     */
    public function append(?string $name, Stringable|string|float|int|bool|null $value): void
    {
        $this->updateQuery($this->pairs->appendTo(self::uvString($name), self::uvString($value)));
    }

    /**
     * Deletes specified parameters and their associated value(s) from the list of all search parameters.
     *
     *  The method requires at least one parameter as the pair name (string or null)
     *  and an optional second and last parameter as the pair value (Stringable|string|float|int|bool|null)
     * <code>
     * $params = new URLSearchParams('a=b&c);
     * $params->delete('c'); //delete all parameters with the key 'c'
     * $params->delete('a', 'b') //delete all pairs with the key 'a' and the value 'b'
     * </code>
     */
    public function delete(): void
    {
        $name = match (true) {
            func_num_args() < 1 => throw new ArgumentCountError('The required name is missing.'),
            null === ($parameter = func_get_arg(0)) => 'null',
            is_string($parameter) => $parameter,
            default => throw new TypeError('The required name must be a string or null; '.gettype($parameter).' received.'),
        };
        $newQuery = match (func_num_args()) {
            1 => $this->pairs->withoutPairByKey($name),
            2 => $this->pairs->withoutPairByKeyValue($name, self::uvString(func_get_arg(1))), /* @phpstan-ignore-line */
            default => throw new ArgumentCountError(__METHOD__.' requires at least one r as the pair name and a second optional argument as the pair value.'),
        };

        $this->updateQuery($newQuery);
    }

    /**
     * Sorts all key/value pairs contained in this object in place and returns undefined.
     *
     * The sort order is according to unicode code points of the keys. This method
     * uses a stable sorting algorithm (i.e. the relative order between
     * key/value pairs with equal keys will be preserved).
     */
    public function sort(): void
    {
        $this->updateQuery($this->pairs->sort());
    }
}
