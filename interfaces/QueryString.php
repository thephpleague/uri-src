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

namespace League\Uri;

use League\Uri\Contracts\UriComponentInterface;
use League\Uri\Exceptions\SyntaxError;
use Stringable;
use function array_key_exists;
use function array_keys;
use function explode;
use function html_entity_decode;
use function implode;
use function is_array;
use function is_bool;
use function is_numeric;
use function is_scalar;
use function is_string;
use function preg_match;
use function preg_quote;
use function preg_replace_callback;
use function rawurldecode;
use function rawurlencode;
use function sprintf;
use function str_replace;
use function strpos;
use function substr;
use const PHP_QUERY_RFC1738;
use const PHP_QUERY_RFC3986;

/**
 * A class to parse the URI query string.
 *
 * @see https://tools.ietf.org/html/rfc3986#section-3.4
 */
final class QueryString
{
    private const ENCODING_LIST = [
        PHP_QUERY_RFC1738 => [
            'suffixKey' => '*',
            'suffixValue' => '*&',
        ],
        PHP_QUERY_RFC3986 => [
            'suffixKey' => "!$'()*,;:@?/%",
            'suffixValue' => "!$'()*,;=:@?/&%",
        ],
    ];
    private const PAIR_VALUE_DECODED = 1;
    private const PAIR_VALUE_PRESERVED = 2;
    private const REGEXP_ENCODED_PATTERN = ',%[A-Fa-f0-9]{2},';
    private const REGEXP_INVALID_CHARS = '/[\x00-\x1f\x7f]/';
    private const REGEXP_UNRESERVED_CHAR = '/[^A-Za-z0-9_\-.~]/';

    private static string $regexpKey;
    private static string $regexpValue;

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Parses the query string like parse_str without mangling the results.
     *
     * The result is similar as PHP parse_str when used with its
     * second argument with the difference that variable names are
     * not mangled.
     *
     * @see http://php.net/parse_str
     * @see https://wiki.php.net/rfc/on_demand_name_mangling
     *
     * @throws SyntaxError
     */
    public static function extract(Stringable|string|bool|null $query, string $separator = '&', int $encType = PHP_QUERY_RFC3986): array
    {
        $query = self::filterQuery($query, $encType);

        return self::convert(match (true) {
            '' === $separator => throw new SyntaxError('The separator character can not be the empty string.'),
            null === $query,
            '' === $query => [],
            default => self::parsePairs($query, $separator, self::PAIR_VALUE_PRESERVED),
        });
    }

    /**
     * Parses a query string into a collection of key/value pairs.
     *
     * @throws SyntaxError
     *
     * @return array<int, array{0:string, 1:string|null}>
     */
    public static function parse(Stringable|string|bool|null $query, string $separator = '&', int $encType = PHP_QUERY_RFC3986): array
    {
        $query = self::filterQuery($query, $encType);

        return match (true) {
            '' === $separator => throw new SyntaxError('The separator character can not be the empty string.'),
            null === $query => [],
            '' === $query => [['', null]],
            default => self::parsePairs($query, $separator, self::PAIR_VALUE_DECODED),
        };
    }

    private static function filterQuery(Stringable|string|bool|null $query, int $encType): ?string
    {
        $query = match (true) {
            !isset(self::ENCODING_LIST[$encType]) => throw new SyntaxError('Unknown or Unsupported encoding.'),
            $query instanceof UriComponentInterface => $query->value(),
            $query instanceof Stringable => (string) $query,
            default => $query,
        };

        return match (true) {
            null === $query => null,
            false === $query => '0',
            true === $query => '1',
            1 === preg_match(self::REGEXP_INVALID_CHARS, $query) => throw new SyntaxError(sprintf('Invalid query string: %s.', $query)),
            PHP_QUERY_RFC1738 === $encType => str_replace('+', '%20', $query),
            default => $query,
        };
    }

