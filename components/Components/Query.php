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

use Iterator;
use League\Uri\Contracts\QueryInterface;
use League\Uri\Contracts\UriComponentInterface;
use League\Uri\Contracts\UriInterface;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\QueryString;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;
use Traversable;
use function array_column;
use function array_count_values;
use function array_filter;
use function array_flip;
use function array_intersect;
use function array_map;
use function array_merge;
use function array_values;
use function count;
use function http_build_query;
use function implode;
use function is_bool;
use function is_int;
use function iterator_to_array;
use function preg_match;
use function preg_quote;
use function preg_replace;
use const PHP_QUERY_RFC1738;
use const PHP_QUERY_RFC3986;

final class Query extends Component implements QueryInterface
{
    /** @var array<int, array{0:string, 1:string|null}> */
    private readonly array $pairs;
    /** @var non-empty-string */
    private readonly string $separator;
    private readonly array $parameters;

    /**
     * Returns a new instance.
     *
     * @param non-empty-string $separator
     */
    private function __construct(
        Stringable|string|null $query,
        string $separator = '&',
        int $encType = PHP_QUERY_RFC3986
    ) {
        $this->pairs = QueryString::parse($query, $separator, $encType);
        $this->parameters = QueryString::extract($query, $separator, $encType);
        $this->separator = $separator;
    }

    public static function new(Stringable|string|null $value = null): self
    {
        return self::fromRFC3986($value);
    }

    /**
     * Returns a new instance from the result of PHP's parse_str.
     *
     * @param non-empty-string $separator
     */
    public static function fromParameters(iterable $parameters, string $separator = '&'): self
    {
        if ($parameters instanceof QueryInterface) {
            return self::fromRFC3986($parameters->value(), $parameters->getSeparator())->withSeparator($separator);
        }

        $newParams = match (true) {
            $parameters instanceof Traversable => iterator_to_array($parameters),
            default => $parameters,
        };

        return match (true) {
            [] === $newParams => self::new(),
            default => new self(
                http_build_query($newParams, '', $separator, PHP_QUERY_RFC3986),
                $separator,
                PHP_QUERY_RFC3986
            )
        };
    }

    /**
     * Returns a new instance from the result of QueryString::parse.
     *
     * @param iterable<int, array{0:string, 1:string|null}> $pairs
     * @param non-empty-string                              $separator
     */
    public static function fromPairs(iterable $pairs, string $separator = '&'): self
    {
        return new self(QueryString::build($pairs, $separator), $separator, PHP_QUERY_RFC3986);
    }

    /**
     * Create a new instance from a URI object.
     */
    public static function fromUri(Stringable|string $uri): self
    {
        $uri = self::filterUri($uri);
        $component = $uri->getQuery();

        return match (true) {
            $uri instanceof UriInterface, '' !== $component => new self($component),
            default => new self(null),
        };
    }

    /**
     * Returns a new instance.
     *
     * @param non-empty-string $separator
     */
    public static function fromRFC3986(Stringable|string|null $query = null, string $separator = '&'): self
    {
        return new self($query, $separator, PHP_QUERY_RFC3986);
    }

