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

namespace League\Uri\Polyfill\Rfc3986;

use PHPUnit\Framework\TestCase;
use Uri\InvalidUriException;
use Uri\Rfc3986\UriPercentEncodingMode;

use function Uri\Rfc3986\uri_percent_encode;

final class UriPercentEncodingTest extends TestCase
{
    public function it_can_encode_string(
        string $input,
        string $expected,
        UriPercentEncodingMode $mode,
    ): void {
        self::assertSame($expected, uri_percent_encode($input, $mode));
    }

    public static function successFullyEncodeString(): iterable
    {
        yield 'fragment encode hash but allows literal slash' => [
            'input' => '/#',
            'expected' => '/#',
            'mode' => UriPercentEncodingMode::Fragment,
        ];

        yield 'query encode space as percent 20' => [
            'input' => 'a b',
            'expected' => 'a%20b',
            'mode' => UriPercentEncodingMode::Query,
        ];

        yield 'form query encode space as +' => [
            'input' => 'a b',
            'expected' => 'a+b',
            'mode' => UriPercentEncodingMode::FormQuery,
        ];

        yield 'user info allows colon but not @' => [
            'input' => 'user:pass@word',
            'expected' => 'user:pass%40word',
            'mode' => UriPercentEncodingMode::UserInfo,
        ];

        yield 'path encode question mark' => [
            'input' => '/a?b',
            'expected' => '/a%3Fb',
            'mode' => UriPercentEncodingMode::Path,
        ];

        yield 'all reserved characters encodes all reserved' => [
            'input' => ':/?#[]@!$&\'()*+,;=',
            'expected' => '%3A%2F%3F%23%5B%5D%40%21%24%26%27%28%29%2A%2B%2C%3B%3D',
            'mode' => UriPercentEncodingMode::AllReservedCharacters,
        ];

        yield 'all but unreserved characters encodes everything else' => [
            'input' => 'abc/@',
            'expected' => 'abc%2F%40',
            'mode' => UriPercentEncodingMode::AllButUnreservedCharacters,
        ];

        yield 'host does not encode IPv4' => [
            'input' => '192.168.10.5',
            'expected' => '192.168.10.5',
            'mode' => UriPercentEncodingMode::RegisteredNameHost,
        ];

        yield 'host does not encode IPv6' => [
            'input' => '[2001:db8::1]',
            'expected' => '[2001:db8::1]',
            'mode' => UriPercentEncodingMode::RegisteredNameHost,
        ];

        yield 'host does not encode IPvFuture' => [
            'input' => '[v1.fe80::a+en1]',
            'expected' => '[v1.fe80::a+en1]',
            'mode' => UriPercentEncodingMode::RegisteredNameHost,
        ];

        yield 'host encode illegal ascii characters' => [
            'input' => "exa!$&'()*+,;=mple.com",
            'expected' => 'exa!$&\'()*+,;=mple.com',
            'mode' => UriPercentEncodingMode::RegisteredNameHost,
        ];

        yield 'host encode illegal ascii characters' => [
            'input' => 'bébé.be',
            'expected' => 'b%C3%A9b%C3%A9.be',
            'mode' => UriPercentEncodingMode::RegisteredNameHost,
        ];

        yield 'host encodes normalized hex case' => [
            'input' => 'b%c3%a9.be',
            'expected' => 'b%C3%A9.be',
            'mode' => UriPercentEncodingMode::RegisteredNameHost,
        ];
    }

    public function testHostEncodesIllegalAsciiCharacters(): void
    {
        $this->expectException(InvalidUriException::class);

        uri_percent_encode('example site.com', UriPercentEncodingMode::RegisteredNameHost);
    }
}