    /**
     * @param non-empty-string $query
     * @param non-empty-string $separator
     *
     * @return array<int, array{0:string, 1:string|null}>
     */
    private static function parsePairs(string $query, string $separator, int $pairValueState): array
    {
        return array_reduce(
            match (true) {
                str_contains($query, $separator) => explode($separator, $query),
                default => [$query],
            },
            fn (array $carry, string|null $pairString) => [...$carry, self::parsePair((string) $pairString, $pairValueState)],
            []
        );
    }

    /**
     * Returns the key/value pair from a query string pair.
     *
     * @return array{0:string, 1:string|null}
     */
    private static function parsePair(string $pair, int $pairValueState): array
    {
        $decodeMatches = static fn (array $matches): string => rawurldecode($matches[0]);

        [$key, $value] = explode('=', $pair, 2) + [1 => null];

        $key = match (true) {
            null === $key => '',
            1 !== preg_match(self::REGEXP_ENCODED_PATTERN, $key) => $key,
            default => (string) preg_replace_callback(self::REGEXP_ENCODED_PATTERN, $decodeMatches(...), $key),
        };

        return match (true) {
            null === $value,
            self::PAIR_VALUE_PRESERVED === $pairValueState,
            1 !== preg_match(self::REGEXP_ENCODED_PATTERN, $value) => [$key, $value],
            default => [
                $key,
                preg_replace_callback(self::REGEXP_ENCODED_PATTERN, $decodeMatches(...), $value),
            ],
        };
    }

    /**
     * Converts a collection of key/value pairs and returns
     * the store PHP variables as elements of an array.
     */
    public static function convert(iterable $pairs): array
    {
        $returnedValue = [];
        foreach ($pairs as $pair) {
            $returnedValue = self::extractPhpVariable($returnedValue, $pair);
        }

        return $returnedValue;
    }

    /**
     * Parses a query pair like parse_str without mangling the results array keys.
     *
     * <ul>
     * <li>empty name are not saved</li>
     * <li>If the value from name is duplicated its corresponding value will be overwritten</li>
     * <li>if no "[" is detected the value is added to the return array with the name as index</li>
     * <li>if no "]" is detected after detecting a "[" the value is added to the return array with the name as index</li>
     * <li>if there's a mismatch in bracket usage the remaining part is dropped</li>
     * <li>“.” and “ ” are not converted to “_”</li>
     * <li>If there is no “]”, then the first “[” is not converted to becomes an “_”</li>
     * <li>no whitespace trimming is done on the key value</li>
     * </ul>
     *
     * @see https://php.net/parse_str
     * @see https://wiki.php.net/rfc/on_demand_name_mangling
     * @see https://github.com/php/php-src/blob/master/ext/standard/tests/strings/parse_str_basic1.phpt
     * @see https://github.com/php/php-src/blob/master/ext/standard/tests/strings/parse_str_basic2.phpt
     * @see https://github.com/php/php-src/blob/master/ext/standard/tests/strings/parse_str_basic3.phpt
     * @see https://github.com/php/php-src/blob/master/ext/standard/tests/strings/parse_str_basic4.phpt
     *
     * @param array        $data  the submitted array
     * @param array|string $name  the pair key
     * @param string       $value the pair value
     */
    private static function extractPhpVariable(array $data, array|string $name, string $value = ''): array
    {
        if (is_array($name)) {
            [$name, $value] = $name;
            $value = rawurldecode((string) $value);
        }

        if ('' === $name) {
            return $data;
        }

        $leftBracketPosition = strpos($name, '[');
        if (false === $leftBracketPosition) {
            $data[$name] = $value;

            return $data;
        }

        $rightBracketPosition = strpos($name, ']', $leftBracketPosition);
        if (false === $rightBracketPosition) {
            $data[$name] = $value;

            return $data;
        }

        $key = substr($name, 0, $leftBracketPosition);
        if (!array_key_exists($key, $data) || !is_array($data[$key])) {
            $data[$key] = [];
        }

        $index = substr($name, $leftBracketPosition + 1, $rightBracketPosition - $leftBracketPosition - 1);
        if ('' === $index) {
            $data[$key][] = $value;

            return $data;
        }

        $remaining = substr($name, $rightBracketPosition + 1);
        if (!str_starts_with($remaining, '[') || false === strpos($remaining, ']', 1)) {
            $remaining = '';
        }

        $data[$key] = self::extractPhpVariable($data[$key], $index.$remaining, $value);

        return $data;
    }

