<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Uri;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;
use TypeError;
use ValueError;

use function tmpfile;

use const PHP_QUERY_RFC1738;
use const PHP_QUERY_RFC3986;

final class QueryBuilderTest extends TestCase
{
    /**
     * @param non-empty-string $separator
     */
    #[DataProvider('providesVariablesToCompose')]
    public function test_it_can_compose_a_query_string(
        object|array $variable,
        string $separator,
        int $encoding,
        QueryBuildingMode $mode,
        ?string $expected
    ): void {
        self::assertSame($expected, QueryBuilder::build($variable, $separator, $encoding, $mode));
    }

    public static function providesVariablesToCompose(): iterable
    {
        yield 'empty string if the variable is empty' => [
            'variable' => [],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => '',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'null if the variable is empty' => [
            'variable' => [],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => null,
            'mode' => QueryBuildingMode::Strict,
        ];

        yield 'null if the object properties are not accessible' => [
            'variable' => new stdClass(),
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => '',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'basic encoding from php-src tests 1' => [
            'variable' =>  ['foo' => 'bar', 'baz' => 1, 'test' => "a ' \" ", 'abc', 'float' => 10.42, 'true' => true, 'false' => false],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => 'foo=bar&baz=1&test=a+%27+%22+&0=abc&float=10.42&true=1&false=0',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'basic encoding from php-src tests 2 - with a different separator' => [
            'variable' =>  ['foo' => 'bar', 'baz' => 1, 'test' => "a ' \" ", 'abc', 'float' => 10.42, 'true' => true, 'false' => false],
            'separator' => ';',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => 'foo=bar;baz=1;test=a+%27+%22+;0=abc;float=10.42;true=1;false=0',
            'mode' => QueryBuildingMode::Default,
        ];

        $data = new class () implements Stringable {
            public string $public = 'input';
            protected string $protected = 'hello';
            private string $private = 'world';
            public function __toString(): string
            {
                return $this->private;
            }
        };

        yield 'basic encoding from php-src tests 3 - with object' => [
            'variable' =>  $data,
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => 'public=input',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'basic encoding from php-src tests 3 - with null object' => [
            'variable' =>  new stdClass(),
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => '',
            'mode' => QueryBuildingMode::Default,
        ];

        $data = new class () implements Stringable {
            public function __toString(): string
            {
                return 'Stringable';
            }
        };

        yield 'basic encoding from php-src tests 4 - with stringable object without public property' => [
            'variable' =>  ['hello', $data],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => '0=hello',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'basic encoding from php-src tests 5 -  with stringable object without public property' => [
            'variable' =>  $data,
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => '',
            'mode' => QueryBuildingMode::Default,
        ];

        $o = new class () {
            public mixed $public = 'input';
        };
        $nested = clone $o;
        $o->public = $nested;

        yield 'basic encoding from php-src tests 6 - nested object' => [
            'variable' =>  $o,
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => 'public%5Bpublic%5D=input',
            'mode' => QueryBuildingMode::Default,
        ];

        $obj = new stdClass();
        $obj->name = 'homepage';
        $obj->page = 1;
        $obj->sort = 'desc,name';

        yield 'basic encoding from php-src tests 7 - stdClass' => [
            'variable' =>  $obj,
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => 'name=homepage&page=1&sort=desc%2Cname',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'basic encoding from php-src tests 8 - array' => [
            'variable' =>  [
                20,
                5 => 13,
                '9' => [
                    1 => 'val1',
                    3 => 'val2',
                    'string' => 'string',
                ],
                'name' => 'homepage',
                'page' => 10,
                'sort' => [
                    'desc',
                    'admin' => [
                        'admin1',
                        'admin2' => [
                            'who' => 'admin2',
                            2 => 'test',
                        ],
                    ],
                ],
            ],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => '0=20&5=13&9%5B1%5D=val1&9%5B3%5D=val2&9%5Bstring%5D=string&name=homepage&page=10&sort%5B0%5D=desc&sort%5Badmin%5D%5B0%5D=admin1&sort%5Badmin%5D%5Badmin2%5D%5Bwho%5D=admin2&sort%5Badmin%5D%5Badmin2%5D%5B2%5D=test',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'basic encoding from php-src tests 8 - array with rfc1738 encoding' => [
            'variable' =>  [
                'name' => 'main page',
                'sort' => 'desc,admin',
                'equation' => '10 + 10 - 5',
            ],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC1738,
            'expected' => 'name=main+page&sort=desc%2Cadmin&equation=10+%2B+10+-+5',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'basic encoding from php-src tests 8 - array with rfc3986 encoding' => [
            'variable' =>  [
                'name' => 'main page',
                'sort' => 'desc,admin',
                'equation' => '10 + 10 - 5',
            ],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC3986,
            'expected' => 'name=main%20page&sort=desc%2Cadmin&equation=10%20%2B%2010%20-%205',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'basic encoding from php-src tests 8 - with null in default mode' => [
            'variable' =>  [null],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC3986,
            'expected' => '',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'basic encoding from php-src tests 8 - with null in conservative mode' => [
            'variable' =>  [null],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC3986,
            'expected' => '0',
            'mode' => QueryBuildingMode::Strict,
        ];

        $v = 'value';
        $ref = &$v;

        yield 'basic encoding from php-src tests 8 - with reference' => [
            'variable' =>  [$ref],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC3986,
            'expected' => '0=value',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'bug resolution in php-src tests 9 - float conversion' => [
            'variable' => ['x' => 1E+14, 'y' => '1E+14'],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC3986,
            'expected' => 'x=1.0E%2B14&y=1E%2B14',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'basic encoding from php-src tests backed enum' => [
            'variable' => ['backed' => BackedEnum::Two],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC3986,
            'expected' => 'backed%5Bname%5D=Two&backed%5Bvalue%5D=Kabiri',
            'mode' => QueryBuildingMode::Default,
        ];

        yield 'basic encoding from php-src tests backed enum in modern handled form' => [
            'variable' => ['backed' => BackedEnum::Two],
            'separator' => '&',
            'encoding' => PHP_QUERY_RFC3986,
            'expected' => 'backed=Kabiri',
            'mode' => QueryBuildingMode::HandleEnums,
        ];
    }

    public function test_it_throws_if_a_object_recursion_is_detected(): void
    {
        $recursive = new class () {
            public mixed $public = 'input';
        };

        $recursive->public = $recursive;

        self::assertSame('', QueryBuilder::build($recursive, queryBuildingMode: QueryBuildingMode::Default));
        self::assertSame('', QueryBuilder::build($recursive, queryBuildingMode: QueryBuildingMode::Legacy));
        self::assertSame('', QueryBuilder::build($recursive, queryBuildingMode: QueryBuildingMode::HandleEnums));

        $this->expectException(ValueError::class);
        self::assertSame('', QueryBuilder::build($recursive, queryBuildingMode: QueryBuildingMode::Strict));
    }

    public function test_it_throws_if_a_array_recursion_is_detected(): void
    {
        $recursive = [];
        $recursive['self'] = &$recursive;

        self::assertSame('', QueryBuilder::build($recursive, queryBuildingMode: QueryBuildingMode::Default));
        self::assertSame('', QueryBuilder::build($recursive, queryBuildingMode: QueryBuildingMode::Legacy));
        self::assertSame('', QueryBuilder::build($recursive, queryBuildingMode: QueryBuildingMode::HandleEnums));

        $this->expectException(ValueError::class);
        self::assertSame('', QueryBuilder::build($recursive, queryBuildingMode: QueryBuildingMode::Strict));
    }

    public function test_it_throws_if_a_resource_is_present(): void
    {
        $tmpfile = [tmpfile()];
        self::assertSame('', QueryBuilder::build($tmpfile, queryBuildingMode: QueryBuildingMode::Default));
        self::assertSame('', QueryBuilder::build($tmpfile, queryBuildingMode: QueryBuildingMode::Legacy));
        self::assertSame('', QueryBuilder::build($tmpfile, queryBuildingMode: QueryBuildingMode::HandleEnums));

        $this->expectException(TypeError::class);

        QueryBuilder::build($tmpfile, queryBuildingMode: QueryBuildingMode::Strict);
    }

    public function test_it_throws_if_a_non_backed_enum_is_given(): void
    {
        $this->expectException(TypeError::class);

        QueryBuilder::build(['pure' => PureEnum::One], queryBuildingMode: QueryBuildingMode::HandleEnums);
    }
}

enum PureEnum
{
    case One;
    case Two;
}

enum BackedEnum: string
{
    case One = 'Rimwe';
    case Two = 'Kabiri';
}
