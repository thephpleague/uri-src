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

namespace League\Uri\Components\Directives;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextDirective::class)]
final class TextDirectiveTest extends TestCase
{
    #[DataProvider('provideValidFragmentTextDirectives')]
    public function testToString(TextDirective $fragmentTextDirective, string $expected): void
    {
        self::assertSame($expected, (string) $fragmentTextDirective);
    }

    #[DataProvider('provideValidFragmentTextDirectives')]
    public function test_it_can_be_created_from_string(TextDirective $fragmentTextDirective, string $expected): void
    {
        self::assertEquals($fragmentTextDirective, TextDirective::fromString($expected));
    }

    public function testToStringEncodesSpecialCharacters(): void
    {
        $fragmentTextDirective = new TextDirective('st&rt', 'e,nd', 'prefix-', '-&suffix');

        self::assertSame('text=prefix%2D-,st%26rt,e%2Cnd,-%2D%26suffix', (string) $fragmentTextDirective);
    }

    public static function provideValidFragmentTextDirectives(): iterable
    {
        yield [new TextDirective('start'), 'text=start'];
        yield [new TextDirective('start', 'end'), 'text=start,end'];
        yield [new TextDirective('start', 'end', 'prefix'), 'text=prefix-,start,end'];
        yield [new TextDirective('start', 'end', 'prefix', 'suffix'), 'text=prefix-,start,end,-suffix'];
        yield [new TextDirective('start', prefix: 'prefix', suffix: 'suffix'), 'text=prefix-,start,-suffix'];
        yield [new TextDirective('start', suffix: 'suffix'), 'text=start,-suffix'];
        yield [new TextDirective('start', prefix: 'prefix'), 'text=prefix-,start'];
    }

    public static function testClasswithers(): void
    {
        $directive = (new TextDirective('foo'))
            ->startingOn('start')
            ->startingOn('start')
            ->endingOn('end')
            ->endingOn('end')
            ->trailedBy('suffix')
            ->trailedBy('suffix')
            ->leadBy('prefix')
            ->leadBy('prefix');

        self::assertSame('start', $directive->start);
        self::assertSame('end', $directive->end);
        self::assertSame('prefix', $directive->prefix);
        self::assertSame('suffix', $directive->suffix);
    }
}
