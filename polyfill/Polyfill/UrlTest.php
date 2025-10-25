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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Uri\InvalidUriException;
use Uri\UriComparisonMode;
use Uri\WhatWg\InvalidUrlException;
use Uri\WhatWg\Url;
use Uri\WhatWg\UrlValidationError;
use Uri\WhatWg\UrlValidationErrorType;

#[CoversClass(Url::class)]
#[CoversClass(InvalidUrlException::class)]
#[CoversClass(UriComparisonMode::class)]
#[CoversClass(UrlValidationError::class)]
#[CoversClass(UrlValidationErrorCollector::class)]
#[CoversClass(UrlValidationErrorType::class)]
final class UrlTest extends TestCase
{
    #[Test]
    public function it_will_fail_parse_an_invalid_url(): void
    {
        $errors = [];
        $url = Url::parse('invalid url', null, $errors);

        self::assertNull($url);
        self::assertNotEmpty($errors);
        self::assertInstanceOf(UrlValidationError::class, $errors[0]);
        self::assertSame(UrlValidationErrorType::MissingSchemeNonRelativeUrl, $errors[0]->type);
    }

    #[Test]
    public function it_will_return_null_on_invalid_url_parsing(): void
    {
        self::assertNull(Url::parse('/foo', Url::parse('mailto:example.com')));
    }

    #[Test]
    public function it_will_return_soft_errors_when_uri_is_parsed_with_errors(): void
    {
        $softErrors = [];
        $url = new Url(' https://example.org', null, $softErrors);

        self::assertSame('https://example.org/', $url->toAsciiString());
        self::assertNotEmpty($softErrors);
        self::assertInstanceOf(UrlValidationError::class, $softErrors[0]);
        self::assertSame(UrlValidationErrorType::InvalidUrlUnit, $softErrors[0]->type);
    }

    #[Test]
    public function it_will_resolve_an_uri(): void
    {
        $url = new Url('https://example.com');

        self::assertSame('https://example.com/', $url->toAsciiString());
        self::assertSame('https://example.com/foo', $url->resolve('/foo')->toAsciiString());
    }

    #[Test]
    public function it_can_retrieve_url_components(): void
    {
        $url = new Url('HTTPS://%61pple:p%61ss@ex%61mple.com:433/foob%61r?%61bc=%61bc#%61bc');

        self::assertSame('https', $url->getScheme());
        self::assertSame('%61pple', $url->getUsername());
        self::assertSame('p%61ss', $url->getPassword());
        self::assertSame('example.com', $url->getAsciiHost());
        self::assertSame('example.com', $url->getUnicodeHost());
        self::assertSame(433, $url->getPort());
        self::assertSame('/foob%61r', $url->getPath());
        self::assertSame('%61bc=%61bc', $url->getQuery());
        self::assertSame('%61bc', $url->getFragment());
    }

    #[Test]
    public function it_will_handle_idna_host(): void
    {
        $url = new Url('https://🐘.com');

        self::assertSame('xn--go8h.com', $url->getAsciiHost());
        self::assertSame('🐘.com', $url->getUnicodeHost());
    }

    #[Test]
    public function it_can_perform_normalization_on_parsing(): void
    {
        $url = new Url('https://[2001:0db8:0001:0000:0000:0ab9:C0A8:0102]/foo/bar%3Fbaz?foo=bar%26baz%3Dqux');

        self::assertSame('[2001:db8:1::ab9:c0a8:102]', $url->getAsciiHost());
        self::assertSame('/foo/bar%3Fbaz', $url->getPath());
        self::assertSame('foo=bar%26baz%3Dqux', $url->getQuery());
    }

    #[Test]
    public function it_fails_when_trying_to_update_an_invalid_host(): void
    {
        $this->expectException(InvalidUrlException::class);

        $url = new Url('https://example.com');
        $url->withHost('2001:db8:0:0:0:0:0:1');
    }

    #[Test]
    public function it_fails_when_trying_to_update_an_valid_host_on_a_opaque_url(): void
    {
        $url = new Url('mailto:toto@example.com');
        $urlbis = $url->withHost('example.com');

        self::assertSame($url->toAsciiString(), $urlbis->toAsciiString());
    }

