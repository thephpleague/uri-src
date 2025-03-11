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

use Closure;
use League\Uri\Contracts\UriComponentInterface;
use League\Uri\Exceptions\SyntaxError;
use SensitiveParameter;
use Stringable;

use function gettype;
use function in_array;
use function is_scalar;
use function preg_match;
use function preg_replace_callback;
use function rawurldecode;
use function rawurlencode;
use function sprintf;
use function strtoupper;

final class Encoder
{
    private const REGEXP_CHARS_INVALID = '/[\x00-\x1f\x7f]/';
    private const REGEXP_CHARS_ENCODED = ',%[A-Fa-f0-9]{2},';
    private const REGEXP_CHARS_PREVENTS_DECODING = ',%
     	2[A-F|1-2|4-9]|
        3[0-9|B|D]|
        4[1-9|A-F]|
        5[0-9|A|F]|
        6[1-9|A-F]|
        7[0-9|E]
    ,ix';
    private const REGEXP_PART_SUBDELIM = "\!\$&'\(\)\*\+,;\=%";
    private const REGEXP_PART_UNRESERVED = 'A-Za-z\d_\-.~';
    private const REGEXP_PART_ENCODED = '%(?![A-Fa-f\d]{2})';

    /**
     * Unreserved characters.
     *
     * @see https://www.rfc-editor.org/rfc/rfc3986.html#section-2.3
     */
    private const REGEXP_UNRESERVED_CHARACTERS = ',%(2[1-9A-Fa-f]|[3-7][0-9A-Fa-f]|61|62|64|65|66|7[AB]|5F),';

    /**
     * Encode User.
     *
     * All generic delimiters MUST be encoded
     */
    public static function encodeUser(Stringable|string|null $component): ?string
    {
        static $pattern = '/[^'.self::REGEXP_PART_UNRESERVED.self::REGEXP_PART_SUBDELIM.']+|'.self::REGEXP_PART_ENCODED.'/';

        return self::encode($component, $pattern);
    }

