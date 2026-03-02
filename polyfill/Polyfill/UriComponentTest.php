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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Uri\InvalidUriException;
use Uri\Rfc3986\UriComponent;

final class UriComponentTest extends TestCase
{
    public function testFragmentDecodesSlashButNotAt(): void
    {
        self::assertSame('_%40/', UriComponent::Fragment->decode('_%40%2F'));
    }

    public function testFragmentDoesNotDecodeEncodedAt(): void
    {
        self::assertSame('%40', UriComponent::Fragment->decode('%40'));
    }

    public function testFragmentEncodesHashButAllowsLiteralSlash(): void
    {
        self::assertSame('/#', UriComponent::Fragment->encode('/#'));
    }

    public function testQueryEncodesSpaceAsPercent20(): void
    {
        self::assertSame('a%20b', UriComponent::Query->encode('a b'));
    }

    public function testFormQueryEncodesSpaceAsPlus(): void
    {
        self::assertSame('a+b', UriComponent::FormQuery->encode('a b'));
    }

    public function testFormQueryDecodesPlusToSpace(): void
    {
        self::assertSame('a b', UriComponent::FormQuery->decode('a+b'));
    }

    public function testFormQueryDoesNotDecodeEncodedQuestionMark(): void
    {
        self::assertSame('?', UriComponent::FormQuery->decode('%3F'));
    }

    public function testUserInfoAllowsColonButNotAt(): void
    {
        self::assertSame('user:pass%40word', UriComponent::UserInfo->encode('user:pass@word'));
    }

    public function testUserInfoDoesNotDecodeEncodedColon(): void
    {
        self::assertSame('%3A', UriComponent::UserInfo->decode('%3A'));
    }

    public function testPathEncodesQuestionMark(): void
    {
        self::assertSame('/a%3Fb', UriComponent::Path->encode('/a?b'));
    }

    public function testPathDecodesEncodedSlash(): void
    {
        self::assertSame('/', UriComponent::Path->decode('%2F'));
    }

    public function testAllReservedCharactersEncodesAllReserved(): void
    {
        self::assertSame('%3A%2F%3F%23%5B%5D%40%21%24%26%27%28%29%2A%2B%2C%3B%3D', UriComponent::AllReservedCharacters->encode(':/?#[]@!$&\'()*+,;='));
    }

    public function testAllReservedCharactersDecodesReserved(): void
    {
        self::assertSame('/', UriComponent::AllReservedCharacters->decode('%2F'));
    }

    public function testAllButUnreservedEncodesEverythingElse(): void
    {
        self::assertSame('abc%2F%40', UriComponent::AllButUnreservedCharacters->encode('abc/@'));
    }

    public function testAllButUnreservedDoesNotDecodeUnreserved(): void
    {
        self::assertSame('%61', UriComponent::AllButUnreservedCharacters->decode('%61'));
    }

    public function testEncodeDecodeIsStableForFragment(): void
    {
        $input = 'a/b#c@d';

        $encoded = UriComponent::Fragment->encode($input);

        self::assertSame($encoded, UriComponent::Fragment->decode($encoded));
    }

    public function testAbsolutePathFirstSegmentRejectsDoubleSlash(): void
    {
        $this->expectException(InvalidUriException::class);

        UriComponent::AbsolutePathReferenceFirstSegment->encode('//foo');
    }

    public function testRelativePathFirstSegmentRejectsColon(): void
    {
        $this->expectException(InvalidUriException::class);

        UriComponent::RelativePathReferenceFirstSegment->encode('foo:bar');
    }

    public function testHostDoesNotEncodeIpv4(): void
    {
        $host = '192.168.10.5';

        self::assertSame($host, UriComponent::Host->encode($host));
    }

    public function testHostDoesNotDecodeIpv4(): void
    {
        $host = '192.168.10.5';

        self::assertSame($host, UriComponent::Host->decode($host));
    }

    public function testHostDoesNotEncodeIpv6(): void
    {
        $host = '[2001:db8::1]';

        self::assertSame($host, UriComponent::Host->encode($host));
    }

    public function testHostDoesNotDecodeIpv6(): void
    {
        $host = '[2001:db8::1]';

        self::assertSame($host, UriComponent::Host->decode($host));
    }

