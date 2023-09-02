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
use League\Uri\KeyValuePair\Converter;
use League\Uri\QueryString;
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
 * @implements IteratorAggregate<array{0:string, 1:string}>
 */
final class URLSearchParams implements Countable, IteratorAggregate, Stringable
{
    private const REGEXP_NON_ASCII_PATTERN = '/[^\x20-\x7f]/';
    private QueryInterface $query;

    /**
     * New instance.
     *
     * A string, which will be parsed from application/x-www-form-urlencoded format. A leading '?' character is ignored.
     * A literal sequence of name-value string pairs, or any object with an iterator that produces a sequence of string pairs.
     * A record of string keys and string values. Note that nesting is not supported.
     */
    public function __construct(object|array|string|null $query = null)
    {
        $rawQuery = match (true) {
            $query instanceof QueryInterface => $query,
            $query instanceof UriComponentInterface => self::resolvePairs($query->value()),
            is_iterable($query) => Query::fromPairs($query),
            $query instanceof Stringable,
            null === $query,
            is_string($query) => match (true) {
                str_starts_with((string) $query, '?') => self::resolvePairs(substr((string) $query, 1)),
                default => self::resolvePairs($query),
            },
            default => (function (object $object) {
                foreach ($object as $key => $value) { /* @phpstan-ignore-line */
                    yield [$this->filterValue($key), $this->filterValue($value)];
                }
            })($query),
        };

        $pairs = array_reduce([...$rawQuery], fn (array $carry, array $pair): array => match (true) {
            null !== $pair[1] => [...$carry, $pair],
            '' !== $pair[0] => [...$carry, [$pair[0], '']],
            '' === $pair[0] => $carry,
        }, []);

        $this->query = Query::fromPairs($pairs);
    }

