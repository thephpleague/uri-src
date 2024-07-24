<?php

declare(strict_types=1);

namespace League\Uri\IPv6;

use Stringable;
use ValueError;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

use function filter_var;
use function inet_pton;
use function implode;
use function str_split;
use function strtolower;
use function unpack;

final class Converter
{
    private const IPV4_MAPPED_PREFIX = '::ffff:';
    private const IPV6_6TO4_PREFIX = '2002:';

    /**
     * Significant 10 bits of IP to detect Zone ID regular expression pattern.
     *
     * @var string
     */
    private const HOST_ADDRESS_BLOCK = "\xfe\x80";

    public static function compressIp(string $ipAddress): string
    {
        if (false === filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new ValueError('The submitted IP is not a valid IPv6 address.');
        }

        $ipAddress = strtolower((string) inet_ntop((string) inet_pton($ipAddress)));
        if (str_starts_with($ipAddress, self::IPV4_MAPPED_PREFIX)) {
            return substr($ipAddress, 7);
        }

        if (!str_starts_with($ipAddress, self::IPV6_6TO4_PREFIX)) {
            return $ipAddress;
        }

        $hexPart = substr($ipAddress, 5, 9);
        $hexParts = explode(':', $hexPart);

        return (string) match (true) {
            count($hexParts) < 2 => $ipAddress,
            default => long2ip((int) hexdec($hexParts[0]) * 65536 + (int) hexdec($hexParts[1])),
        };
    }

    public static function expandIp(string $ipAddress): string
    {
        if (false !== filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipAddress =  self::IPV4_MAPPED_PREFIX.$ipAddress;
        }

        if (false === filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new ValueError('The submitted IP is not a valid IPv6 address.');
        }

        $hex = (array) unpack("H*hex", (string) inet_pton($ipAddress));

        return implode(':', str_split(strtolower($hex['hex'] ?? ''), 4));
    }

    public static function compress(Stringable|string|null $host): ?string
    {
        $components = self::parse($host);
        if (null === $components['ipAddress']) {
            return match ($host) {
                null => $host,
                default => (string) $host,
            };
        }

        $components['ipAddress'] = self::compressIp($components['ipAddress']);

        return self::build($components);
    }

    public static function expand(Stringable|string|null $host): ?string
    {
        $components = self::parse($host);
        if (null === $components['ipAddress']) {
            return match ($host) {
                null => $host,
                default => (string) $host,
            };
        }

        $components['ipAddress'] = self::expandIp($components['ipAddress']);

        return self::build($components);
    }

    private static function build(array $components): string
    {
        $components['ipAddress'] ??= null;
        $components['zoneIdentifier'] ??= null;

        if (null !== $components['ipAddress'] && false !== filter_var($components['ipAddress'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $components['ipAddress'];
        }

        return '['.$components['ipAddress'].match ($components['zoneIdentifier']) {
            null => '',
            default => '%'.$components['zoneIdentifier'],
        }.']';
    }

    /**]
     * @param Stringable|string|null $host
     *
     * @return array{ipAddress:string|null, zoneIdentifier:string|null}
     */
    private static function parse(Stringable|string|null $host): array
    {
        if ($host === null) {
            return ['ipAddress' => null, 'zoneIdentifier' => null];
        }

        $host = (string) $host;
        if ($host === '') {
            return ['ipAddress' => null, 'zoneIdentifier' => null];
        }

        if (false !== filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return ['ipAddress' => self::IPV4_MAPPED_PREFIX.$host, 'zoneIdentifier' => null];
        }

        if (!str_starts_with($host, '[')) {
            return ['ipAddress' => null, 'zoneIdentifier' => null];
        }

        if (!str_ends_with($host, ']')) {
            return ['ipAddress' => null, 'zoneIdentifier' => null];
        }

        [$ipv6, $zoneIdentifier] = explode('%', substr($host, 1, -1), 2) + [1 => null];
        if (false === filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return ['ipAddress' => null, 'zoneIdentifier' => null];
        }

        return match (true) {
            null === $zoneIdentifier,
            is_string($ipv6) && str_starts_with((string)inet_pton($ipv6), self::HOST_ADDRESS_BLOCK) =>  ['ipAddress' => $ipv6, 'zoneIdentifier' => $zoneIdentifier],
            default => ['ipAddress' => null, 'zoneIdentifier' => null],
        };
    }
}
