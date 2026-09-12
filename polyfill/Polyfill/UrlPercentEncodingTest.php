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
use Uri\WhatWg\UrlPercentEncodingMode;

use function Uri\WhatWg\url_percent_encode;

final class UrlPercentEncodingTest extends TestCase
{
    #[DataProvider('encodeProvider')]
    public function testEncode(UrlPercentEncodingMode $component, string $input, string $expected): void
    {
        self::assertSame($expected, url_percent_encode($input, $component));
    }

    public static function encodeProvider(): iterable
    {
        yield 'query encodes space' => [UrlPercentEncodingMode::Query, 'a b', 'a%20b'];
        yield 'path encodes ?' => [UrlPercentEncodingMode::Path, 'a?b', 'a%3Fb'];
        yield 'path segment encodes slash' => [UrlPercentEncodingMode::PathSegment, 'a/b', 'a%2Fb'];
        yield 'username encodes @' => [UrlPercentEncodingMode::Username, 'a@b', 'a%40b'];
        yield 'password encodes @' => [UrlPercentEncodingMode::Password, 'a@b', 'a%40b'];
        yield 'fragment encodes space' => [UrlPercentEncodingMode::Fragment, 'a b', 'a%20b'];
        yield 'special query encodes apostrophe' => [UrlPercentEncodingMode::SpecialQuery, "a'b", 'a%27b'];
        yield 'form query converts space to plus' => [UrlPercentEncodingMode::FormQuery, 'a b', 'a+b'];
        yield 'unicode encoded UTF8' => [UrlPercentEncodingMode::Path, 'bébé', 'b%C3%A9b%C3%A9'];
    }

    #[DataProvider('userInfoEncodeProvider')]
    public function testUserInfoEncode(string $input, string $expected): void
    {
        self::assertSame(
            $expected,
            url_percent_encode($input, UrlPercentEncodingMode::Username)
        );
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

    #[DataProvider('opaqueHostEncodeProvider')]
    public function testOpaqueHostEncode(string $input, string $expected): void
    {
        self::assertSame(
            $expected,
            url_percent_encode($input, UrlPercentEncodingMode::OpaqueHost)
        );
    }

    public static function opaqueHostEncodeProvider(): iterable
    {
        yield 'ascii host unchanged' => ['example.com', 'example.com'];
        yield 'unicode host encoded' => ['bébé.com', 'b%C3%A9b%C3%A9.com'];
        yield 'control char encoded' => ["example\t.com", 'example%09.com'];
        yield 'greater than tilde encoded 1' => ["example\x80.com", 'example%80.com'];
        yield 'greater than tilde encoded 2' => ["example\u{80}.com", 'example%C2%80.com'];
    }

    public function testPathSegmentEncodesSlash(): void
    {
        self::assertSame(
            'a%2Fb',
            url_percent_encode('a/b', UrlPercentEncodingMode::PathSegment)
        );
    }

    public function testOpaquePathSegmentAllowsSlash(): void
    {
        self::assertSame(
            'a/b',
            url_percent_encode('a/b', UrlPercentEncodingMode::OpaquePath)
        );
    }

    public function testPathEncodesQuestionMark(): void
    {
        self::assertSame(
            'file%3Fname',
            url_percent_encode('file?name', UrlPercentEncodingMode::Path)
        );
    }

    public function testOpaquePathSegmentDoesNotEncodesQuestionMark(): void
    {
        self::assertSame(
            'file?name',
            url_percent_encode('file?name', UrlPercentEncodingMode::OpaquePath)
        );
    }

    public function testOpaqueHostUsesPercentEncoding(): void
    {
        self::assertSame(
            'b%C3%A9b%C3%A9.be',
            url_percent_encode('bébé.be', UrlPercentEncodingMode::OpaqueHost)
        );
    }
}
