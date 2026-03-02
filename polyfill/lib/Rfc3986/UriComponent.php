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

namespace Uri\Rfc3986;

use League\Uri\HostRecord;
use League\Uri\UriString;
use Uri\InvalidUriException;

use function preg_match;
use function preg_quote;
use function preg_replace_callback;
use function rawurldecode;
use function rawurlencode;
use function str_contains;
use function str_replace;
use function strtolower;
use function strtoupper;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80600) {
    enum UriComponent
    {
        case UserInfo;
        case Host;
        case AbsolutePathReferenceFirstSegment;
        case RelativePathReferenceFirstSegment;
        case Path;
        case PathSegment;
        case Query;
        case FormQuery;
        case Fragment;
        case AllReservedCharacters;
        case AllButUnreservedCharacters;

        private const REGEXP_PART_UNRESERVED = 'A-Za-z0-9\-._~';
        private const REGEXP_PART_SUBDELIM = '!\$&\'\(\)\*\+,;=';
        private const GEN_DELIMS = ':/?#[]@';

        /**
         * @throws InvalidUriException
         */
        public function encode(string $input): string
        {
            match ($this) {
                self::AbsolutePathReferenceFirstSegment => !str_starts_with($input, '//') || throw new InvalidUriException('Absolute-path first segment must not start with "//".'),
                self::RelativePathReferenceFirstSegment => !str_contains($input, ':') || throw new InvalidUriException('Relative-path first segment must not contain ":".'),
                self::Host => HostRecord::isValid($input) || throw new InvalidUriException('Host must not contain invalid characters.'),
                default => null,
            };

            return match ($this) {
                self::FormQuery => str_replace('%20', '+', $this->encodeComponent($input)),
                self::Host => HostRecord::isIp($input) ? $input : $this->encodeComponent(strtolower($input)),
                default => $this->encodeComponent($input),
            };
        }

        /**
         * @throws InvalidUriException
         */
        public function decode(string $input): string
        {
            UriString::containsRfc3986Chars($input) || throw new InvalidUriException('the input string contains invalid characters.');

            return match ($this) {
                self::FormQuery => $this->decodeComponent(str_replace('+', ' ', $input)),
                self::Host => HostRecord::isIp($input) ? $input : $this->decodeComponent($input),
                default => $this->decodeComponent($input),
            };
        }

        private function encodeComponent(string $component): string
        {
            $encodeAllowedChars = self::REGEXP_PART_UNRESERVED
                .self::REGEXP_PART_SUBDELIM
                .preg_quote(match ($this) {
                    self::UserInfo => ':',
                    self::AbsolutePathReferenceFirstSegment => ':@',
                    self::RelativePathReferenceFirstSegment => '@',
                    self::Path => '/:@',
                    self::Query,
                    self::FormQuery => '/?:@&=',
                    self::Fragment => '/?:@&=#',
                    default => '',
                }, '/');
            $pattern = match ($this) {
                self::AllReservedCharacters => '/['.preg_quote(self::GEN_DELIMS, '/').self::REGEXP_PART_SUBDELIM.']/',
                self::AllButUnreservedCharacters => '/(?:%[0-9A-Fa-f]{2}|[^'.self::REGEXP_PART_UNRESERVED.'])/u',
                default => '/%(?![0-9A-Fa-f]{2})|[^'.$encodeAllowedChars.'%]+/',
            };

            return (string) preg_replace_callback(
                pattern: '/%[0-9a-f]{2}/',
                callback: static fn (array $matches): string => strtoupper($matches[0]),
                subject: (string) preg_replace_callback(
                    pattern: $pattern,
                    callback: static fn (array $matches): string => rawurlencode($matches[0]),
                    subject: $component
                )
            );
        }

        private function decodeComponent(string $component): string
        {
            $decodeAllowedChars = match ($this) {
                self::Path,
                self::PathSegment,
                self::AbsolutePathReferenceFirstSegment,
                self::RelativePathReferenceFirstSegment => '/:',
                self::Query,
                self::FormQuery => '/?',
                self::Fragment => '/?#',
                self::AllReservedCharacters => self::GEN_DELIMS.self::REGEXP_PART_SUBDELIM,
                default => '',
            };
            $decodeAll = self::Host === $this;
            $decoder = function (array $matches) use ($decodeAllowedChars, $decodeAll): string {
                $encoded = strtoupper($matches[0]);
                $decoded = rawurldecode($encoded);

                return match (true) {
                    $decodeAll => $decoded,
                    1 === preg_match('/['.self::REGEXP_PART_UNRESERVED.']/', $decoded) => $encoded,
                    str_contains($decodeAllowedChars, $decoded) => $decoded,
                    default => $encoded,
                };
            };

            return (string) preg_replace_callback(pattern: '/%[0-9A-Fa-f]{2}/', callback: $decoder, subject: $component);
        }
    }
}
