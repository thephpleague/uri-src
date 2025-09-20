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

if (PHP_VERSION_ID < 80500) {
    /**
     * This is a user-land polyfill to the native Uri\WhatWg\UrlValidationError class included in PHP8.5.
     *
     * @see https://wiki.php.net/rfc/url_parsing_api
     */
    final class UrlValidationError
    {
        public function __construct(
            public readonly string $context,
            public readonly UrlValidationErrorType $type,
            public readonly bool $failure
        ) {
        }
    }
}
