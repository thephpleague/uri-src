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
use Uri\WhatWg\InvalidUrlException;
use Uri\WhatWg\Url;
use Uri\WhatWg\UrlBuilder;

final class UrlBuilderTest extends TestCase
{
    public function test_it_can_build_a_new_uri_instance(): void
    {
        $builder = new UrlBuilder();
        $changedBuilder = $builder
            ->setScheme('https')
            ->setUsername('user')
            ->setPassword('pass')
            ->setHost('wiki.php.net')
            ->setPort(8080)
            ->setPathSegments(['rf:c', 'uri_followup'])
            ->setQuery('a=1&b=2')
            ->setFragment('uri_building');

        self::assertSame($changedBuilder, $builder);
        self::assertSame('https://user:pass@wiki.php.net:8080/rf:c/uri_followup?a=1&b=2#uri_building', $builder->build()->toAsciiString());
    }

    public function test_it_can_build_a_new_uri_instance_with_delimiters_given(): void
    {
        $builder = (new UrlBuilder())
            ->setScheme('https://')
            ->setHost('bébé.be')
            ->setPort(8080)
            ->setPath('rf:c/uri_followup')
            ->setQuery('?a=1&b=2')
            ->setFragment('#uri_building');

        self::assertSame('https://bébé.be:8080/rf:c/uri_followup?a=1&b=2#uri_building', $builder->build()->toUnicodeString());
    }

    public function test_it_fails_to_build_a_new_uri_if_the_user_info_is_present_and_the_host_is_missing(): void
    {
        $this->expectException(InvalidUrlException::class);

        (new UrlBuilder())
            ->setScheme('https')
            ->setUsername('user')
            ->setPassword('pass')
            ->setPathSegments(['rf:c', 'uri_followup'])
            ->setQuery('a=1&b=2')
            ->setFragment('uri_building')
            ->build();
    }

    public function test_it_fails_to_build_a_new_uri_if_the_port_is_present_and_the_host_is_missing(): void
    {
        $this->expectException(InvalidUrlException::class);

        (new UrlBuilder())
            ->setScheme('https')
            ->setPort(8080)
            ->setPathSegments(['rf:c', 'uri_followup'])
            ->setQuery('a=1&b=2')
            ->setFragment('uri_building')
            ->build();
    }

    public function test_it_fails_to_build_a_new_uri_if_the_scheme_is_missing(): void
    {
        $this->expectException(InvalidUrlException::class);

        (new UrlBuilder())
            ->setPathSegments(['rf:c', 'uri_followup'])
            ->setQuery('a=1&b=2')
            ->setFragment('uri_building')
            ->build();
    }

    public function test_it_fails_if_the_scheme_contains_invalid_characters(): void
    {
        $this->expectException(InvalidUrlException::class);

        (new UrlBuilder())->setScheme('htt*s')->build();
    }

    public function test_it_fails_if_the_host_contains_invalid_characters(): void
    {
        $this->expectException(InvalidUrlException::class);

        (new UrlBuilder())->setHost("\0bébé.be");
    }

    public function test_building_without_calling_any_setter(): void
    {
        $this->expectException(InvalidUrlException::class);

        self::assertSame('', (new UrlBuilder())->build()->toAsciiString());
    }

    public function test_building_with_base_url(): void
    {
        $this->expectException(InvalidUrlException::class);

        (new UrlBuilder())->setPath('/foo')->build(Url::parse('mailto:example.com'));
    }

    public function test_it_makes_a_difference_between_path_segments_and_opaque_segments(): void
    {
        $uri = (new UrlBuilder())
            ->setPath('text/plain;base64,SGVsbG8gV29ybGQh')
            ->setScheme('data')
            ->build();

        $uribis = (new UrlBuilder())
            ->setPath('text/plain;base64,SGVsbG8gV29ybGQh')
            ->setScheme('HttP')
            ->build();

        self::assertSame('data:text/plain;base64,SGVsbG8gV29ybGQh', $uri->toUnicodeString());
        self::assertSame('http://text/plain;base64,SGVsbG8gV29ybGQh', $uribis->toUnicodeString());
    }

    public function test_it_can_build_from_a_base_url_with_a_relative_url(): void
    {
        $uri = (new UrlBuilder())
            ->setQuery('query')
            ->setFragment('fragment')
            ->build(baseUrl: new Url('foo://example.com'));

        self::assertSame('foo://example.com?query#fragment', $uri->toAsciiString());
    }

    public function test_it_can_build_from_a_special_scheme_base_url_with_a_relative_url(): void
    {
        $uri = (new UrlBuilder())
            ->setQuery('query')
            ->setFragment('fragment')
            ->build(baseUrl: new Url('https://example.com'));

        self::assertSame('https://example.com/?query#fragment', $uri->toAsciiString());
    }
}
