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

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80600) {
    /**
     * This is a user-land polyfill to the native Uri\Rfc3986\UriType Enum included in PHP8.6.
     *
     * @see https://wiki.php.net/rfc/uri_followup#uri_type_detection
     */
    enum UriType
    {
        case AbsolutePathReference;
        case RelativePathReference;
        case NetworkPathReference;
        case Uri;
    }
}
