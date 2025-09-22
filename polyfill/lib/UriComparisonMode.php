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

namespace Uri;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80500) {
    /**
     * This is a user-land polyfill to the native Uri\UriComparisonMode Enum included in PHP8.5.
     *
     * @see https://wiki.php.net/rfc/url_parsing_api
     *
     * @phpstan-type UriDebugShape array{scheme: ?string, username: ?string, password: ?string, host: ?string, port: ?int, path: string, query: ?string, fragment: ?string}
     * @phpstan-type UriSerializedShape array{0: array{uri: string}, 1: array{}}
     */
    enum UriComparisonMode
    {
        case IncludeFragment;
        case ExcludeFragment;
    }
}
