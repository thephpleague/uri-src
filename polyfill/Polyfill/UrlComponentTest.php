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
use Uri\WhatWg\UrlComponent;

final class UrlComponentTest extends TestCase
{
    #[DataProvider('encodeProvider')]
    public function testEncode(UrlComponent $component, string $input, string $expected): void
    {
        self::assertSame($expected, $component->encode($input));
    }

    #[DataProvider('decodeProvider')]
    public function testDecode(UrlComponent $component, string $input, string $expected): void
    {
        self::assertSame($expected, $component->decode($input));
    }

    #[DataProvider('roundtripProvider')]
    public function testRoundTrip(UrlComponent $component, string $input): void
    {
        $encoded = $component->encode($input);
        $decoded = $component->decode($encoded);

        if (UrlComponent::FormQuery === $component) {
            $input = str_replace('+', ' ', $input);
        }

        self::assertSame($input, $decoded);
    }

    public static function encodeProvider(): iterable
    {
        yield 'query encodes space' => [UrlComponent::Query, 'a b', 'a%20b'];
        yield 'path encodes ?' => [UrlComponent::Path, 'a?b', 'a%3Fb'];
        yield 'path segment encodes slash' => [UrlComponent::PathSegment, 'a/b', 'a%2Fb'];
        yield 'userinfo encodes @' => [UrlComponent::UserInfo, 'a@b', 'a%40b'];
        yield 'fragment encodes space' => [UrlComponent::Fragment, 'a b', 'a%20b'];
        yield 'special query encodes apostrophe' => [UrlComponent::SpecialQuery, "a'b", 'a%27b'];
        yield 'form query converts space to plus' => [UrlComponent::FormQuery, 'a b', 'a+b'];
        yield 'unicode encoded UTF8' => [UrlComponent::Path, 'bébé', 'b%C3%A9b%C3%A9'];
    }

    public static function decodeProvider(): iterable
    {
        yield 'decode query' => [UrlComponent::Query, 'a%20b', 'a b'];
        yield 'decode path' => [UrlComponent::Path, 'a%3Fb', 'a?b'];
        yield 'decode fragment' => [UrlComponent::Fragment, 'a%20b', 'a b'];
        yield 'decode form query plus' => [UrlComponent::FormQuery, 'a+b', 'a b'];
        yield 'decode unicode' => [UrlComponent::Path, 'b%C3%A9b%C3%A9', 'bébé'];
    }

    public static function roundtripProvider(): iterable
    {
        $samples = [
            'simple',
            'a b',
            'hello/world',
            'bébé',
            'foo?bar',
            'user:pass',
        ];

        foreach (UrlComponent::cases() as $component) {
            foreach ($samples as $sample) {
                yield $component->name.' '.$sample => [$component, $sample];
            }
        }

        yield UrlComponent::UserInfo->name.' unicode' => [UrlComponent::UserInfo, 'bébé'];
        yield UrlComponent::OpaqueHost->name.' host ascii' => [UrlComponent::OpaqueHost, 'example.com'];
        yield UrlComponent::OpaqueHost->name.' unicode' => [UrlComponent::OpaqueHost, 'bébé.com'];
    }

    #[DataProvider('userInfoEncodeProvider')]
    public function testUserInfoEncode(string $input, string $expected): void
    {
        self::assertSame($expected, UrlComponent::UserInfo->encode($input));
    }

    #[DataProvider('userInfoDecodeProvider')]
    public function testUserInfoDecode(string $input, string $expected): void
    {
        self::assertSame($expected, UrlComponent::UserInfo->decode($input));
    }

    #[DataProvider('opaqueHostEncodeProvider')]
    public function testOpaqueHostEncode(string $input, string $expected): void
    {
        self::assertSame($expected, UrlComponent::OpaqueHost->encode($input));
    }

    #[DataProvider('opaqueHostDecodeProvider')]
    public function testOpaqueHostDecode(string $input, string $expected): void
    {
        self::assertSame($expected, UrlComponent::OpaqueHost->decode($input));
    }

    public static function userInfoEncodeProvider(): iterable
    {
        yield 'slash encoded' => ['user/name', 'user%2Fname'];
        yield 'colon encoded' => ['user:pass', 'user%3Apass'];
        yield 'semicolon encoded' => ['user;name', 'user%3Bname'];
        yield 'at encoded' => ['user@name', 'user%40name'];
        yield 'square bracket encoded' => ['user[name]', 'user%5Bname%5D'];
        yield 'pipe encoded' => ['user|name', 'user%7Cname'];
        yield 'unicode encoded' => ['bébé', 'b%C3%A9b%C3%A9'];
    }

    public static function userInfoDecodeProvider(): iterable
    {
        yield 'decode slash' => ['user%2Fname', 'user/name'];
        yield 'decode colon' => ['user%3Apass', 'user:pass'];
        yield 'decode unicode' => ['b%C3%A9b%C3%A9', 'bébé'];
    }

    public static function opaqueHostEncodeProvider(): iterable
    {
        yield 'ascii host unchanged' => ['example.com', 'example.com'];
        yield 'unicode host encoded' => ['bébé.com', 'b%C3%A9b%C3%A9.com'];
        yield 'control char encoded' => ["example\t.com", 'example%09.com'];
        yield 'greater than tilde encoded 1' => ["example\x80.com", 'example%80.com'];
        yield 'greater than tilde encoded 2' => ["example\u{80}.com", 'example%C2%80.com'];
    }

    public static function opaqueHostDecodeProvider(): iterable
    {
        yield 'decode unicode host' => ['b%C3%A9b%C3%A9.com', 'bébé.com'];
        yield 'decode control char' => ['example%09.com', "example\t.com"];
    }

    public function testPathSegmentEncodesSlash(): void
    {
        self::assertSame('a%2Fb', UrlComponent::PathSegment->encode('a/b'));
    }

    public function testOpaquePathSegmentAllowsSlash(): void
    {
        self::assertSame('a/b', UrlComponent::OpaquePathSegment->encode('a/b'));
    }

    public function testPathEncodesQuestionMark(): void
    {
        self::assertSame('file%3Fname', UrlComponent::Path->encode('file?name'));
    }

    public function testOpaquePathSegmentEncodesQuestionMark(): void
    {
        self::assertSame('file%3Fname', UrlComponent::OpaquePathSegment->encode('file?name'));
    }

    public function testOpaqueHostUsesPercentEncoding(): void
    {
        $result = UrlComponent::OpaqueHost->encode('bébé.be');

        self::assertSame(
            'b%C3%A9b%C3%A9.be',
            $result
        );
    }
}
