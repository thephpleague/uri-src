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
use Uri\Rfc3986\UriBuilder;

final class UriBuilderTest extends TestCase
{
    public function test_it_can_build_a_new_uri_instance(): void
    {
        $builder = new UriBuilder();
        $changedBuilder = $builder
            ->setScheme('https')
            ->setUserInfo('user:pass')
            ->setHost('wiki.php.net')
            ->setPort(8080)
            ->setPathSegments(['rf:c', 'uri_followup'])
            ->setQuery('a=1&b=2')
            ->setFragment('uri_building');

        self::assertSame($changedBuilder, $builder);
        self::assertSame('https://user:pass@wiki.php.net:8080/rf:c/uri_followup?a=1&b=2#uri_building', $builder->build()->toRawString());
    }

    public function test_it_fails_to_build_a_new_uri_if_the_user_info_is_present_and_the_host_is_missing(): void
    {
        $this->expectException(InvalidUriException::class);

        (new UriBuilder())
            ->setScheme('https')
            ->setUserInfo('user:pass')
            ->setPathSegments(['rf:c', 'uri_followup'])
            ->setQuery('a=1&b=2')
            ->setFragment('uri_building')
            ->build();
    }

    public function test_it_fails_to_build_a_new_uri_if_the_port_is_present_and_the_host_is_missing(): void
    {
        $this->expectException(InvalidUriException::class);

        (new UriBuilder())
            ->setScheme('https')
            ->setPort(8080)
            ->setPathSegments(['rf:c', 'uri_followup'])
            ->setQuery('a=1&b=2')
            ->setFragment('uri_building')
            ->build();
    }

    public function test_it_fails_to_build_a_new_uri_if_the_scheme_is_missing_and_the_path_contains_colone_before_a_slash(): void
    {
        $this->expectException(InvalidUriException::class);

        (new UriBuilder())
            ->setPathSegments(['rf:c', 'uri_followup'])
            ->setQuery('a=1&b=2')
            ->setFragment('uri_building')
            ->build();
    }

    public function test_it_fails_if_the_path_contains_invalid_characters(): void
    {
        $this->expectException(InvalidUriException::class);

        (new UriBuilder())->setPathSegments(['rfc', 'uri_fòllowup'])->build();
    }

    public function test_it_prepend_the_path_when_there_is_too_many_slashes(): void
    {
        $uri = (new UriBuilder())->setPathSegments(['', '', ''])->build();

        self::assertSame('/.//', $uri->getPath());
    }

    public function test_building_without_calling_any_setter(): void
    {
        self::assertSame('', (new UriBuilder())->build()->toString());
    }

    public function test_building_with_a_simple_path(): void
    {
        self::assertSame('rfc', (new UriBuilder())->setPath('rfc')->build()->toString());
    }
}
