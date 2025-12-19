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

namespace League\Uri;

enum QueryBuildingMode
{
    /**
     * Use http_build_query algorithm.
     */
    case Default;

    /**
     * strictly uses get_object_vars on objects and Enum
     * if the value can not be serialized the entry is skipped.
     *
     * ie http_build_query behavior for PHP8.4-
     */
    case Legacy;

    /**
     * Mimic PHP8.4+ http_build_query behavior
     */
    case HandleEnums;

    /**
     * Disallow building with objects, Enum or resource throw TypeError
     * Recursions throws a ValueError
     * null values are kept but composed without the `=` separator.
     */
    case Strict;
}
