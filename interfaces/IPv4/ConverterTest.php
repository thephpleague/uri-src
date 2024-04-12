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

namespace League\Uri\IPv4;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Converter::class)]
final class ConverterTest extends TestCase
{
    #[DataProvider('providerHost')]
    public function testParseWithAutoDetectCalculator(?string $input, ?string $expected): void
    {
        self::assertEquals($expected, Converter::fromEnvironment()->toDecimal($input) ?? $input);
    }

    #[DataProvider('providerHost')]
    public function testConvertToDecimal(string $input, string $decimal, string $octal, string $hexadecimal): void
    {
        self::assertSame($octal, Converter::fromGMP()->toOctal($input));
        self::assertSame($octal, Converter::fromNative()->toOctal($input));
        self::assertSame($octal, Converter::fromBCMath()->toOctal($input));

        self::assertSame($decimal, Converter::fromGMP()->toDecimal($input));
        self::assertSame($decimal, Converter::fromNative()->toDecimal($input));
        self::assertSame($decimal, Converter::fromBCMath()->toDecimal($input));

        self::assertSame($hexadecimal, Converter::fromGMP()->toHexadecimal($input));
        self::assertSame($hexadecimal, Converter::fromNative()->toHexadecimal($input));
        self::assertSame($hexadecimal, Converter::fromBCMath()->toHexadecimal($input));
        self::assertTrue(Converter::fromEnvironment()->isIpv4($input));
    }

    public static function providerHost(): array
    {
        return [
            '0 host' => ['0', '0.0.0.0', '0000.0000.0000.0000', '0x0000'],
            'normal IP' => ['192.168.0.1', '192.168.0.1', '0300.0250.0000.0001',  '0xc0a801'],
            'normal IP ending with a dot' => ['192.168.0.1.', '192.168.0.1', '0300.0250.0000.0001',  '0xc0a801'],
            'octal (1)' => ['030052000001', '192.168.0.1', '0300.0250.0000.0001',  '0xc0a801'],
            'octal (2)' => ['0300.0250.0000.0001', '192.168.0.1', '0300.0250.0000.0001',  '0xc0a801'],
            'hexadecimal (1)' => ['0x', '0.0.0.0', '0000.0000.0000.0000', '0x0000'],
            'hexadecimal (2)' => ['0xffffffff', '255.255.255.255', '0377.0377.0377.0377', '0xffffffff'],
            'decimal (1)' => ['3232235521', '192.168.0.1', '0300.0250.0000.0001',  '0xc0a801'],
            'decimal (2)' => ['999999999', '59.154.201.255', '0073.0232.0311.0377', '0x3b9ac9ff'],
            'decimal (3)' => ['256', '0.0.1.0', '0000.0000.0001.0000', '0x0010'],
            'decimal (4)' => ['192.168.257', '192.168.1.1', '0300.0250.0001.0001', '0xc0a811'],
        ];
    }

    #[DataProvider('providerInvalidHost')]
    public function testParseWithInvalidHost(?string $input): void
    {
        self::assertNull(Converter::fromBCMath()->toHexadecimal($input));
        self::assertNull(Converter::fromNative()->toOctal($input));
        self::assertNull(Converter::fromGMP()->toDecimal($input));
        self::assertFalse(Converter::fromEnvironment()->isIpv4($input));
    }

    public static function providerInvalidHost(): array
    {
        return [
            'null host' => [null],
            'empty host' => [''],
            'non ip host' => ['ulb.ac.be'],
            'invalid host (0)' => ['256.256.256.256.256'],
            'invalid host (1)' => ['256.256.256.256'],
            'invalid host (3)' => ['256.256.256'],
            'invalid host (4)' => ['999999999.com'],
            'invalid host (5)' => ['10000000000'],
            'invalid host (6)' => ['192.168.257.com'],
            'invalid host (7)' => ['192..257'],
            'invalid host (8)' => ['0foobar'],
            'invalid host (9)' => ['0xfoobar'],
            'invalid host (10)' => ['0xffffffff1'],
            'invalid host (11)' => ['0300.5200.0000.0001'],
            'invalid host (12)' => ['255.255.256.255'],
            'invalid host (13)' => ['0ffaed'],
            'invalid host (14)' => ['192.168.1.0x3000000'],
        ];
    }
}