    /**
     * Build a query string from an associative array.
     *
     * The method expects the return value from Query::parse to build
     * a valid query string. This method differs from PHP http_build_query as
     * it does not modify parameters keys.
     *
     * @param array<array{0:string, 1:string|float|int|bool|null}> $pairs
     *
     * @throws SyntaxError If the encoding type is invalid
     * @throws SyntaxError If a pair is invalid
     */
    public static function build(iterable $pairs, string $separator = '&', int $encType = PHP_QUERY_RFC3986): ?string
    {
        $res = match (true) {
            '' === $separator => throw new SyntaxError('The separator character can not be the empty string.'),
            !isset(self::ENCODING_LIST[$encType]) => throw new SyntaxError('Unknown or Unsupported encoding.'),
            default => [],
        };

        self::$regexpValue = '/(%[A-Fa-f0-9]{2})|[^A-Za-z0-9_\-\.~'.preg_quote(
            str_replace(
                html_entity_decode($separator, ENT_HTML5, 'UTF-8'),
                '',
                self::ENCODING_LIST[$encType]['suffixValue']
            ),
            '/'
        ).']+/ux';

        self::$regexpKey = '/(%[A-Fa-f0-9]{2})|[^A-Za-z0-9_\-\.~'.preg_quote(
            str_replace(
                html_entity_decode($separator, ENT_HTML5, 'UTF-8'),
                '',
                self::ENCODING_LIST[$encType]['suffixKey']
            ),
            '/'
        ).']+/ux';

        foreach ($pairs as $pair) {
            $res[] = self::buildPair($pair);
        }

        return match (true) {
            [] === $res => null,
            PHP_QUERY_RFC1738 === $encType => str_replace('%20', '+', implode($separator, $res)),
            default => implode($separator, $res),
        };
    }

    /**
     * Build a RFC3986 query key/value pair association.
     *
     * @throws SyntaxError If the pair is invalid
     */
    private static function buildPair(array $pair): string
    {
        if ([0, 1] !== array_keys($pair)) {
            throw new SyntaxError('A pair must be a sequential array starting at `0` and containing two elements.');
        }

        [$name, $value] = $pair;

        $encodeMatches = static fn (array $matches): string => match (true) {
            1 === preg_match(self::REGEXP_UNRESERVED_CHAR, rawurldecode($matches[0])) => rawurlencode($matches[0]),
            default => $matches[0],
        };

        $formatter = static fn (string $value, string $regexp): string => match (true) {
            1 === preg_match('/[\x00-\x1f\x7f]/', $value) => rawurlencode($value),
            1 === preg_match($regexp, $value) => (string) preg_replace_callback($regexp, $encodeMatches(...), $value),
            default => $value,
        };

        $name = match (true) {
            !is_scalar($name) => throw new SyntaxError(sprintf('A pair key must be a scalar value `%s` given.', gettype($name))),
            is_bool($name) => (int) $name,
            is_string($name) => $formatter($name, self::$regexpKey),
            default => $name,
        };

        return match (true) {
            is_string($value) => $name.'='.$formatter($value, self::$regexpValue),
            is_numeric($value) => $name.'='.$value,
            is_bool($value) => $name.'='.(int) $value,
            null === $value => (string) $name,
            default => throw new SyntaxError(sprintf('A pair value must be a scalar value or the null value, `%s` given.', gettype($value))),
        };
    }
}
