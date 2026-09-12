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

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80600) {
    /**
     * This is a user-land polyfill to the native Uri\Rfc3986\HostType Enum included in PHP8.6.
     *
     * @see https://wiki.php.net/rfc/uri_followup#percent-encoding_support
     */
    enum UrlPercentEncodingMode
    {
        case Username;
        case Password;
        case OpaqueHost;
        case Path;
        case OpaquePath;
        case PathSegment;
        case Query;
        case SpecialQuery;
        case FormQuery;
        case Fragment;
    }
}
