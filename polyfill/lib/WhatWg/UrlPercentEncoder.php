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

use Rowbot\URL\Component\Host\NullHost;
use Rowbot\URL\Component\Host\StringHost;
use Rowbot\URL\Component\Scheme;
use Rowbot\URL\String\Utf8String;
use Rowbot\URL\URLRecord;
use \Rowbot\URL\URL as RowbotURL;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80600) {
    /**
     * This is a user-land polyfill to the native Uri\Rfc3986\LeadingSlashPolicy Enum included in PHP8.6.
     *
     * @see https://wiki.php.net/rfc/uri_followup#percent-encoding_and_decoding_support
     */
    enum UriPercentEncoder
    {
        case UserInfo;
        case Host;
        case OpaqueHost;
        case Path;
        case PathSegment;
        case OpaquePath;
        case OpaquePathSegment;
        case Query;
        case SpecialQuery;
        case FormQuery;
        case Fragment;

        public function encode(string $input): string
        {
            return match ($this) {
                self::UserInfo => $this->encodeUserInfo($input),
                self::Host, self::OpaqueHost => $this->encodeHost($input, $this),
                default => rawurlencode($input),
            };
        }

        public function decode(string $input): string
        {
            return $input;
        }

        private function encodeUserInfo(string $userInfo): string
        {
            $record = new URLRecord();
            $record->scheme = new Scheme('https');
            $record->host = new StringHost('localhost');
            $record->setUsername(Utf8String::fromUnsafe($userInfo));

            return (new RowbotURL($record->serializeURL()))->username;
        }

        private function encodeHost(string $host, self $encoder): string
        {
            $record = new URLRecord();
            $record->scheme = new Scheme($encoder === self::OpaqueHost ? 'foo' : 'https');
            $record->host = new StringHost($host);

            return (new RowbotURL($record->serializeURL()))->hostname;
        }
    }
}
