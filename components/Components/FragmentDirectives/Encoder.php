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

namespace League\Uri\Components\FragmentDirectives;

use BackedEnum;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\StringCoercionMode;
use Stringable;
use Throwable;

use function get_debug_type;
use function in_array;
use function preg_match;
use function preg_replace_callback;
use function rawurldecode;
use function rawurlencode;
use function str_replace;

final class Encoder
{
    private const REGEXP_CHARS_INVALID = '/[\x00-\x1f\x7f]/';
    private const REGEXP_CHARS_ENCODED = ',%[A-Fa-f0-9]{2},';
    private const REGEXP_PART_UNRESERVED = 'A-Za-z\d_\-.~';
    private const REGEXP_ENCODER = '/[^' . self::REGEXP_PART_UNRESERVED . '!$&\'()*+,;=%:@\/?]+|%(?![A-Fa-f0-9]{2})/';

    private function __construct()
    {
    }

    private static function normalizeInput(BackedEnum|Stringable|string|null $value): ?string
    {
        try {
            return StringCoercionMode::Native->coerce($value);
        } catch (Throwable $exception) {
            throw new SyntaxError('The component must be a scalar value `'.get_debug_type($value).'` given.', previous: $exception);
        }
    }

    public static function encode(BackedEnum|Stringable|string|null $value): ?string
    {
        $value = self::normalizeInput($value);
        if (null === $value) {
            return null;
        }

        if ('' === $value) {
            return $value;
        }

        $encoder = static fn (array $found): string => 1 === preg_match('/[^'.self::REGEXP_PART_UNRESERVED.']/', rawurldecode($found[0])) ? rawurlencode($found[0]) : $found[0];
        $encoded = (string) preg_replace_callback(self::REGEXP_ENCODER, $encoder, $value);

        return strtr($encoded, ['-' => '%2D', ',' => '%2C', '&' => '%26']);
    }

    public static function decode(BackedEnum|Stringable|string|null $value): ?string
    {
        $value = self::normalizeInput($value);
        if (null === $value) {
            return null;
        }

        if ('' === $value) {
            return $value;
        }

        1 !== preg_match(self::REGEXP_CHARS_INVALID, $value) || throw new SyntaxError('Invalid component string: '.$value.'.');

        $decoder = static fn (array $matches): string => in_array($matches[0], ['%20', '%2F'], true) ? $matches[0] : rawurldecode($matches[0]);
        $decoded = (string) preg_replace_callback(self::REGEXP_CHARS_ENCODED, $decoder, $value);

        return str_replace('%20', ' ', $decoded);
    }
}
