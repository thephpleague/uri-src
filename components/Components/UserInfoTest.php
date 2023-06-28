<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Uri\Components;

use League\Uri\Contracts\UriInterface;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\Http;
use League\Uri\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;

/**
 * @group userinfo
 * @coversDefaultClass \League\Uri\Components\UserInfo
 */
final class UserInfoTest extends TestCase
{
    /**
     * @dataProvider userInfoProvider
     */
    public function testConstructor(
        Stringable|string|null $user,
        Stringable|string|null $pass,
        ?string $expected_user,
        ?string $expected_pass,
        string $expected_str,
        string $uriComponent
    ): void {
        $userinfo = new UserInfo($user, $pass);
        self::assertSame($expected_user, $userinfo->getUser());
        self::assertSame($expected_pass, $userinfo->getPass());
        self::assertSame($expected_str, (string) $userinfo);
        self::assertSame($uriComponent, $userinfo->getUriComponent());
    }

    public static function userInfoProvider(): array
    {
        return [
            'using stringable object' => [
                'user' => new UserInfo('login'),
                'pass' => new UserInfo('pass'),
                'expected_user' => 'login',
                'expected_pass' => 'pass',
                'expected_str' => 'login:pass',
                'uriComponent' => 'login:pass@',
            ],
            'using strings' => [
                'user' => 'login',
                'pass' => 'pass',
                'expected_user' => 'login',
                'expected_pass' => 'pass',
                'expected_str' => 'login:pass',
                'uriComponent' => 'login:pass@',
            ],
            'using encoded string for username' => [
                'user' => 'login%61',
                'pass' => 'pass',
                'expected_user' => 'login%61',
                'expected_pass' => 'pass',
                'expected_str' => 'login%61:pass',
                'uriComponent' => 'login%61:pass@',
            ],
            'with an undefined password' => [
                'user' => 'login',
                'pass' => null,
                'expected_user' => 'login',
                'expected_pass' => null,
                'expected_str' => 'login',
                'uriComponent' => 'login@',
            ],
            'with an undefined username and password' => [
                'user' => null,
                'pass' => null,
                'expected_user' => null,
                'expected_pass' => null,
                'expected_str' => '',
                'uriComponent' => '',
            ],
            'with an undefined password and an empty string as the username' => [
                'user' => '',
                'pass' => null,
                'expected_user' => '',
                'expected_pass' => null,
                'expected_str' => '',
                'uriComponent' => '@',
            ],
            'with empty strings' => [
                'user' => '',
                'pass' => '',
                'expected_user' => '',
                'expected_pass' => '',
                'expected_str' => ':',
                'uriComponent' => ':@',
            ],
            'with encoded username and password' => [
                'user' => 'foò',
                'pass' => 'bar',
                'expected_user' => 'foò',
                'expected_pass' => 'bar',
                'expected_str' => 'fo%C3%B2:bar',
                'uriComponent' => 'fo%C3%B2:bar@',
            ],
            'with encoded username and password containing + sign' => [
                'user' => 'fo+o',
                'pass' => 'ba+r',
                'expected_user' => 'fo+o',
                'expected_pass' => 'ba+r',
                'expected_str' => 'fo+o:ba+r',
                'uriComponent' => 'fo+o:ba+r@',
            ],
        ];
    }

    public static function createFromStringProvider(): array
    {
        return [
            'simple' => [null, 'user:pass', 'user', 'pass', 'user:pass'],
            'empty password' => [null, 'user:', 'user', '', 'user:'],
            'no password' => [null, 'user', 'user', null, 'user'],
            'no login but has password' => [null, ':pass', '', null, ''],
            'empty all' => [null, '', '', null, ''],
            'null content' => [null, null, null, null, ''],
            'component interface' => [null, new UserInfo('user', 'pass'), 'user', 'pass', 'user:pass'],
            'reset object' => ['login', new UserInfo(null), null, null, ''],
            'encoded chars 1' => [null, 'foo%40bar:bar%40foo', 'foo@bar', 'bar@foo', 'foo%40bar:bar%40foo'],
            'encoded chars 3' => [null, 'foo%a1bar:bar%40foo', 'foo%A1bar', 'bar@foo', 'foo%A1bar:bar%40foo'],
            'encoded chars 2' => [null, "user:'O=+9zLZ%7d%25%7bz+:tC", 'user', "'O=+9zLZ}%{z+:tC", "user:'O=+9zLZ%7D%25%7Bz+:tC"],
        ];
    }

