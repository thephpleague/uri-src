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

namespace League\Uri\Parser;

use League\Uri\Contracts\UriComponentInterface;
use League\Uri\Exceptions\SyntaxError;
use Stringable;
use function array_filter;
use function explode;
use function implode;
use function preg_match;
use function str_contains;
use function str_replace;
use const PHP_QUERY_RFC1738;
use const PHP_QUERY_RFC3986;

final class KeyValueConverter
{
    private const REGEXP_INVALID_CHARS = '/[\x00-\x1f\x7f]/';
    private const RFC_SUB_DELIM = "!$&'/()*+,;"; // the "=" character is intentionally missing

    /** @var non-empty-string */
    private readonly string $separator;
    private readonly array $toEncoding;
    private readonly array $toRFC3986Encoding;

    /**
     * @param non-empty-string $separator
     * @param array<string>    $toRFC3986Encoding
     * @param array<string>    $toEncoding
     */
    private function __construct(
        string $separator,
        array $toRFC3986Encoding = [],
        array $toEncoding = []
    ) {
        $this->separator = $this->filterSeparator($separator);
        $this->toRFC3986Encoding = $this->filterEncoding($toRFC3986Encoding);
        $this->toEncoding = $this->filterEncoding($toEncoding);
    }

    /**
     * @param non-empty-string $separator
     */
    public static function new(string $separator): self
    {
        return new self($separator);
    }

    /**
     * @param non-empty-string $separator
     */
    public static function fromRFC3986(string $separator = '&'): self
    {
        return self::new($separator);
    }

    /**
     * @param non-empty-string $separator
     */
    public static function fromRFC1738(string $separator = '&'): self
    {
        return new self($separator, ['%20'], ['+']);
    }

    public static function fromEncodingType(int $encType): self
    {
        return match (true) {
            PHP_QUERY_RFC3986 === $encType => self::fromRFC3986(),
            PHP_QUERY_RFC1738 === $encType => self::fromRFC1738(),
            default => throw new SyntaxError('Unknown or Unsupported encoding.'),
        };
    }

    /**
     * @return array<non-empty-list<string|null>>
     */
    public function toPairs(Stringable|string|bool|null $value): array
    {
        $filteredValue = $this->filterValue($value);

        $tmp = match (true) {
            null === $filteredValue => [],
            default => explode($this->separator, $filteredValue),
        };

        return array_map(fn (string $pair): array => explode('=', $pair, 2) + [1 => null], $tmp);
    }

    /**
     * @param iterable<array{0:string|null, 1:string|null}> $pairs
     */
    public function toValue(iterable $pairs): ?string
    {
        $filteredPairs = [];
        foreach ($pairs as $pair) {
            $filteredPairs[] = match (true) {
                null === $pair[1] => (string) $pair[0],
                default => $pair[0].'='.$pair[1],
            };
        }

        return match (true) {
            [] === $filteredPairs => null,
            default => str_replace($this->toRFC3986Encoding, $this->toEncoding, implode($this->separator, $filteredPairs)),
        };
    }

    /**
     * @param non-empty-string $separator
     */
    public function withSeparator(string $separator): self
    {
        return match (true) {
            $separator === $this->separator => $this,
            default => new self($separator, $this->toRFC3986Encoding, $this->toEncoding),
        };
    }

    public function withRfc3986Output(string ...$encoding): self
    {
        return match (true) {
            $encoding === $this->toRFC3986Encoding => $this,
            default => new self($this->separator, $encoding, $this->toEncoding),
        };
    }

    public function withEncodingOutput(string ...$encoding): self
    {
        return match (true) {
            $encoding === $this->toEncoding => $this,
            default => new self($this->separator, $this->toRFC3986Encoding, $encoding),
        };
    }

    private function filterValue(Stringable|string|bool|null $query): ?string
    {
        $query = match (true) {
            $query instanceof UriComponentInterface => $query->value(),
            $query instanceof Stringable => (string) $query,
            default => $query,
        };

        return match (true) {
            null === $query => null,
            false === $query => '0',
            true === $query => '1',
            1 === preg_match(self::REGEXP_INVALID_CHARS, $query) => throw new SyntaxError('Invalid query string: `'.$query.'`.'),
            default => str_replace($this->toEncoding, $this->toRFC3986Encoding, $query),
        };
    }

    /**
     * @return non-empty-string
     */
    private function filterSeparator(string $separator): string
    {
        return match (true) {
            '' === $separator => throw new SyntaxError('The separator character can not be the empty string.'),
            !str_contains(self::RFC_SUB_DELIM, $separator) => throw new SyntaxError('The separator character MUST be a reserved sub-delim characters other than the "=" character.'),
            default => $separator,
        };
    }

    /**
     * @return array<string>
     */
    private function filterEncoding(array $encoding): array
    {
        if (array_filter($encoding, fn (string $str): bool => '' !== $str) !== $encoding) {
            throw new SyntaxError('The encoding charactees must all be string.');
        }

        return $encoding;
    }
}
