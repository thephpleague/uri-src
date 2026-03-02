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

use function ord;
use function rawurldecode;
use function str_contains;
use function str_replace;
use function strlen;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80600) {
    enum UrlComponent
    {
        case UserInfo;
        case OpaqueHost;
        case Path;
        case PathSegment;
        case OpaquePathSegment;
        case Query;
        case SpecialQuery;
        case FormQuery;
        case Fragment;

        private const HEX = '0123456789ABCDEF';

        public function encode(string $input): string
        {
            $result = '';
            $length = strlen($input);
            $set = match ($this) {
                self::Query => ' "#<>',
                self::SpecialQuery => " \"#<>'",
                self::Path,
                self::OpaquePathSegment => ' "#<>?^`{}',
                self::PathSegment => ' "#<>?^`{}/',
                self::UserInfo => ' "#<>?^`{}/:;=@[]|',
                self::FormQuery => ' "#<>?^`{}/:;=@[]|$%&+,!\'()~',
                self::Fragment => ' "<>`',
                self::OpaqueHost => '',
            };

            for ($i = 0; $i < $length; $i++) {
                $char = $input[$i];
                $ord = ord($char);
                $result .= (0x1F >= $ord || 0x7F === $ord || 0x7E < $ord || str_contains($set, $char))
                    ? '%'.self::HEX[$ord >> 4].self::HEX[$ord & 0x0F]
                    : $char;
            }

            return self::FormQuery === $this
                ? str_replace('%20', '+', $result)
                : $result;
        }

        public function decode(string $input): string
        {
            return rawurldecode(
                self::FormQuery === $this
                    ? str_replace('+', ' ', $input)
                    : $input
            );
        }
    }
}
