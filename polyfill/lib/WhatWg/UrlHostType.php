<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Uri\WhatWg;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80600) {
    enum UrlHostType
    {
        case IPv4;
        case IPv6;
        case Domain;
        case Opaque;
        case Empty;
    }
}
