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
use function base64_encode;
use function dirname;
use function file_get_contents;

/**
 * @group path
 * @group datapath
 * @coversDefaultClass \League\Uri\Components\DataPath
 */
final class DataPathTest extends TestCase
{
    private string $rootPath;

    public function setUp(): void
    {
        $this->rootPath = dirname(__DIR__, 2).'/test_files';
    }

    public function testIsAbsolute(): void
    {
        $path = DataPath::new(';,Bonjour%20le%20monde!');

        self::assertFalse($path->isAbsolute());
    }

    public function testWithoutDotSegments(): void
    {
        $path = DataPath::new(';,Bonjour%20le%20monde%21');

        self::assertEquals($path, $path->withoutDotSegments());
    }

    public function testWithLeadingSlash(): void
    {
        $this->expectException(SyntaxError::class);

        DataPath::new(';,Bonjour%20le%20monde%21')->withLeadingSlash();
    }

    public function testWithoutLeadingSlash(): void
    {
        $path = DataPath::new(';,Bonjour%20le%20monde%21');

        self::assertEquals($path, $path->withoutLeadingSlash());
    }

    public function testConstructorFailedMalformePath(): void
    {
        $this->expectException(SyntaxError::class);

        DataPath::new('€');
    }

    /**
     * @dataProvider invalidDataUriPath
     */
    public function testCreateFromPathFailed(string $path): void
    {
        $this->expectException(SyntaxError::class);

        DataPath::fromFileContents($path);
    }

    /**
     * @dataProvider invalidDataUriPath
     */
    public function testConstructorFailed(string $path): void
    {
        $this->expectException(SyntaxError::class);

        DataPath::new($path);
    }

    public static function invalidDataUriPath(): array
    {
        return [
            'invalid format' => ['/usr/bin/yeah'],
        ];
    }

    /**
     * @dataProvider validPathContent
     */
    public function testDefaultConstructor(string $path, string $expected): void
    {
        self::assertSame($expected, DataPath::new($path)->toString());
    }

    public static function validPathContent(): array
    {
        return [
            [
                'path' => 'text/plain;,',
                'expected' => 'text/plain;charset=us-ascii,',
            ],
            [
                'path' => ',',
                'expected' => 'text/plain;charset=us-ascii,',
            ],
            [
                'path' => '',
                'expected' => 'text/plain;charset=us-ascii,',
            ],
        ];
    }

    /**
     * @dataProvider validFilePath
     */
    public function testCreateFromPath(string $path, string $mimetype, string $mediatype): void
    {
        $uri = DataPath::fromFileContents($path);

        self::assertSame($mimetype, $uri->getMimeType());
        self::assertSame($mediatype, $uri->getMediaType());
    }

    public static function validFilePath(): array
    {
        $rootPath = dirname(__DIR__, 2).'/test_files';

        return [
            'text file' => [$rootPath.'/hello-world.txt', 'text/plain', 'text/plain;charset=us-ascii'],
            'img file' => [$rootPath.'/red-nose.gif', 'image/gif', 'image/gif;charset=binary'],
        ];
    }

    public function testWithParameters(): void
    {
        $uri = DataPath::new('text/plain;charset=us-ascii,Bonjour%20le%20monde%21');
        $newUri = $uri->withParameters('charset=us-ascii');

        self::assertSame($newUri, $uri);
    }

    public function testWithParametersOnBinaryData(): void
    {
        $expected = 'charset=binary;foo=bar';
        $uri = DataPath::fromFileContents($this->rootPath.'/red-nose.gif');
        $newUri = $uri->withParameters($expected);

        self::assertSame($expected, $newUri->getParameters());
    }

    /**
     * @dataProvider invalidParametersString
     */
    public function testWithParametersFailedWithInvalidParameters(string $path, string $parameters): void
    {
        $this->expectException(SyntaxError::class);

        DataPath::fromFileContents($path)->withParameters($parameters);
    }

    public static function invalidParametersString(): array
    {
        return [
            [
                'path' => __DIR__.'/data/red-nose.gif',
                'parameters' => 'charset=binary;base64',
            ],
            [
                'path' => __DIR__.'/data/hello-world.txt',
                'parameters' => 'charset=binary;base64;foo=bar',
            ],
        ];
    }

    /**
     * @dataProvider fileProvider
     */
    public function testToBinary(DataPath $uri): void
    {
        self::assertTrue($uri->toBinary()->isBinaryData());
    }

    /**
     * @dataProvider fileProvider
     */
    public function testToAscii(DataPath $uri): void
    {
        self::assertFalse($uri->toAscii()->isBinaryData());
    }

    public static function fileProvider(): array
    {
        $rootPath = dirname(__DIR__, 2).'/test_files';

        return [
            'with a file' => [DataPath::fromFileContents($rootPath.'/red-nose.gif')],
            'with a text' => [DataPath::new('text/plain;charset=us-ascii,Bonjour%20le%20monde%21')],
        ];
    }

