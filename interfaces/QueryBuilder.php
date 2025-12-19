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
use League\Uri\KeyValuePair\Converter;
use ReflectionEnum;
use TypeError;
use UnitEnum;
use ValueError;

use function http_build_query;
use function is_array;
use function is_object;

final class QueryBuilder
{
    private const RECURSION_MARKER = "\0__RECURSION_INTERNAL_MARKER__\0";

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Build a query string from an object or an array like http_build_query without discarding values.
     * The method differs from http_build_query for the following behavior:
     *
     *  - if a resource is used, a TypeError is thrown.
     *  - if a recursion is detected a ValueError is thrown
     *  - the method preserves value with `null` value (http_build_query) skip the key.
     *  - the method does not handle prefix usage
     *
     * @param array<array-key, mixed> $data
     * @param non-empty-string $separator
     *
     * @throws TypeError if a resource is found it the input array
     * @throws ValueError if a recursion is detected
     */
    public static function build(
        array|object $data,
        string $separator = '&',
        int $encType = PHP_QUERY_RFC1738,
        QueryBuildingMode $queryBuildingMode = QueryBuildingMode::Default
    ): ?string {
        if (QueryBuildingMode::Default === $queryBuildingMode) {
            return http_build_query(data: $data, arg_separator: $separator, encoding_type: $encType);
        }

        $query = self::buildFromValue($data, Converter::fromEncodingType($encType)->withSeparator($separator), $queryBuildingMode);

        return QueryBuildingMode::Strict !== $queryBuildingMode ? (string) $query : $query;
    }

    public static function buildFromValue(
        array|object $data,
        ?Converter $converter = null,
        QueryBuildingMode $queryBuildingMode = QueryBuildingMode::Default,
    ): ?string {
        $converter ??= Converter::fromRFC3986();
        $separator = $converter->separator();
        if (QueryBuildingMode::Default === $queryBuildingMode) {
            return $converter->toValue(
                QueryString::parseFromValue(
                    http_build_query(data: $data, arg_separator: $separator),
                    $converter
                )
            );
        }

        if (QueryBuildingMode::Strict === $queryBuildingMode && !is_array($data)) {
            throw new ValueError('In conservative mode only arrays are supported.');
        }

        if (QueryBuildingMode::HandleEnums === $queryBuildingMode && $data instanceof UnitEnum) {
            $enumType = (new ReflectionEnum($data::class))->isBacked() ? 'Backed' : 'Pure';

            throw new TypeError('Argument #1 ($data) must not be an enum, '.$enumType.' given') ;
        }

        return QueryString::buildFromPairs(self::composeRecursive($queryBuildingMode, $data), $converter);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @throws TypeError if a resource is found it the input array
     *
     * @return iterable<array{0: array-key, 1: string|int|float|bool|null}>
     */
    private static function composeRecursive(
        QueryBuildingMode $queryBuildingMode,
        array|object $data,
        string|int $prefix = '',
        array $seenObjects = [],
    ): iterable {
        if (QueryBuildingMode::Strict === $queryBuildingMode && !is_array($data)) {
            throw new ValueError('In conservative mode only arrays are supported.');
        }

        if (is_object($data)) {
            $id = spl_object_id($data);
            if (isset($seenObjects[$id])) {
                if (QueryBuildingMode::Strict === $queryBuildingMode) {
                    throw new ValueError('composition failed; object recursion detected.');
                }

                return;
            }

            $seenObjects[$id] = true;
            $data = get_object_vars($data);
        }

        if (self::isRecursive($data)) {
            if (QueryBuildingMode::Strict === $queryBuildingMode) {
                throw new ValueError('composition failed; array recursion detected.');
            }

            return;
        }

        foreach ($data as $name => $value) {
            if ('' !== $prefix) {
                $name = $prefix.'['.$name.']';
            }

            if (QueryBuildingMode::Strict === $queryBuildingMode && (is_object($data) || is_resource($data))) {
                throw new ValueError('In conservative mode only arrays, scalar value or null are supported.');
            }

            if (is_resource($value)) {
                if (QueryBuildingMode::Strict === $queryBuildingMode) {
                    throw new TypeError('composition failed; a resource has been detected and can not be converted.');
                }
                continue;
            }

            if (null === $value || is_scalar($value)) {
                yield [$name, $value];

                continue;
            }

            if (QueryBuildingMode::HandleEnums === $queryBuildingMode) {
                if ($value instanceof UnitEnum) {
                    $value instanceof BackedEnum || throw new TypeError('Unbacked enum '.$value::class.' cannot be converted to a string');
                    yield [$name, $value->value];

                    continue;
                }
            }

            yield from self::composeRecursive($queryBuildingMode, $value, $name, $seenObjects);
        }
    }

    /**
     * Array recursion detection.
     * @see https://stackoverflow.com/questions/9042142/detecting-infinite-array-recursion-in-php
     */
    private static function isRecursive(array &$arr): bool
    {
        if (isset($arr[self::RECURSION_MARKER])) {
            return true;
        }

        try {
            $arr[self::RECURSION_MARKER] = true;
            foreach ($arr as $key => &$value) {
                if (self::RECURSION_MARKER !== $key && is_array($value) && self::isRecursive($value)) {
                    return true;
                }
            }

            return false;
        } finally {
            unset($arr[self::RECURSION_MARKER]);
        }
    }
}
