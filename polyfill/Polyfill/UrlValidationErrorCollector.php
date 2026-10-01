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

namespace League\Uri\Polyfill;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;
use Uri\WhatWg\UrlValidationError;
use Uri\WhatWg\UrlValidationErrorType;
use ValueError;

use function array_values;
use function is_scalar;
use function is_string;
use function mb_substr;

/**
 * This class allows collecting WHATWG errors emitted by \Rowbot\URL\URL
 * and converts them into \Uri\WhatWg\UrlValidationError instances.
 */
final class UrlValidationErrorCollector extends AbstractLogger
{
    /** @var array<string, list<UrlValidationError>> */
    private array $errors;

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->errors = [];
    }

    /**
     * @return list<UrlValidationError>
     */
    public function errors(): array
    {
        return array_merge(...array_values($this->errors));
    }

    /**
     * @return list<UrlValidationError>
     */
    public function recoverableErrors(?string $baseUrl = null): array
    {
        $errors = [];
        foreach ($this->errors as $offset => $error) {
            if ($offset === $baseUrl) {
                continue;
            }

            foreach ($error as $item) {
                if (!$item->failure) {
                    $errors[] = $item;
                }
            }
        }

        return $errors;
    }

    public function log(mixed $level, string|Stringable $message, array $context = []): void
    {
        // the 'input' field contains the URL where the error occurred.
        $errorContext = $context['input'] ?? null;
        if (is_scalar($errorContext) || $errorContext instanceof Stringable) {
            $errorContext = (string) $errorContext;
        }

        if (!is_string($errorContext)) {
            return;
        }

        // the 'column' field contains the last position where the soft error occurred.
        $column = $context['column'] ?? 1;
        if (!is_int($column)) {
            return;
        }

        //PHP's UrlValidationError loses the full URL and only exposes the
        //part of the URL which was not parsed after the error is detected.
        $this->errors[$errorContext][] = new UrlValidationError(
            mb_substr($errorContext, $column - 1),
            // \Rowbot\URL\URL makes no usage of string interpolation
            // the message is a string representing one of
            // \Uri\WhatWg\UrlValidationErrorType case
            // defined in https://url.spec.whatwg.org/#writing
            match ((string) $message) {
                // IDNA Error Tyoe
                'domain-to-ASCII' => UrlValidationErrorType::DomainToAscii,
                'domain-to-unicode' => UrlValidationErrorType::DomainToUnicode,
                'domain-invalid-code-point' => UrlValidationErrorType::DomainInvalidCodePoint,
                // Host parsing
                'host-invalid-code-point' => UrlValidationErrorType::HostInvalidCodePoint,
                'IPv4-part-empty' => UrlValidationErrorType::Ipv4EmptyPart,
                'IPv4-non-decimal-part' => UrlValidationErrorType::Ipv4NonDecimalPart,
                'IPv4-too-many-parts' => UrlValidationErrorType::Ipv4TooManyParts,
                'IPv4-non-numeric-part' => UrlValidationErrorType::Ipv4NonNumericPart,
                'IPv4-out-of-range-part' => UrlValidationErrorType::Ipv4OutOfRangePart,
                'IPv6-invalid-compression' => UrlValidationErrorType::Ipv6InvalidCompression,
                'IPv6-invalid-code-point' => UrlValidationErrorType::Ipv6InvalidCodePoint,
                'IPv6-multiple-compression' => UrlValidationErrorType::Ipv6MultipleCompression,
                'IPv6-too-few-pieces' => UrlValidationErrorType::Ipv6TooFewPieces,
                'IPv6-too-many-pieces' => UrlValidationErrorType::Ipv6TooManyPieces,
                'IPv6-unclosed' => UrlValidationErrorType::Ipv6Unclosed,
                'IPv4-in-IPv6-out-of-range-part' => UrlValidationErrorType::Ipv4InIpv6OutOfRangePart,
                'IPv4-in-IPv6-too-few-parts' => UrlValidationErrorType::Ipv4InIpv6TooFewParts,
                'IPv4-in-IPv6-invalid-code-point' => UrlValidationErrorType::Ipv4InIpv6InvalidCodePoint,
                'IPv4-in-IPv6-too-many-pieces' => UrlValidationErrorType::Ipv4InIpv6TooManyPieces,
                // URL parsing
                'invalid-URL-unit' => UrlValidationErrorType::InvalidUrlUnit,
                'special-scheme-missing-following-solidus' => UrlValidationErrorType::SpecialSchemeMissingFollowingSolidus,
                'missing-scheme-non-relative-URL' => UrlValidationErrorType::MissingSchemeNonRelativeUrl,
                'invalid-credentials' => UrlValidationErrorType::InvalidCredentials,
                'invalid-reverse-solidus' => UrlValidationErrorType::InvalidReverseSoldius,
                'host-missing' => UrlValidationErrorType::HostMissing,
                'port-out-of-range' => UrlValidationErrorType::PortOutOfRange,
                'port-invalid' => UrlValidationErrorType::PortInvalid,
                'file-invalid-Windows-drive-letter-host' => UrlValidationErrorType::FileInvalidWindowsDriveLetterHost,
                'file-invalid-Windows-drive-letter' => UrlValidationErrorType::FileInvalidWindowsDriveLetter,
                default  => throw new ValueError('unknown error type:'.$message),
            },
            // \Rowbot\URL\URL emits LogLevel::WARNING when the error is a failure
            // otherwise LogLevel::NOTICE is used
            LogLevel::WARNING === $level,
        );
    }
}
