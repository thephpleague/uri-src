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
     * Use current http_build_query algorithm.
     */
    case Native;

    /**
     * Strictly uses get_object_vars on objects (Enum included)
     * if the value can not be serialized the entry is skipped.
     *
     * ie http_build_query behavior before PHP8.4
     */
    case Compatible;

    /**
     * Mimic PHP8.4+ http_build_query behavior
     * with support for Enum
     */
    case EnumNative;

    /**
     * Validation-first mode.
     *
     * Guarantees that only scalar values and null are accepted.
     * Any object, enum, resource, or recursive structure
     * results in an exception.
     *
     * This contract is stable and independent of PHP's
     * http_build_query implementation.
     */
    case Strict;
}
