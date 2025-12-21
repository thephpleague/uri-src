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
     * Pre-PHP 8.4 Mode.
     *
     * Strictly uses get_object_vars on objects (Enum included)
     * If the value can not be serialized the entry is skipped.
     *
     * ie http_build_query behavior before PHP8.4
     */
    case Compatible;

    /**
     * Transitional compatibility mode.
     *
     * Provides stable support for BackedEnum values.
     * Throws for UnitEnum.
     * Uses get_object_vars() for non-enum objects.
     * Unserializable values are skipped.
     *
     * Approximates http_build_query behavior in PHP 8.4+.
     *
     * Non-enum behavior may evolve in future versions.
     */
    case EnumCompatible;

    /**
     * Use PHP version http_build_query algorithm.
     */
    case Native;

    /**
     * Validation-first mode.
     *
     * Guarantees that only scalar values, BackedEnum, and null are accepted.
     * Any object, UnitEnum, resource, or recursive structure
     * results in an exception.
     *
     * This contract is stable and independent of PHP's
     * http_build_query implementation.
     */
    case ValueOnly;
}