    /**
     * @dataProvider invalidParameters
     */
    public function testUpdateParametersFailed(string $parameters): void
    {
        $this->expectException(SyntaxError::class);
        $uri = DataPath::new('text/plain;charset=us-ascii,Bonjour%20le%20monde%21');
        $uri->withParameters($parameters);
    }

    public static function invalidParameters(): array
    {
        return [
            'cannot modify binary flag' => ['base64=3'],
            'cannot add non empty flag' => ['image/jpg'],
        ];
    }

    public function testBinarySave(): void
    {
        $newFilePath = $this->rootPath.'/temp.gif';
        $uri = DataPath::fromFileContents($this->rootPath.'/red-nose.gif');
        $res = $uri->save($newFilePath);

        self::assertSame($uri->toString(), DataPath::fromFileContents($newFilePath)->toString());

        // Ensure file handle of \SplFileObject gets closed.
        unset($res);
        unlink($newFilePath);
    }

    public function testRawSave(): void
    {
        $context = stream_context_create([
            'http'=> [
                'method' => 'GET',
                'header' => "Accept-language: en\r\nCookie: foo=bar\r\n",
            ],
        ]);

        $newFilePath = $this->rootPath.'/temp.txt';
        $uri = DataPath::fromFileContents($this->rootPath.'/hello-world.txt', $context);

        $res = $uri->save($newFilePath);
        self::assertSame((string) $uri, (string) DataPath::fromFileContents($newFilePath));
        $data = file_get_contents($newFilePath);
        self::assertSame(base64_encode((string) $data), $uri->getData());

        // Ensure file handle of \SplFileObject gets closed.
        unset($res);
        unlink($newFilePath);
    }

    public function testDataPathConstructor(): void
    {
        self::assertSame('text/plain;charset=us-ascii,', (string) DataPath::new());
    }

    public function testInvalidBase64Encoded(): void
    {
        $this->expectException(SyntaxError::class);

        DataPath::new('text/plain;charset=us-ascii;base64,boulook%20at%20me');
    }

    public function testInvalidComponent(): void
    {
        $this->expectException(SyntaxError::class);

        DataPath::new("data:text/plain;charset=us-ascii,bou\nlook%20at%20me");
    }

    public function testInvalidString(): void
    {
        $this->expectException(SyntaxError::class);

        DataPath::new('text/plain;boulook€');
    }

    public function testInvalidMimetype(): void
    {
        $this->expectException(SyntaxError::class);

        DataPath::new('data:toto\\bar;foo=bar,');
    }


    /**
     * @dataProvider getURIProvider
     */
    public function testCreateFromUri(Psr7UriInterface|UriInterface $uri, ?string $expected): void
    {
        $path = DataPath::fromUri($uri);

        self::assertSame($expected, $path->value());
    }

    public static function getURIProvider(): iterable
    {
        return [
            'PSR-7 URI object' => [
                'uri' => Http::new('data:text/plain;charset=us-ascii,Bonjour%20le%20monde%21'),
                'expected' => 'text/plain;charset=us-ascii,Bonjour%20le%20monde%21',
            ],
            'PSR-7 URI object with no path' => [
                'uri' => Http::new(),
                'expected' => 'text/plain;charset=us-ascii,',
            ],
            'League URI object' => [
                'uri' => Uri::new('data:text/plain;charset=us-ascii,Bonjour%20le%20monde%21'),
                'expected' => 'text/plain;charset=us-ascii,Bonjour%20le%20monde%21',
            ],
            'League URI object with no path' => [
                'uri' => Uri::new(),
                'expected' => 'text/plain;charset=us-ascii,',
            ],
        ];
    }

    public function testHasTrailingSlash(): void
    {
        self::assertFalse(DataPath::new('text/plain;charset=us-ascii,')->hasTrailingSlash());
    }

    public function testWithTrailingSlash(): void
    {
        $path = DataPath::new('text/plain;charset=us-ascii,')->withTrailingSlash();

        self::assertSame('text/plain;charset=us-ascii,/', (string) $path);
        self::assertSame($path, $path->withTrailingSlash());
    }

    public function testWithoutTrailingSlash(): void
    {
        $path = DataPath::new('text/plain;charset=us-ascii,/')->withoutTrailingSlash();

        self::assertSame('text/plain;charset=us-ascii,', (string) $path);
        self::assertSame($path, $path->withoutTrailingSlash());
    }

    public function testDecoded(): void
    {
        $encodedPath = 'text/plain;charset=us-ascii,Bonjour%20le%20monde%21';
        $decodedPath = 'text/plain;charset=us-ascii,Bonjour le monde%21';
        $path = DataPath::new($encodedPath);

        self::assertSame($encodedPath, $path->value());
        self::assertSame($decodedPath, $path->decoded());
    }
}
