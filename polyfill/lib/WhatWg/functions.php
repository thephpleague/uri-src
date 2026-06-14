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

namespace Uri\WhatWg;

use function array_fill;
use function array_key_exists;
use function function_exists;
use function ord;
use function str_replace;
use function str_split;
use function strlen;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID >= 80600) {
    return;
}

if (!function_exists('Uri\WhatWg\url_percent_encode')) {
    /**
     * This is a user-land polyfill to the native Uri\Rfc3986\HostType Enum included in PHP8.6.
     *
     * @see https://wiki.php.net/rfc/uri_followup#percent-encoding_support
     */
    function url_percent_encode(string $input, UrlPercentEncodingMode $mode): string
    {
        static $hex = '0123456789ABCDEF';
        /** @var array<key-of<UrlPercentEncodingMode>, list<bool>> $tables */
        static $tables = [];
        if (!array_key_exists($mode->name, $tables)) {
            $set = match ($mode) {
                UrlPercentEncodingMode::Query => ' "#<>',
                UrlPercentEncodingMode::SpecialQuery => " \"#<>'",
                UrlPercentEncodingMode::Path,
                UrlPercentEncodingMode::OpaquePathSegment => ' "#<>?^`{}',
                UrlPercentEncodingMode::PathSegment => ' "#<>?^`{}/',
                UrlPercentEncodingMode::UserInfo => ' "#<>?^`{}/:;=@[]|',
                UrlPercentEncodingMode::FormQuery => ' "#<>?^`{}/:;=@[]|$%&+,!\'()~',
                UrlPercentEncodingMode::Fragment => ' "<>`',
                UrlPercentEncodingMode::OpaqueHost => '',
            };

            $table = array_fill(0, 256, false);
            foreach (str_split($set) as $char) {
                $table[ord($char)] = true;
            }

            $tables[$mode->name] = $table;
        }

        $lookupTable = $tables[$mode->name];
        $result = '';
        $length = strlen($input);
        for ($i = 0; $i < $length; $i++) {
            $ord = ord($input[$i]);
            $result .= ($ord <= 0x1F || 0x7F === $ord || $ord > 0x7E || $lookupTable[$ord])
                ? '%'.$hex[$ord >> 4].$hex[$ord & 0x0F]
                : $input[$i];
        }

        return UrlPercentEncodingMode::FormQuery === $mode
            ? str_replace('%20', '+', $result)
            : $result;
    }
}
