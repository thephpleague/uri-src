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

use PHPUnit\Framework\TestCase;
use Uri\InvalidUriException;
use Uri\Rfc3986\UriPercentEncodingMode;

use function Uri\Rfc3986\uri_percent_encode;

final class UriPercentEncodingTest extends TestCase
{
    public function testFragmentEncodesHashButAllowsLiteralSlash(): void
    {
        self::assertSame('/#', uri_percent_encode('/#', UriPercentEncodingMode::Fragment));
    }

    public function testQueryEncodesSpaceAsPercent20(): void
    {
        self::assertSame('a%20b', uri_percent_encode('a b', UriPercentEncodingMode::Query));
    }

    public function testFormQueryEncodesSpaceAsPlus(): void
    {
        self::assertSame('a+b', uri_percent_encode('a b', UriPercentEncodingMode::FormQuery));
    }

    public function testUserInfoAllowsColonButNotAt(): void
    {
        self::assertSame(
            'user:pass%40word',
            uri_percent_encode('user:pass@word', UriPercentEncodingMode::UserInfo)
        );
    }

    public function testPathEncodesQuestionMark(): void
    {
        self::assertSame('/a%3Fb', uri_percent_encode('/a?b', UriPercentEncodingMode::Path));
    }

    public function testAllReservedCharactersEncodesAllReserved(): void
    {
        self::assertSame(
            '%3A%2F%3F%23%5B%5D%40%21%24%26%27%28%29%2A%2B%2C%3B%3D',
            uri_percent_encode(':/?#[]@!$&\'()*+,;=', UriPercentEncodingMode::AllReservedCharacters)
        );
    }

    public function testAllButUnreservedEncodesEverythingElse(): void
    {
        self::assertSame(
            'abc%2F%40',
            uri_percent_encode('abc/@', UriPercentEncodingMode::AllButUnreservedCharacters)
        );
    }

    public function testHostDoesNotEncodeIpv4(): void
    {
        $host = '192.168.10.5';

        self::assertSame($host, uri_percent_encode($host, UriPercentEncodingMode::RegisteredNameHost));
    }

    public function testHostDoesNotEncodeIpv6(): void
    {
        $host = '[2001:db8::1]';

        self::assertSame($host, uri_percent_encode($host, UriPercentEncodingMode::RegisteredNameHost));
    }

    public function testHostDoesNotEncodeIpvFuture(): void
    {
        $host = '[v1.fe80::a+en1]';

        self::assertSame($host, uri_percent_encode($host, UriPercentEncodingMode::RegisteredNameHost));
    }

    public function testHostEncodesIllegalAsciiCharacters(): void
    {
        $this->expectException(InvalidUriException::class);

        uri_percent_encode('example site.com', UriPercentEncodingMode::RegisteredNameHost);
    }

    public function testHostPreservesSubDelimiters(): void
    {
        self::assertSame(
            'exa!$&\'()*+,;=mple.com',
            uri_percent_encode("exa!$&'()*+,;=mple.com", UriPercentEncodingMode::RegisteredNameHost)
        );
    }

    public function testHostEncodesUtf8UsingPercentEncoding(): void
    {
        self::assertSame(
            'b%C3%A9b%C3%A9.be',
            uri_percent_encode('bébé.be', UriPercentEncodingMode::RegisteredNameHost)
        );
    }

    public function testHostEncodeNormalizesHexCase(): void
    {
        self::assertSame(
            'b%C3%A9.be',
            uri_percent_encode('b%c3%a9.be', UriPercentEncodingMode::RegisteredNameHost)
        );
    }
}