    public function testHostDoesNotEncodeIpvFuture(): void
    {
        $host = '[v1.fe80::a+en1]';

        self::assertSame($host, UriComponent::Host->encode($host));
    }

    public function testHostDoesNotDecodeIpvFuture(): void
    {
        $host = '[v1.fe80::a+en1]';

        self::assertSame($host, UriComponent::Host->decode($host));
    }

    public function testHostEncodesIllegalAsciiCharacters(): void
    {
        $this->expectException(InvalidUriException::class);

        UriComponent::Host->encode('example site.com');
    }

    public function testHostPreservesSubDelimiters(): void
    {
        self::assertSame('exa!$&\'()*+,;=mple.com', UriComponent::Host->encode("exa!$&'()*+,;=mple.com"));
    }

    public function testHostEncodesUtf8UsingPercentEncoding(): void
    {
        self::assertSame('b%C3%A9b%C3%A9.be', UriComponent::Host->encode('bébé.be'));
    }

    public function testHostDecodeRestoresUtf8(): void
    {
        self::assertSame('bébé.be', UriComponent::Host->decode('b%C3%A9b%C3%A9.be'));
    }

    public function testHostEncodeNormalizesHexCase(): void
    {
        self::assertSame('b%C3%A9.be', UriComponent::Host->encode('b%c3%a9.be'));
    }

    public function testHostDecodeAcceptsLowercaseHex(): void
    {
        self::assertSame('bé.be', UriComponent::Host->decode('b%c3%a9.be'));
    }

    public function testHostEncodeDecodeSymmetry(): void
    {
        $original = 'bébé.example';

        $encoded = UriComponent::Host->encode($original);
        $decoded = UriComponent::Host->decode($encoded);

        self::assertSame($original, $decoded);
    }

    #[DataProvider('fragmentDecodingProvider')]
    public function testFragmentDecoding(string $input, string $expected): void
    {
        self::assertSame($expected, UriComponent::Fragment->decode($input));
    }

    /**
     * @return iterable<non-empty-string, list<string>>
     */
    public static function fragmentDecodingProvider(): iterable
    {
        yield 'unreserved A remains encoded' => ['%41', '%41'];
        yield 'reserved allowed in fragment: "/" decoded' => ['%2F', '/'];
        yield 'reserved NOT allowed in fragment: "@" stays encoded' => ['%40', '%40'];
        yield 'mixed fragment decoding' => ['_%40%2F', '_%40/'];
    }

    #[DataProvider('queryDecodingProvider')]
    public function testQueryDecoding(string $input, string $expected): void
    {
        self::assertSame($expected, UriComponent::Query->decode($input));
    }

    /**
     * @return iterable<non-empty-string, list<string>>
     */
    public static function queryDecodingProvider(): iterable
    {
        yield 'encoded question mark must be decoded in query' => ['%3F', '?'];
        yield 'encoded slash is decoded' => ['%2F', '/'];
        yield 'unreserved never decoded' => ['%7E', '%7E'];
    }

    public function testAllButUnreservedDecoding(): void
    {
        self::assertSame('%41%2F', UriComponent::AllButUnreservedCharacters->decode('%41%2F'));
    }

    public function testAllReservedCharactersDecoding(): void
    {
        self::assertSame(':/?#[]@!$&\'()*+,;=', UriComponent::AllReservedCharacters->decode('%3A%2F%3F%23%5B%5D%40%21%24%26%27%28%29%2A%2B%2C%3B%3D'));
    }

    #[DataProvider('decodeProvider')]
    public function testDecodeComponent(UriComponent $component, string $input, string $expected): void
    {
        self::assertSame($expected, $component->decode($input));
    }