    #[Test]
    public function it_fails_when_trying_to_update_an_invalid_scheme(): void
    {
        $this->expectException(InvalidUrlException::class);

        $url = new Url('mailto:toto@example.com');
        $url->withScheme('f__oo');
    }

    #[Test]
    public function it_succeed_when_updating_an_invalid_ipv4_host(): void
    {
        $invalidIpv4Host = '255.255.255.256';
        $url = new Url('scheme://host/path');
        $urlBis = $url->withHost($invalidIpv4Host);

        self::assertSame($invalidIpv4Host, $urlBis->getAsciiHost());
    }

    #[Test]
    public function it_will_percent_encode_characters(): void
    {
        $url = new Url('https://example.com');
        $newUrl = $url->withPath('/?#:');

        self::assertSame('/%3F%23:', $newUrl->getPath());
    }

    #[Test]
    public function it_will_accept_delimiters_with_withers(): void
    {
        $url = new Url('https://example.com/');
        $newUrl = $url
            ->withQuery('?foo')
            ->withFragment('#bar');

        self::assertSame('foo', $newUrl->getQuery());
        self::assertSame('bar', $newUrl->getFragment());
    }

    #[Test]
    public function it_can_recompose_the_uri(): void
    {
        $url = new Url('HTTPS://////EXAMPLE.com');

        self::assertSame('https://example.com/', $url->toAsciiString());

        $url = new Url('HTTPS://////你好你好.com');

        self::assertSame('https://xn--6qqa088eba.com/', $url->toAsciiString());
        self::assertSame('https://你好你好.com/', $url->toUnicodeString());

        $url = new Url('https://[0:0::1]/');

        self::assertSame('https://[::1]/', $url->toAsciiString());
    }

    #[Test]
    public function it_can_compare_url_for_equivalence(): void
    {
        $url = new Url('https:////example.COM/#fragment');

        self::assertTrue($url->equals(new Url('https://EXAMPLE.COM')));

        $url = new Url('https://example.com#foo');

        self::assertFalse($url->equals(new Url('https://example.com'), UriComparisonMode::IncludeFragment));
    }

    #[Test]
    public function it_can_be_serialized(): void
    {
        $url = new Url('HTTPS://example.com/foo/bar');

        self::assertSame('O:14:"Uri\WhatWg\Url":2:{i:0;a:1:{s:3:"uri";s:27:"https://example.com/foo/bar";}i:1;a:0:{}}', serialize($url));
    }

    #[Test]
    public function it_can_be_unserialized(): void
    {
        $url = unserialize('O:14:"Uri\WhatWg\Url":2:{i:0;a:1:{s:3:"uri";s:27:"https://example.com/foo/bar";}i:1;a:0:{}}');

        self::assertInstanceOf(Url::class, $url);
    }

    #[Test]
    public function it_can_exposed_its_components_for_debugging(): void
    {
        $url = new Url('https://example.com/foo/');

        self::assertSame([
            'scheme' => 'https',
            'username' => null,
            'password' => null,
            'host' => 'example.com',
            'port' => null,
            'path' => '/foo/',
            'query' => null,
            'fragment' => null,
        ], $url->__debugInfo());
    }

    #[Test]
    public function it_will_convert_to_unicode_the_host_in_the_uri_while_preserving_uri_construction(): void
    {
        $url = new Url('HTTPS://🐘.com:443/foo/../bar/./baz?#fragment');

        self::assertSame('https://xn--go8h.com/bar/baz?#fragment', $url->toAsciiString());
        self::assertSame('https://🐘.com/bar/baz?#fragment', $url->toUnicodeString());
    }

    #[Test]
    #[DataProvider('providesInvalidPort')]
    public function it_will_fail_to_update_with_an_invalid_port(int $port): void
    {
        $this->expectException(InvalidUrlException::class);

        (new Url('https://user:pass@example.com/foo/bar'))->withPort($port);
    }

    public static function providesInvalidPort(): iterable
    {
        yield 'Port is too high' => ['port' => 65536];
        yield 'Port is too low' => ['port' => -1];
    }

    #[Test]
    public function it_will_fail_to_update_with_an_invalid_host(): void
    {
        $this->expectException(InvalidUrlException::class);

        (new Url('https://user:pass@example.com/foo/bar'))->withHost('::1');
    }