    private static function resolvePairs(Stringable|string|null $query): array
    {
        return QueryString::parseFromValue($query, self::converter());
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
     * Returns a new instance from a URI.
     */
    public static function fromUri(Stringable|string $uri): self
    {
        return new self(Query::fromUri($uri));
    }

    /**
     * Returns a query string suitable for use in a URL.
     */
    public function toString(): string
    {
        return (string) QueryString::buildFromPairs($this->query, self::converter());
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Returns an iterator allowing iteration through all keys contained in this object.
     *
     * @return iterable<string>
     */
    public function keys(): iterable
    {
        foreach ($this->query as [$key, $__]) {
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
        foreach ($this->query as [$__, $value]) {
            yield $value ?? '';
        }
    }

    /**
     * Tells whether the specified parameter is in the search parameters.
     *
     * The method can be used with one or two arguments representing the key and the optional value
     * <code>
     * $params = new URLSearchParams('a=b&c);
     * $params->has('c');      // return true
     * $params->has('a', 'b'); // return true
     * $params->has('a', 'c'); // return false
     * </code>
     */
    public function has(): bool
    {
        if (func_num_args() < 1) {
            throw new ArgumentCountError('The required key is missing.');
        }

        $key = func_get_arg(0);
        if (null !== $key && !is_string($key)) {
            throw new TypeError('The required key must be a string or null.');
        }

        $key = $this->filterValue($key);
        $argumentCount = func_num_args();
        if (1 === $argumentCount) {
            return $this->query->has($key);
        }

        if (2 !== $argumentCount) {
            throw new ArgumentCountError(__METHOD__.' requires a key and an optional value.');
        }

        $value = $this->filterValue(func_get_arg(1));  /* @phpstan-ignore-line */
        foreach ($this->query as [$name, $content]) {
            if ($key === $name && $value === $content) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the first value associated to the given search parameter or null if none exists.
     */
    public function get(string $name): ?string
    {
        return match (true) {
            $this->has($name) => $this->query->get($name) ?? '',
            default => null,
        };
    }

    /**
     * Returns all the values associated with a given search parameter as an array.
     *
     * @return array<string>
     */
    public function getAll(string $name): array
    {
        return array_map(fn (string|null $value): string => $value ?? '', $this->query->getAll($name));
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
        return 0 === count($this->query);
    }

    /**
     * Returns the total number of search parameter entries.
     */
    public function count(): int
    {
        return count($this->query);
    }

    /**
     * Allowing iteration through all key/value pairs contained in this object.
     *
     * The iterator returns key/value pairs in the same order as they appear in the query string.
     * The key and value of each pair are string objects.
     */
    public function getIterator(): Iterator
    {
        yield from $this->query;
    }

    /**
     * Allows iteration through all values contained in this object via a callback function.
     *
     * @param Closure(string $value, string $key): void $callback
     */
    public function each(Closure $callback): void
    {
        foreach ($this->query as [$key, $value]) {
            $callback($value ?? '', $key);
        }
    }

    private function filterValue(Stringable|string|float|int|bool|null $value): string
    {
        return match (true) {
            null === $value => 'null',
            false === $value => 'false',
            true === $value => 'true',
            default => (string) $value,
        };
    }

    private function updateQuery(QueryInterface $query): void
    {
        if ($query->value() !== $this->query->value()) {
            $this->query = $query;
        }
    }

    /**
     * appends a specified key/value pair as a new search parameter.
     */
    public function append(string|null $key, Stringable|string|float|int|bool|null $value): void
    {
        $this->updateQuery($this->query->appendTo($this->filterValue($key), $this->filterValue($value)));
    }

    /**
     * Deletes specified parameters and their associated value(s) from the list of all search parameters.
     *
     * The method expects at least on parameter the key (string or null)
     * and an optional second and last parameter the value (Stringable|string|float|int|bool|null)
     * <code>
     * $params = new URLSearchParams('a=b&c);
     * $params->delete('c'); //delete all parameters with the key 'c'
     * $params->delete('a', 'b') //delete all pairs with the key 'a' and the value 'b'
     * </code>
     */
    public function delete(): void
    {
        if (func_num_args() < 1) {
            throw new ArgumentCountError('The required key is missing.');
        }

        $key = func_get_arg(0);
        if (null !== $key && !is_string($key)) {
            throw new TypeError('The required key must be a string or null.');
        }

        $key = $this->filterValue($key);
        $argumentCount = func_num_args();
        $newQuery = match (true) {
            1 === $argumentCount => $this->query->withoutPairByKey($key),
            2 === $argumentCount => $this->query->withoutPairByKeyValue($key, $this->filterValue(func_get_arg(1))), /* @phpstan-ignore-line */
            default => throw new ArgumentCountError(__METHOD__.' requires a key and an optional value.'),
        };

        $this->updateQuery($newQuery);
    }

    /**
     * sets the value associated with a given search parameter to the given value.
     *
     * If there were several matching values, this method deletes the others.
     * If the search parameter doesn't exist, this method creates it.
     */
    public function set(string|null $key, Stringable|string|float|int|bool|null $value): void
    {
        $this->updateQuery($this->query->withPair($this->filterValue($key), $this->filterValue($value)));
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
        $parameters = array_reduce([...$this->query], function (array $carry, array $pair) {
            $carry[$pair[0]] ??= [];
            $carry[$pair[0]][] = $pair[1];

            return $carry;
        }, []);

        $codepoints = fn (string $str): string => implode(
            '.',
            array_map(
                fn (string $char) => mb_ord($char), /* @phpstan-ignore-line */
                (array) preg_split(pattern:'//u', subject: $str, flags: PREG_SPLIT_NO_EMPTY)
            )
        );

        $compare = fn (string $name1, string $name2): int => match (true) {
            1 === preg_match(self::REGEXP_NON_ASCII_PATTERN, $name1),
            1 === preg_match(self::REGEXP_NON_ASCII_PATTERN, $name2) => strcmp($codepoints($name1), $codepoints($name2)),
            default => strcmp($name1, $name2),
        };

        uksort($parameters, $compare);

        $pairs = [];
        foreach ($parameters as $key => $values) {
            foreach ($values as $value) {
                $pairs[] = [$key, $value];
            }
        }

        $this->updateQuery(Query::fromPairs($pairs));
    }
}