    /**
     * @return iterable<non-empty-string, array{0: UriComponent, 1:string, 2: string}>
     */
    public static function decodeProvider(): iterable
    {
        yield 'unreserved remains encoded in path' => [UriComponent::Path, '%7E', '%7E'];
        yield 'unreserved remains encoded in query' => [UriComponent::Query, '%41', '%41'];
        yield 'slash is decoded in path' => [UriComponent::Path, '%2F', '/'];
        yield 'colon is decoded in path' => [UriComponent::Path, '%3A', ':'];
        yield 'question mark remains encoded in path' => [UriComponent::Path, '%3F', '%3F'];
        yield 'question mark is decoded in query' => [UriComponent::Query, '%3F', '?'];
        yield 'slash is decoded in query' => [UriComponent::Query, '%2F', '/'];
        yield 'hash remains encoded in query' => [UriComponent::Query, '%23', '%23'];
        yield 'hash is decoded in fragment' => [UriComponent::Fragment, '%23', '#'];
        yield 'question mark is decoded in fragment' => [UriComponent::Fragment, '%3F', '?'];
        yield 'slash is decoded in fragment' => [UriComponent::Fragment, '%2F', '/'];
        yield  'reserved gen-delims are decoded in AllReservedCharacters' => [UriComponent::AllReservedCharacters, '%3A%2F%3F%23%5B%5D%40', ':/?#[]@'];
        yield 'sub-delims are decoded in AllReservedCharacters' => [UriComponent::AllReservedCharacters, '%21%24%26%27%28%29%2A%2B%2C%3B%3D', '!$&\'()*+,;='];
        yield 'unreserved still remains encoded in AllReservedCharacters' => [UriComponent::AllReservedCharacters, '%41', '%41'];
        yield 'host decodes everything' => [UriComponent::Host, 'b%C3%A9b%C3%A9.be', 'bébé.be'];
        yield 'host decodes reserved characters' => [UriComponent::Host, '%3A%2F%3F', ':/?'];
    }

    #[DataProvider('roundTripProvider')]
    public function testEncodeDecodeRoundTrip(UriComponent $component, string $input, string $expectedAfterDecode): void
    {
        $encoded = $component->encode($input);
        $decoded = $component->decode($encoded);

        self::assertSame(
            $expectedAfterDecode,
            $decoded,
            'Round-trip failed for '.$component->name.': encode('.$input.') = '.$encoded.', decode() = '.$decoded,
        );
    }

    public static function roundTripProvider(): iterable
    {
        yield 'userinfo simple' => [UriComponent::UserInfo, 'user:pass', 'user:pass'];
        yield 'userinfo with reserved' => [UriComponent::UserInfo, 'user!$&\'()*+,;=', 'user!$&\'()*+,;='];
        yield 'path basic' => [UriComponent::Path, '/a/b/c', '/a/b/c'];
        yield 'path with colon and slash' => [UriComponent::Path, '/a:b/c', '/a:b/c'];
        yield 'path with encoded question mark remains encoded' => [UriComponent::Path, '/a%3Fb', '/a%3Fb'];
        yield 'path segment simple' => [UriComponent::PathSegment, 'segment', 'segment'];
        yield 'path segment with slash encoded' => [UriComponent::PathSegment, 'seg/ment', 'seg/ment'];
        yield 'absolute path first segment' => [UriComponent::AbsolutePathReferenceFirstSegment, 'segment', 'segment'];
        yield 'relative path first segment' => [UriComponent::RelativePathReferenceFirstSegment, 'segment', 'segment'];
        yield 'query simple' => [UriComponent::Query, 'a=b&c=d', 'a=b&c=d'];
        yield 'query with encoded hash remains encoded' => [UriComponent::Query, 'a%23b', 'a%23b'];
        yield 'query allows literal question mark' => [UriComponent::Query, 'a?b', 'a?b'];
        yield 'form query space normalization' => [UriComponent::FormQuery, 'a b', 'a b'];
        yield 'form query plus literal' => [UriComponent::FormQuery, 'a+b', 'a b'];
        yield 'fragment simple' => [UriComponent::Fragment, 'section1', 'section1'];
        yield 'fragment allows hash and question mark' => [UriComponent::Fragment, 'a#b?c', 'a#b?c'];
        yield 'host idn round trip' => [UriComponent::Host, 'bébé.be', 'bébé.be'];
        yield 'host ascii' => [UriComponent::Host, 'example.com', 'example.com'];
        yield 'all reserved characters preserved' => [UriComponent::AllReservedCharacters, ':/?#[]@!$&\'()*+,;=', ':/?#[]@!$&\'()*+,;='];
        yield 'all but unreserved characters' => [UriComponent::AllButUnreservedCharacters, 'AZaz09-._~', 'AZaz09-._~'];
    }
}