    #[Test]
    public function it_will_handle_window_uri(): void
    {
        $url = new Url('FiLE:///c:/Users/JohnDoe/Documents/report.txt');

        self::assertSame('file', $url->getScheme());
        self::assertSame('', $url->getUnicodeHost(), 'the unicode host must be null');
        self::assertSame('', $url->getAsciiHost(), 'the ascii host must be null');
        self::assertNull($url->getPort(), 'the port must be null');
        self::assertNull($url->getFragment(), 'the fragment must be null');
        self::assertNull($url->getQuery(), 'the port must be null');
        self::assertSame('file:///c:/Users/JohnDoe/Documents/report.txt', $url->toUnicodeString());
        self::assertSame($url->toUnicodeString(), $url->toAsciiString());
    }

    #[Test]
    public function it_return_the_same_instance_if_nothing_is_changed(): void
    {
        $url = new Url('https://apple:pass@example.com:433/foobar?abc=abc#abc');
        $urlBis = $url
            ->withScheme('https:')
            ->withUsername('apple')
            ->withPassword('pass')
            ->withHost('example.com')
            ->withPort(433)
            ->withPath('/foobar')
            ->withQuery('?abc=abc')
            ->withFragment('#abc');

        self::assertTrue($urlBis->equals($url));
        self::assertSame($urlBis->toUnicodeString(), $url->toUnicodeString());
        self::assertSame($urlBis->toAsciiString(), $url->toAsciiString());
    }

    #[Test]
    public function it_will_return_soft_errors_when_uri_is_resolved_with_errors(): void
    {
        $softErrors = [];
        $url = new Url('ftp://example.com');
        $urlBis = $url->resolve('//user:p%61ss@example.org/💩', $softErrors);

        self::assertSame('ftp://user:p%61ss@example.org/%F0%9F%92%A9', $urlBis->toUnicodeString());
        self::assertNotEmpty($softErrors);
        self::assertInstanceOf(UrlValidationError::class, $softErrors[0]);
        self::assertSame(UrlValidationErrorType::InvalidCredentials, $softErrors[0]->type);
    }

    #[Test]
    public function it_can_update_the_password_separately(): void
    {
        $uri = new Url('https://user@example.com');
        $res = $uri->withPassword('password');

        self::assertSame('https://user:password@example.com/', $res->toAsciiString());
    }

    #[Test]
    public function it_can_update_the_username_separately(): void
    {
        $uri = new Url('https://:password@example.com');
        $res = $uri->withUsername('user');

        self::assertSame('https://user:password@example.com/', $res->toAsciiString());
    }

    #[Test]
    public function it_returns_a_singleton_error_instance(): void
    {
        try {
            new Url('https://example.com:99999');
        } catch (InvalidUriException $e) {
            self::assertInstanceOf(InvalidUrlException::class, $e);
            self::assertSame($e->errors[0]->type, UrlValidationErrorType::PortOutOfRange);
        }
    }

    #[Test]
    public function it_will_update_the_port_and_the_scheme(): void
    {
        $uri = new Url('https://example.com:432');
        $resScheme = $uri->withScheme('http')->withPort(8080);
        $resSchemeDot = $uri->withScheme('http:')->withPort(8080);
        $resSchemeDotSlashes = $uri->withScheme('HtTp://')->withPort(8080);

        self::assertSame('http', $resScheme->getScheme());
        self::assertSame(8080, $resScheme->getPort());
        self::assertTrue($resScheme->equals($resSchemeDot));
        self::assertTrue($resSchemeDot->equals($resSchemeDotSlashes));
    }

    public function test_it_will_return_the_host_correct_format(): void
    {
        $uri = new Url('https://bébé.be');

        self::assertSame('https://xn--bb-bjab.be/', $uri->toAsciiString());
        self::assertSame('https://bébé.be/', $uri->toUnicodeString());

        self::assertSame('xn--bb-bjab.be', $uri->getAsciiHost());
        self::assertSame('bébé.be', $uri->getUnicodeHost());

        $uri = new Url('https://xn--bb-bjab.be');

        self::assertSame('https://xn--bb-bjab.be/', $uri->toAsciiString());
        self::assertSame('https://bébé.be/', $uri->toUnicodeString());

        self::assertSame('xn--bb-bjab.be', $uri->getAsciiHost());
        self::assertSame('bébé.be', $uri->getUnicodeHost());
    }

}
