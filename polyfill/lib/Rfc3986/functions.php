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

use League\Uri\HostRecord;
use Uri\InvalidUriException;

use function function_exists;
use function preg_quote;
use function preg_replace_callback;
use function rawurlencode;
use function str_replace;
use function strtolower;
use function strtoupper;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID >= 80600) {
    return;
}

if (!function_exists('Uri\Rfc3986\uri_percent_encode')) {
    /**
     * This is a user-land polyfill to the native Uri\Rfc3986\HostType Enum included in PHP8.6.
     *
     * @see https://wiki.php.net/rfc/uri_followup#percent-encoding_support
     *
     * @throws InvalidUriException
     */
    function uri_percent_encode(string $input, UriPercentEncodingMode $mode): string
    {
        /** @var array<key-of<UriPercentEncodingMode>, non-empty-string> $settings */
        static $settings = [];
        if ([] === $settings) {
            $regexpPartUnreserved = 'A-Za-z0-9\-._~';
            $regexpPartSubDelims = '!\$&\'\(\)\*\+,;=';
            $genDelims = ':/?#[]@';
            $baseRexp = $regexpPartUnreserved.$regexpPartSubDelims;

            foreach (UriPercentEncodingMode::cases() as $case) {
                $extra = match ($case) {
                    UriPercentEncodingMode::UserInfo => ':',
                    UriPercentEncodingMode::Path => '/:@',
                    UriPercentEncodingMode::Query,
                    UriPercentEncodingMode::FormQuery => '/?:@&=',
                    UriPercentEncodingMode::Fragment => '/?:@&=#',
                    default => '',
                };

                $settings[$case->name] = match ($case) {
                    UriPercentEncodingMode::AllReservedCharacters => '/['.preg_quote($genDelims, '/').$regexpPartSubDelims.']/',
                    UriPercentEncodingMode::AllButUnreservedCharacters => '/(?:%[0-9A-Fa-f]{2}|[^'.$regexpPartUnreserved.'])/u',
                    default => '/%(?![0-9A-Fa-f]{2})|[^'.$baseRexp.preg_quote($extra, '/').'%]+/',
                };
            }
        }

        if (UriPercentEncodingMode::RegisteredNameHost === $mode) {
            HostRecord::isValid($input) || throw new InvalidUriException('Host must not contain invalid characters.');
            if (HostRecord::isIp($input)) {
                return $input;
            }

            $input = strtolower($input);
        }

        $result = (string) preg_replace_callback(
            pattern: '/%[0-9a-f]{2}/',
            callback: static fn (array $matches): string => strtoupper($matches[0]),
            subject: (string) preg_replace_callback(
                pattern: $settings[$mode->name],
                callback: static fn (array $matches): string => rawurlencode($matches[0]),
                subject: $input
            )
        );

        return UriPercentEncodingMode::FormQuery === $mode
            ? str_replace('%20', '+', $result)
            : $result;
    }
}