    /**
     * Returns a new instance.
     *
     * @param non-empty-string $separator
     */
    public static function fromRFC1738(Stringable|string|null $query = null, string $separator = '&'): self
    {
        return new self($query, $separator, PHP_QUERY_RFC1738);
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function value(): ?string
    {
        return QueryString::build($this->pairs, $this->separator);
    }

    public function getUriComponent(): string
    {
        return match (true) {
            [] === $this->pairs => '',
            default => '?'.$this->value(),
        };
    }

    public function toRFC1738(): ?string
    {
        return QueryString::build($this->pairs, $this->separator, PHP_QUERY_RFC1738);
    }

    public function jsonSerialize(): ?string
    {
        return $this->toRFC1738();
    }

    public function count(): int
    {
        return count($this->pairs);
    }

    public function getIterator(): Iterator
    {
        yield from $this->pairs;
    }

    public function pairs(): iterable
    {
        foreach ($this->pairs as $pair) {
            yield $pair[0] => $pair[1];
        }
    }

    public function has(string ...$keys): bool
    {
        foreach ($keys as $key) {
            if (!isset(array_flip(array_column($this->pairs, 0))[$key])) {
                return false;
            }
        }

        return [] !== $keys;
    }

    public function get(string $key): ?string
    {
        foreach ($this->pairs as $pair) {
            if ($key === $pair[0]) {
                return $pair[1];
            }
        }

        return null;
    }

    public function getAll(string $key): array
    {
        return array_column(array_filter($this->pairs, fn (array $pair): bool => $key === $pair[0]), 1);
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    public function parameter(string $name): mixed
    {
        return $this->parameters[$name] ?? null;
    }

    public function hasParameter(string ...$names): bool
    {
        foreach ($names as $name) {
            if (!isset($this->parameters[$name])) {
                return false;
            }
        }

        return [] !== $names;
    }

    public function withSeparator(string $separator): self
    {
        return match (true) {
            $separator === $this->separator => $this,
            '' === $separator => throw new SyntaxError('The separator character can not be the empty string.'),
            default => self::fromPairs($this->pairs, $separator),
        };
    }

    public function sort(): self
    {
        if (count($this->pairs) === count(array_count_values(array_column($this->pairs, 0)))) {
            return $this;
        }

        /** @var array<int, array{0:string, 1:string|null}> $pairs */
        $pairs = array_merge(...array_values(array_reduce($this->pairs, $this->reducePairs(...), [])));
        if ($pairs === $this->pairs) {
            return $this;
        }

        return self::fromPairs($pairs);
    }

    /**
     * Reduce pairs to help sorting them according to their keys.
     */
    private function reducePairs(array $pairs, array $pair): array
    {
        $pairs[$pair[0]] = $pairs[$pair[0]] ?? [];
        $pairs[$pair[0]][] = $pair;

        return $pairs;
    }

    public function withoutDuplicates(): self
    {
        if (count($this->pairs) === count(array_count_values(array_column($this->pairs, 0)))) {
            return $this;
        }

        $pairs = array_reduce($this->pairs, $this->removeDuplicates(...), []);
        if ($pairs === $this->pairs) {
            return $this;
        }

        return self::fromPairs($pairs, $this->separator);
    }

    /**
     * Adds a query pair only if it is not already present in a given array.
     */
    private function removeDuplicates(array $pairs, array $pair): array
    {
        if (!in_array($pair, $pairs, true)) {
            $pairs[] = $pair;
        }

        return $pairs;
    }

    public function withoutEmptyPairs(): self
    {
        $pairs = array_filter($this->pairs, $this->filterEmptyPair(...));
        if ($pairs === $this->pairs) {
            return $this;
        }

        return self::fromPairs($pairs);
    }

    /**
     * Empty Pair filtering.
     */
    private function filterEmptyPair(array $pair): bool
    {
        return '' !== $pair[0] && null !== $pair[1] && '' !== $pair[1];
    }

    public function withoutNumericIndices(): self
    {
        $pairs = array_map($this->encodeNumericIndices(...), $this->pairs);
        if ($pairs === $this->pairs) {
            return $this;
        }

        return self::fromPairs($pairs, $this->separator);
    }

    /**
     * Remove numeric indices from pairs.
     *
     * @param array{0:string, 1:string|null} $pair
     *
     * @return array{0:string, 1:string|null}
     */
    private function encodeNumericIndices(array $pair): array
    {
        static $regexp = ',\[\d+],';

        $pair[0] = (string) preg_replace($regexp, '[]', $pair[0]);

        return $pair;
    }

    public function withPair(string $key, Stringable|string|int|bool|null $value): QueryInterface
    {
        $pairs = $this->addPair($this->pairs, [$key, $this->filterPair($value)]);

        return match (true) {
            $pairs === $this->pairs => $this,
            default => self::fromPairs($pairs, $this->separator),
        };
    }

    /**
     * Add a new pair to the query key/value list.
     *
     * If there are any key/value pair whose kay is kay, in the list,
     * set the value of the first such key/value pair to value and remove the others.
     * Otherwise, append a new key/value pair whose key is key and value is value, to the list.
     */
    private function addPair(array $list, array $pair): array
    {
        $found = false;
        $reducer = static function (array $pairs, array $srcPair) use ($pair, &$found): array {
            if ($pair[0] !== $srcPair[0]) {
                $pairs[] = $srcPair;

                return $pairs;
            }

            if (!$found) {
                $pairs[] = $pair;
                $found = true;

                return $pairs;
            }

            return $pairs;
        };

        $pairs = array_reduce($list, $reducer, []);
        if (!$found) {
            $pairs[] = $pair;
        }

        return $pairs;
    }

    public function merge(Stringable|string|null $query): QueryInterface
    {
        $pairs = $this->pairs;
        foreach (QueryString::parse(self::filterComponent($query), $this->separator) as $pair) {
            $pairs = $this->addPair($pairs, $pair);
        }

        if ($pairs === $this->pairs) {
            return $this;
        }

        return self::fromPairs($pairs, $this->separator);
    }

    /**
     * Validate the given pair.
     *
     * To be valid the pair must be the null value, a scalar or a collection of scalar and null values.
     */
    private function filterPair(Stringable|string|int|bool|null $value): ?string
    {
        if ($value instanceof UriComponentInterface) {
            return $value->value();
        }

        if (null === $value) {
            return null;
        }

        if (is_bool($value)) {
            return true === $value ? 'true' : 'false';
        }

        return (string) $value;
    }

    public function withoutPair(string ...$keys): QueryInterface
    {
        if ([] === $keys) {
            return $this;
        }

        $keysToRemove = array_intersect($keys, array_column($this->pairs, 0));
        if ([] === $keysToRemove) {
            return $this;
        }

        return self::fromPairs(
            array_filter($this->pairs, static fn (array $pair): bool => !in_array($pair[0], $keysToRemove, true)),
            $this->separator
        );
    }

    public function appendTo(string $key, Stringable|string|int|bool|null $value): QueryInterface
    {
        return self::fromPairs([...$this->pairs, [$key, $this->filterPair($value)]], $this->separator);
    }

    public function append(Stringable|string|null $query): QueryInterface
    {
        if ($query instanceof UriComponentInterface) {
            $query = $query->value();
        }

        $pairs = array_merge($this->pairs, QueryString::parse($query, $this->separator));
        if ($pairs === $this->pairs) {
            return $this;
        }

        return self::fromPairs(array_filter($pairs, $this->filterEmptyValue(...)), $this->separator);
    }

    /**
     * Empty Pair filtering.
     */
    private function filterEmptyValue(array $pair): bool
    {
        return '' !== $pair[0] || null !== $pair[1];
    }

    public function withoutParameters(string ...$names): QueryInterface
    {
        if ([] === $names) {
            return $this;
        }

        $mapper = static fn (string $offset): string => preg_quote($offset, ',').'(\[.*\].*)?';
        $regexp = ',^('.implode('|', array_map($mapper, $names)).')?$,';
        $filter = fn (array $pair): bool => 1 !== preg_match($regexp, $pair[0]);

        $pairs = array_filter($this->pairs, $filter);
        if ($pairs === $this->pairs) {
            return $this;
        }

        return self::fromPairs($pairs, $this->separator);
    }

    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @see Query::fromParameters()
     *
     * @codeCoverageIgnore
     *
     * @param non-empty-string $separator
     *
     * Returns a new instance from the result of PHP's parse_str.
     *
     * @deprecated Since version 7.0.0
     */
    public static function createFromParams(iterable|object $params, string $separator = '&'): self
    {
        if (!is_iterable($params)) {
            return new self(
                http_build_query($params, '', $separator, PHP_QUERY_RFC3986),
                $separator,
                PHP_QUERY_RFC3986
            );
        }

        return self::fromParameters($params, $separator);
    }

    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @see Query::fromPairs()
     *
     * @codeCoverageIgnore
     *
     *
     * Returns a new instance from the result of QueryString::parse.
     *
     * @param iterable<int, array{0:string, 1:string|null}> $pairs
     * @param non-empty-string                              $separator
     */
    public static function createFromPairs(iterable $pairs, string $separator = '&'): self
    {
        return self::fromPairs($pairs, $separator);
    }

    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @see Query::fromUri()
     *
     * @codeCoverageIgnore
     *
     * Create a new instance from a URI object.
     */
    public static function createFromUri(Psr7UriInterface|UriInterface $uri): self
    {
        return self::fromUri($uri);
    }

    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @see Query::fromRFC3986()
     *
     * @codeCoverageIgnore
     *
     * Returns a new instance.
     *
     * @param non-empty-string $separator
     */
    public static function createFromRFC3986(Stringable|string|int|null $query = '', string $separator = '&'): self
    {
        if (null !== $query) {
            $query = (string) $query;
        }

        return self::fromRFC3986($query, $separator);
    }

    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @see Query::fromRFC1738()
     *
     * @codeCoverageIgnore
     *
     * Returns a new instance.
     *
     * @param non-empty-string $separator
     */
    public static function createFromRFC1738(Stringable|string|int|null $query = '', string $separator = '&'): self
    {
        if (is_int($query)) {
            $query = (string) $query;
        }

        return self::fromRFC1738($query, $separator);
    }

    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @see Query::parameters()
     * @see Query::parameter()
     *
     * @codeCoverageIgnore
     *
     * Returns the query as a collection of PHP variables or a single variable assign to a specific key
     */
    public function params(?string $key = null): mixed
    {
        return match (true) {
            null === $key => $this->parameters(),
            default => $this->parameter($key),
        };
    }

    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @see Query::withoutParameters()
     *
     * @codeCoverageIgnore
     */
    public function withoutParams(string ...$names): QueryInterface
    {
        return $this->withoutParameters(...$names);
    }

    /**
     * DEPRECATION WARNING! This method will be removed in the next major point release.
     *
     * @deprecated Since version 7.0.0
     * @see Query::value()
     *
     * @codeCoverageIgnore
     */
    public function toRFC3986(): ?string
    {
        return $this->value();
    }
}