    public function testWithContentReturnSameInstance(): void
    {
        self::assertEquals(
            new UserInfo('user', 'pass'),
            UserInfo::new('user:pass')
        );
    }

    /**
     * @dataProvider withUserInfoProvider
     */
    public function testWithUserInfo(?string $user, ?string $pass, ?string $expected): void
    {
        self::assertSame($expected, UserInfo::new()->withUser($user)->withPass($pass)->toString());
    }

    public static function withUserInfoProvider(): array
    {
        return [
            'simple' => ['user', 'pass', 'user:pass'],
            'empty password' => ['user', '', 'user:'],
            'no password' => ['user', null, 'user'],
            'no login but has password' => ['', 'pass', ':pass'],
            'empty all' => ['', '', ':'],
            'null all' => [null, null, ''],
        ];
    }

    public function testItWillThrowIfWeAttemptToModifyAPasswordOnANullUser(): void
    {
        $this->expectException(SyntaxError::class);

        UserInfo::new()->withPass('toto');
    }

    public function testConstructorThrowsException(): void
    {
        $this->expectException(SyntaxError::class);

        new UserInfo("\0");
    }

    /**
     * @dataProvider getURIProvider
     */
    public function testCreateFromUri(UriInterface|Psr7UriInterface $uri, ?string $expected): void
    {
        $userInfo = UserInfo::fromUri($uri);

        self::assertSame($expected, $userInfo->value());
    }

    public static function getURIProvider(): iterable
    {
        return [
            'PSR-7 URI object' => [
                'uri' => Http::new('http://foo:bar@example.com?foo=bar'),
                'expected' => 'foo:bar',
            ],
            'PSR-7 URI object with no user info' => [
                'uri' => Http::new('path/to/the/sky?foo'),
                'expected' => null,
            ],
            'PSR-7 URI object with empty string user info' => [
                'uri' => Http::new('http://@example.com?foo=bar'),
                'expected' => null,
            ],
            'League URI object' => [
                'uri' => Uri::new('http://foo:bar@example.com?foo=bar'),
                'expected' => 'foo:bar',
            ],
            'League URI object with no user info' => [
                'uri' => Uri::new('path/to/the/sky?foo'),
                'expected' => null,
            ],
            'League URI object with empty string user info' => [
                'uri' => Uri::new('http://@example.com?foo=bar'),
                'expected' => '',
            ],
            'URI object with encoded user info string' => [
                'uri' => Uri::new('http://login%af:bar@example.com:81'),
                'expected' => 'login%AF:bar',
            ],
        ];
    }

    public function testCreateFromAuthorityWithoutUserInfoComponent(): void
    {
        $uri = Uri::new('http://example.com:443');
        $auth = Authority::fromUri($uri);

        self::assertEquals(UserInfo::fromUri($uri), UserInfo::fromAuthority($auth));
    }

    public function testCreateFromAuthorityWithActualUserInfoComponent(): void
    {
        $uri = Uri::new('http://user:pass@example.com:443');
        $auth = Authority::fromUri($uri);

        self::assertEquals(UserInfo::fromUri($uri), UserInfo::fromAuthority($auth));
    }

    public function testItFailsToCreateANewInstanceWhenTheUsernameIsUndefined(): void
    {
        $this->expectException(SyntaxError::class);

        new UserInfo(null, 'password');
    }
}
