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
use Uri\PathSegments;
use Uri\PathType;

use function dump;
use function serialize;
use function unserialize;

final class PathSegmentsTest extends TestCase
{
    #[DataProvider('validPathProvider')]
    public function test_handling_encoded_path(string $raw, string $expected): void
    {
        self::assertSame($expected, (new PathSegments($raw))->toString());
    }

    public static function validPathProvider(): array
    {
        $unreserved = 'a-zA-Z0-9.-_~!$&\'()*+,;=:@';

        return [
            'empty' => ['', ''],
            'root path' => ['/', '/'],
            'absolute path' => ['/path/to/my/file.csv', '/path/to/my/file.csv'],
            'relative path' => ['you', 'you'],
            'relative path with ending slash' => ['foo/bar/', 'foo/bar/'],
            'path with a space' => ['/shop/rev iew/', '/shop/rev%20iew/'],
            'path with an encoded char in lowercase' => ['/master/toto/a%c2%b1b', '/master/toto/a%C2%B1b'],
            'path with an encoded char in uppercase' => ['/master/toto/%7Eetc', '/master/toto/%7Eetc'],
            'path with character to encode' => ['/foo^bar', '/foo%5Ebar'],
            'path with a reserved char encoded' => ['%2Ffoo^bar', '%2Ffoo%5Ebar'],
            'Percent encode spaces' => ['/pa th', '/pa%20th'],
            'Percent encode multibyte' => ['/€', '/%E2%82%AC'],
            "Don't encode something that's already encoded" => ['/pa%20th', '/pa%20th'],
            'Percent encode invalid percent encodings' => ['/pa%2-th', '/pa%252-th'],
            "Don't encode path segments" => ['/pa/th//two', '/pa/th//two'],
            "Don't encode unreserved chars or sub-delimiters" => ["/$unreserved", "/$unreserved"],
            'Encoded unreserved chars are not decoded' => ['/p%61th', '/p%61th'],
        ];
    }

    public function test_path_segments_getters_method(): void
    {
        $raw = '/toto/master/toto/a%c2%b1b';
        $pathSegments = new PathSegments($raw);

        self::assertCount(4, $pathSegments);
        self::assertSame(PathType::Absolute, $pathSegments->getType());
        self::assertSame('toto', $pathSegments->getFirst());
        self::assertSame('a±b', $pathSegments->getLast());
        self::assertNull($pathSegments->get(42));
        self::assertSame(['toto', 'master', 'toto', 'a±b'], $pathSegments->getAll());
        self::assertSame(0, $pathSegments->getIndexOf('toto'));
        self::assertNull($pathSegments->getIndexOf('foobar'));
        self::assertSame(2, $pathSegments->getLastIndexOf('toto'));
        self::assertNull($pathSegments->getLastIndexOf('foobar'));
        self::assertSame(['toto', 'master', 'toto', 'a±b'], iterator_to_array($pathSegments));
        self::assertTrue($pathSegments->has('master'));
        self::assertFalse($pathSegments->has('a%c2%b1b'));
    }

    public function test_it_can_be_unserialized(): void
    {
        $pathSegmentsA = new PathSegments('/toto/master/toto/a%c2%b1b');
        /** @var PathSegments $pathSegmentsB */
        $pathSegmentsB = unserialize(serialize($pathSegmentsA));

        self::assertSame($pathSegmentsA->toRawString(), $pathSegmentsB->toRawString());
    }

    public function test_it_can_change_its_type(): void
    {
        $pathSegmentsA = new PathSegments('/toto/master/toto/a%c2%b1b');
        self::assertSame(PathType::Absolute, $pathSegmentsA->getType());

        $pathSegmentsB = $pathSegmentsA->withType(PathType::Absolute);
        self::assertSame(PathType::Absolute, $pathSegmentsB->getType());
        self::assertSame($pathSegmentsB->toRawString(), $pathSegmentsB->toRawString());

        $pathSegmentsC = $pathSegmentsB->withType(PathType::Relative);

        self::assertSame('toto/master/toto/a%C2%B1b', $pathSegmentsC->toRawString());
        self::assertNotSame($pathSegmentsB->toRawString(), $pathSegmentsC->toRawString());
    }

    public function test_it_can_change_the_full_segments(): void
    {
        $pathSegmentsA = new PathSegments('/toto/master/toto/a%c2%b1b');
        self::assertSame(['toto', 'master', 'toto', 'a±b'], $pathSegmentsA->getAll());

        $pathSegmentsB = $pathSegmentsA->withSegments(['toto', 'master', 'toto', 'a±b']);
        self::assertSame(PathType::Absolute, $pathSegmentsB->getType());
        self::assertSame($pathSegmentsB->toRawString(), $pathSegmentsB->toRawString());

        $pathSegmentsC = $pathSegmentsB->withSegments(['path', 'is changed', 'now']);
        self::assertSame(PathType::Absolute, $pathSegmentsC->getType());
        self::assertNotSame($pathSegmentsB->toRawString(), $pathSegmentsC->toRawString());

        $pathSegmentsE = (new PathSegments(''))->withSegments(['', 'path', 'is changed', 'now']);
        self::assertSame(PathType::Relative, $pathSegmentsE->getType());
        self::assertNotSame($pathSegmentsE->toRawString(), $pathSegmentsC->toRawString());
    }

    public function test_it_handles_adding_segments_containing_a_slash(): void
    {
        $pathSegments = (new PathSegments(''))->withSegments(['path', 'is changed', 'no/w']);

        self::assertCount(3, $pathSegments);
        self::assertSame('no%2Fw', $pathSegments->getLast());
        self::assertSame('path/is%20changed/no%2Fw', $pathSegments->toRawString());
        self::assertSame('path/is%20changed/no%2Fw', $pathSegments->toString());
        self::assertSame([
            'type' => PathType::Relative,
            'segments' => ['path', 'is changed', 'no%2Fw'],
        ], $pathSegments->__debugInfo());
    }
}
