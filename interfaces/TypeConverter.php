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

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Stringable;
use UnitEnum;

use function date_create_immutable_from_format;
use function date_get_last_errors;
use function enum_exists;
use function filter_var;
use function is_bool;
use function is_float;
use function is_int;
use function is_iterable;
use function is_scalar;
use function is_string;
use function trim;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const FILTER_VALIDATE_FLOAT;
use const FILTER_VALIDATE_INT;

final class TypeConverter
{
    public static function toString(mixed $value): ?string
    {
        return !is_scalar($value) && !$value instanceof Stringable ? null : (string) $value;
    }

    /**
     * @return array<string>
     */
    public static function toStrings(mixed $values, ?string $default = null): array
    {
        if (!is_iterable($values)) {
            return [];
        }

        $arr = [];
        foreach ($values as $index => $value) {
            $item = self::toString($value) ?? $default;
            if (null === $item) {
                return [];
            }

            $arr[$index] = $item;
        }

        return $arr;
    }

    public static function toInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        return (is_string($value) && false !== ($res = filter_var($value, FILTER_VALIDATE_INT))) ? $res : null;
    }

    /**
     * @return array<int>
     */
    public static function toIntegers(mixed $values, ?int $default = null): array
    {
        if (!is_iterable($values)) {
            return [];
        }

        $arr = [];
        foreach ($values as $index => $value) {
            $item = self::toInteger($value) ?? $default;
            if (null === $item) {
                return [];
            }

            $arr[$index] = $item;
        }

        return $arr;
    }

    public static function toFloat(mixed $value): ?float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (float) $value;
        }

        return is_string($value) && false !== ($res = filter_var($value, FILTER_VALIDATE_FLOAT)) ? $res : null;
    }

    /**
     * @return array<float>
     */
    public static function toFloats(mixed $values, ?float $default = null): array
    {
        if (!is_iterable($values)) {
            return [];
        }

        $arr = [];
        foreach ($values as $index => $value) {
            $item = self::toFloat($value) ?? $default;
            if (null === $item) {
                return [];
            }

            $arr[$index] = $item;
        }

        return $arr;
    }

    public static function toBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }

    /**
     * @return array<bool>
     */
    public static function toBooleans(mixed $values, ?bool $default = null): array
    {
        if (!is_iterable($values)) {
            return [];
        }

        $arr = [];
        foreach ($values as $index => $value) {
            $item = self::toBoolean($value) ?? $default;
            if (null === $item) {
                return [];
            }

            $arr[$index] = $item;
        }

        return $arr;
    }

    /**
     * @param class-string<UnitEnum> $enumClass
     */
    public static function toEnum(mixed $value, string $enumClass): ?UnitEnum
    {
        if (!enum_exists($enumClass)) {
            return null;
        }

        if ($value instanceof $enumClass) {
            return $value;
        }

        if (!is_string($value) && !is_int($value)) {
            return null;
        }

        $intValue = is_int($value) ? $value : self::toInteger($value);
        $value = (string) $value;
        foreach ($enumClass::cases() as $case) {
            if ($case instanceof BackedEnum) {
                if ($case->value === $intValue || $case->value === $value) {
                    return $case;
                }
                continue;
            }

            if ($case->name === $value) {
                return $case;
            }
        }

        return null;
    }

    /**
     * @param class-string<UnitEnum> $enumClass
     *
     * @return array<UnitEnum>
     */
    public static function toEnums(mixed $values, string $enumClass, ?UnitEnum $default = null): array
    {
        if (!is_iterable($values)) {
            return [];
        }

        $arr = [];
        foreach ($values as $index => $value) {
            $item = self::toEnum($value, $enumClass) ?? $default;
            if (null === $item) {
                return [];
            }

            $arr[$index] = $item;
        }

        return $arr;
    }

    public static function toDateTimeImmutable(mixed $value, string $format, DateTimeZone|string|null $timezone = null): ?DateTimeImmutable
    {
        if (!is_string($value) || str_contains($value, "\0") || '' === ($format = trim($format))) {
            return null;
        }

        if (!$timezone instanceof DateTimeZone) {
            try {
                $timezone = new DateTimeZone($timezone ?? 'UTC');
            } catch (Exception) {
                return null;
            }
        }

        $date = date_create_immutable_from_format($format, $value, $timezone);
        $errors = date_get_last_errors();

        return false !== $date
        && (false === $errors || (0 === $errors['error_count'] && 0 === $errors['warning_count'])) ? $date : null;
    }

    /**
     * @return array<DateTimeImmutable>
     */
    public static function toDateTimeImmutables(
        mixed $values,
        string $format,
        DateTimeZone|string|null $timezone = null,
        DateTimeInterface|null $default = null,
    ): array {
        if (!is_iterable($values)) {
            return [];
        }

        if (null !== $default) {
            if (!$default instanceof DateTimeImmutable) {
                $default = DateTimeImmutable::createFromInterface($default);
            }
        }

        $arr = [];
        foreach ($values as $index => $value) {
            $item = self::toDateTimeImmutable($value, $format, $timezone) ?? $default;
            if (null === $item) {
                return [];
            }

            $arr[$index] = $item;
        }

        return $arr;
    }
}