    /**
     * Encode Password.
     *
     * Generic delimiters ":" MUST NOT be encoded
     */
    public static function encodePassword(#[SensitiveParameter] Stringable|string|null $component): ?string
    {
        static $pattern = '/[^'.self::REGEXP_PART_UNRESERVED.self::REGEXP_PART_SUBDELIM.':]+|'.self::REGEXP_PART_ENCODED.'/';

        return self::encode($component, $pattern);
    }

    /**
     * Encode Path.
     *
     * Generic delimiters ":", "@", and "/" MUST NOT be encoded
     */
    public static function encodePath(Stringable|string|null $component): string
    {
        static $pattern = '/[^'.self::REGEXP_PART_UNRESERVED.self::REGEXP_PART_SUBDELIM.':@\/]+|'.self::REGEXP_PART_ENCODED.'/';

        return (string) self::encode($component, $pattern);
    }

    /**
     * Encode Query or Fragment.
     *
     * Generic delimiters ":", "@", "?", and "/" MUST NOT be encoded
     */
    public static function encodeQueryOrFragment(Stringable|string|null $component): ?string
    {
        static $pattern = '/[^'.self::REGEXP_PART_UNRESERVED.self::REGEXP_PART_SUBDELIM.':@\/?]+|'.self::REGEXP_PART_ENCODED.'/';

        return self::encode($component, $pattern);
    }

    public static function encodeQueryKeyValue(mixed $component): ?string
    {
        static $pattern = '/[^'.self::REGEXP_PART_UNRESERVED.']+|'.self::REGEXP_PART_ENCODED.'/';
        $encoder = static fn (array $found): string => 1 === preg_match('/[^'.self::REGEXP_PART_UNRESERVED.']/', rawurldecode($found[0])) ? rawurlencode($found[0]) : $found[0];
        $filteredComponent = self::filterComponent($component);

        return match (true) {
            null === $filteredComponent => throw new SyntaxError(sprintf('A pair key/value must be a scalar value `%s` given.', gettype($component))),
            1 === preg_match(self::REGEXP_CHARS_INVALID, $filteredComponent) => rawurlencode($filteredComponent),
            default => (string) preg_replace_callback($pattern, $encoder, $filteredComponent),
        };
    }

    /**
     * Decodes all the URI component characters.
     */
    public static function decodeAll(Stringable|string|null $component): ?string
    {
        return self::decode($component, static fn (array $matches): string => rawurldecode($matches[0]));
    }

    /**
     * Decodes the URI component without decoding the unreserved characters which are already encoded.
     */
    public static function decodePartial(Stringable|string|int|null $component): ?string
    {
        $decoder = static function (array $matches): string {
            if (1 === preg_match(self::REGEXP_CHARS_PREVENTS_DECODING, $matches[0])) {
                return strtoupper($matches[0]);
            }

            return rawurldecode($matches[0]);
        };

        return self::decode($component, $decoder);
    }

    /**
     * Decodes the component unreserved characters.
     */
    public static function decodeUnreservedCharacters(Stringable|string|null $str): ?string
    {
        $str = self::filterComponent($str);
        if (null === $str || '' === $str) {
            return $str;
        }

        return preg_replace_callback(self::REGEXP_UNRESERVED_CHARACTERS, static fn (array $matches): string => rawurldecode($matches[0]), $str) ?? '';
    }

    /**
     * Decodes the path component while preserving characters that should not be decoded in the context of a full valid URI.
     */
    public static function decodePath(Stringable|string|null $path): ?string
    {
        $decoder = static function (array $matches): string {
            $encodedChar = strtoupper($matches[0]);

            return in_array($encodedChar, ['%2F', '%20', '%3F', '%23'], true) ? $encodedChar : rawurldecode($encodedChar);
        };

        return self::decode($path, $decoder);
    }

    /**
     * Decodes the query component while preserving characters that should not be decoded in the context of a full valid URI.
     */
    public static function decodeQuery(Stringable|string|null $path): ?string
    {
        $decoder = static function (array $matches): string {
            $encodedChar = strtoupper($matches[0]);

            return in_array($encodedChar, ['%26', '%3D', '%20', '%23'], true) ? $encodedChar : rawurldecode($encodedChar);
        };

        return self::decode($path, $decoder);
    }

    /**
     * Decodes the fragment component while preserving characters that should not be decoded in the context of a full valid URI.
     */
    public static function decodeFragment(Stringable|string|null $path): ?string
    {
        return self::decode($path, static fn (array $matches): string => '%20' === $matches[0] ? $matches[0] : rawurldecode($matches[0]));
    }

    private static function filterComponent(mixed $component): ?string
    {
        return match (true) {
            true === $component => '1',
            false === $component => '0',
            $component instanceof UriComponentInterface => $component->value(),
            $component instanceof Stringable,
            is_scalar($component) => (string) $component,
            null === $component => null,
            default => throw new SyntaxError(sprintf('The component must be a scalar value `%s` given.', gettype($component))),
        };
    }

    /**
     * Encodes the URI component characters using a regular expression to find which characters need encoding.
     */
    private static function encode(Stringable|string|int|bool|null $component, string $pattern): ?string
    {
        $component = self::filterComponent($component);
        if (null === $component || '' === $component) {
            return $component;
        }

        return (string) preg_replace_callback(
            $pattern,
            static fn (array $found): string => 1 === preg_match('/[^'.self::REGEXP_PART_UNRESERVED.']/', rawurldecode($found[0])) ? rawurlencode($found[0]) : $found[0],
            $component
        );
    }

    /**
     * Decodes the URI component characters using a closure.
     */
    private static function decode(Stringable|string|int|null $component, Closure $decoder): ?string
    {
        $component = self::filterComponent($component);
        if (null === $component || '' === $component) {
            return $component;
        }

        if (1 === preg_match(self::REGEXP_CHARS_INVALID, $component)) {
            throw new SyntaxError('Invalid component string: '.$component.'.');
        }

        if (1 === preg_match(self::REGEXP_CHARS_ENCODED, $component)) {
            return (string) preg_replace_callback(self::REGEXP_CHARS_ENCODED, $decoder, $component);
        }

        return $component;
    }
}
