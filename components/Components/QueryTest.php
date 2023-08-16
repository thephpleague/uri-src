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

use ArrayIterator;
use League\Uri\Contracts\UriInterface;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\Http;
use League\Uri\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;
use function json_encode;

/**
 * @group query
 * @coversDefaultClass \League\Uri\Components\Query
 */
final class QueryTest extends TestCase
{
    protected Query $query;

    protected function setUp(): void
    {
        $this->query = Query::new('kingkong=toto');
    }

    public function testSeparator(): void
    {
        $query = Query::new('foo=bar&kingkong=toto');
        $newQuery = $query->withSeparator('|');
        self::assertSame('&', $query->getSeparator());
        self::assertSame('|', $newQuery->getSeparator());
        self::assertSame('foo=bar|kingkong=toto', $newQuery->value());

        $this->expectException(SyntaxError::class);
        $newQuery->withSeparator('');
    }

    public function testIterator(): void
    {
        $query = Query::new('a=1&b=2&c=3&a=4');

        $keys = [];
        $values = [];
        foreach ($query as $pair) {
            $keys[] = $pair[0];
            $values[] = $pair[1];
        }
        self::assertSame(['a', 'b', 'c', 'a'], $keys);
        self::assertSame(['1', '2', '3', '4'], $values);

        $keysp = [];
        $valuesp = [];
        foreach ($query->pairs() as $key => $value) {
            $keysp[] = $key;
            $valuesp[] = $value;
        }

        self::assertSame(['a', 'b', 'c', 'a'], $keysp);
        self::assertSame(['1', '2', '3', '4'], $valuesp);
    }

    public function testJsonEncode(): void
    {
        self::assertSame(
            '"a=1&b=2&c=3&a=4&a=3+d"',
            json_encode(Query::new('a=1&b=2&c=3&a=4&a=3%20d'))
        );
    }

    public function testGetUriComponent(): void
    {
        self::assertSame('', Query::new()->getUriComponent());
        self::assertSame('?', Query::new('')->getUriComponent());
        self::assertSame('?foo=bar', Query::new('foo=bar')->getUriComponent());
    }

    public function testCreateFromPairsWithIterable(): void
    {
        /** @var iterable<int, array{0:string, 1:string|null}> $iterable */
        $iterable = (function (): iterable {
            $data = [['john', 'doe the john'], ['john', null]];

            foreach ($data as $offset => $value) {
                yield $offset => $value;
            }
        })();

        self::assertCount(2, Query::fromPairs($iterable));
    }

    public function testcreateFromPairsWithQueryObject(): void
    {
        $query = Query::new('a=1&b=2');
        self::assertEquals($query, Query::fromPairs($query));
    }

    public function testCreateFromPairsFailedWithBadIterable(): void
    {
        $this->expectException(SyntaxError::class);

        Query::fromPairs([['toto' => ['foo' => [(object) []]]]]); /* @phpstan-ignore-line */
    }

    public function testNormalization(): void
    {
        self::assertSame('foo=bar', Query::new('foo=bar&&&=&&&&&&')->withoutEmptyPairs()->value());
        self::assertNull(Query::new('&=bar&=')->withoutEmptyPairs()->toRFC1738());
        self::assertNull(Query::new('&&&&&&&&&&&')->withoutEmptyPairs()->toRFC1738());
        self::assertSame($this->query, $this->query->withoutEmptyPairs());
    }

    /**
     * @dataProvider validAppendValue
     */
    public function testAppend(?string $query, Stringable|string|null $appendData, ?string $expected): void
    {
        self::assertSame($expected, Query::new($query)->append($appendData)->value());
    }

    public static function validAppendValue(): array
    {
        return [
            ['', 'foo=bar&foo=baz', 'foo=bar&foo=baz'],
            [null, null, null],
            [null, 'foo=bar&foo=baz', 'foo=bar&foo=baz'],
            ['foo=bar&foo=baz', null, 'foo=bar&foo=baz'],
            ['', 'foo=bar', 'foo=bar'],
            ['', 'foo=', 'foo='],
            ['', 'foo', 'foo'],
            ['foo=bar', Query::new('foo=baz'), 'foo=bar&foo=baz'],
            ['foo=bar', 'foo=baz', 'foo=bar&foo=baz'],
            ['foo=bar', 'foo=', 'foo=bar&foo='],
            ['foo=bar', 'foo', 'foo=bar&foo'],
            ['foo=bar', 'foo=baz&foo=yolo', 'foo=bar&foo=baz&foo=yolo'],
            ['foo=bar', '', 'foo=bar'],
            ['foo=bar', 'foo=baz', 'foo=bar&foo=baz'],
            ['foo=bar', '&foo=baz', 'foo=bar&foo=baz'],
            ['&foo=bar', 'foo=baz', 'foo=bar&foo=baz'],
            ['foo=bar&', 'foo=baz&', 'foo=bar&foo=baz'],
            ['&foo=bar', '&foo=baz', 'foo=bar&foo=baz'],
            ['foo=bar&', '&foo=baz', 'foo=bar&foo=baz'],
            ['&foo=bar&', '&foo=baz&', 'foo=bar&foo=baz'],
            ['=toto&foo=bar', 'foo=bar', '=toto&foo=bar&foo=bar'],
        ];
    }

    public function testGetParameter(): void
    {
        $query = Query::new('kingkong=toto&kingkong=barbaz&&=&=b');

        self::assertNull($query->get('togo'));
        self::assertSame([], $query->getAll('togo'));
        self::assertSame('toto', $query->get('kingkong'));
        self::assertNull($query->get(''));
        self::assertSame(['toto', 'barbaz'], $query->getAll('kingkong'));
        self::assertSame([null, '', 'b'], $query->getAll(''));
    }

    public function testHas(): void
    {
        self::assertTrue($this->query->has('kingkong'));
        self::assertFalse($this->query->has('togo'));
    }

    public function testCountable(): void
    {
        self::assertCount(2, Query::new('kingkong=toto&kingkong=barbaz'));
    }

    public function testStringWithoutContent(): void
    {
        $query = Query::new('foo&bar&baz');

        self::assertNull($query->get('foo'));
        self::assertNull($query->get('bar'));
        self::assertNull($query->get('baz'));
    }

    public function testParams(): void
    {
        $query = Query::new('foo[]=bar&foo[]=baz');

        self::assertCount(1, $query->parameters());
        self::assertSame(['bar', 'baz'], $query->parameter('foo'));
        self::assertNull($query->parameter('foo[]'));
    }

    /**
     * @dataProvider withoutPairProvider
     */
    public function testWithoutPair(string $origin, array $without, string $result): void
    {
        self::assertSame($result, (string) Query::new($origin)->withoutPair(...$without));
    }

    public static function withoutPairProvider(): array
    {
        return [
            ['foo&bar&baz&to.go=toofan', ['foo', 'to.go'], 'bar&baz'],
            ['foo&bar&baz&to.go=toofan', ['foo', 'unknown'], 'bar&baz&to.go=toofan'],
            ['foo&bar&baz&to.go=toofan', ['tata', 'query'], 'foo&bar&baz&to.go=toofan'],
            ['a=b&c=d', ['a'], 'c=d'],
            ['a=a&b=b&a=a&c=c', ['a'], 'b=b&c=c'],
            ['a=a&=&b=b&c=c', [''], 'a=a&b=b&c=c'],
            ['a=a&&b=b&c=c', [''], 'a=a&b=b&c=c'],
        ];
    }

    public function testWithoutPairVariadicArgument(): void
    {
        $query = Query::new('foo&bar=baz');

        self::assertSame($query, $query->withoutPair());
    }

    public function testwithoutPairGetterMethod(): void
    {
        $query = Query::new()->appendTo('first', 1);
        self::assertTrue($query->has('first'));
        self::assertSame('1', $query->get('first'));
        $query = $query->withoutPair('first');
        self::assertFalse($query->has('first'));
        $query = $query
            ->appendTo('first', 1)
            ->appendTo('first', 10)
            ->withoutPair('first')
        ;
        self::assertFalse($query->has('first'));
    }

    /**
     * @dataProvider withoutParamProvider
     */
    public function testwithoutParam(array $origin, array $without, string $expected): void
    {
        self::assertSame($expected, Query::fromParameters($origin)->withoutParameters(...$without)->toString());
    }

    public static function withoutParamProvider(): array
    {
        $data = [
            'filter' => [
                'foo' => [
                    'bar',
                    'baz',
                ],
                'bar' => [
                    'bar' => 'foo',
                    'foo' => 'bar',
                ],
            ],
        ];

        return [
            'simple removal' => [
                'origin' => ['foo' => 'bar', 'bar' => 'baz'],
                'without' => ['bar'],
                'expected' => 'foo=bar',
            ],
            'complext removal' => [
                'origin' => [
                    'arr[one' => 'sid',
                    'arr' => ['4' => 'fred'],
                ],
                'without' => ['arr'],
                'expected' => 'arr%5Bone=sid',
            ],
            'nothing to remove' => [
                'origin' => $data,
                'without' => ['filter[dummy]'],
                'expected' => 'filter%5Bfoo%5D%5B0%5D=bar&filter%5Bfoo%5D%5B1%5D=baz&filter%5Bbar%5D%5Bbar%5D=foo&filter%5Bbar%5D%5Bfoo%5D=bar',
            ],
            'remove 2nd level' => [
                'origin' => $data,
                'without' => ['filter[bar]'],
                'expected' => 'filter%5Bfoo%5D%5B0%5D=bar&filter%5Bfoo%5D%5B1%5D=baz',
            ],
            'remove nth level' => [
                'origin' => $data,
                'without' => ['filter[foo][0]', 'filter[bar][bar]'],
                'expected' => 'filter%5Bfoo%5D%5B1%5D=baz&filter%5Bbar%5D%5Bfoo%5D=bar',
            ],
        ];
    }

    public function testWithoutParamDoesNotChangeParamsKey(): void
    {
        $data = [
            'foo' => [
                'bar',
                'baz',
            ],
        ];

        $query = Query::fromParameters($data);
        self::assertSame('foo%5B0%5D=bar&foo%5B1%5D=baz', $query->value());

        self::assertTrue($query->hasParameter('foo'));
        self::assertFalse($query->hasParameter('bar'));
        self::assertFalse($query->hasParameter('foo', 'bar'));

        $newQuery = $query->withoutParameters('foo[0]');

        self::assertSame('foo%5B1%5D=baz', $newQuery->value());
        self::assertSame(['foo' => [1 => 'baz']], $newQuery->parameters());
    }

    public function testWithoutParamVariadicArgument(): void
    {
        $query = Query::new('foo&bar=baz');

        self::assertSame($query, $query->withoutParameters());
    }

    public function testCreateFromParamsWithTraversable(): void
    {
        $data = [
            'foo' => [
                'bar',
                'baz',
            ],
        ];

        self::assertSame($data, Query::fromParameters(new ArrayIterator($data))->parameters());
    }

    public function testCreateFromParamsWithQueryObject(): void
    {
        $query = Query::new('a=1&b=2');
        self::assertEquals($query->value(), Query::fromParameters($query)->value());
    }

    public static function testWithoutNumericIndices(): void
    {
        $data = [
            'filter' => [
                'foo' => [
                    'bar',
                    'baz',
                ],
                'bar' => [
                    'bar' => 'foo',
                    'foo' => 'bar',
                ],
            ],
        ];

        $withIndices = 'filter%5Bfoo%5D%5B0%5D=bar&filter%5Bfoo%5D%5B1%5D=baz&filter%5Bbar%5D%5Bbar%5D=foo&filter%5Bbar%5D%5Bfoo%5D=bar';
        $withoutIndices = 'filter%5Bfoo%5D%5B%5D=bar&filter%5Bfoo%5D%5B%5D=baz&filter%5Bbar%5D%5Bbar%5D=foo&filter%5Bbar%5D%5Bfoo%5D=bar';

        $query = Query::fromParameters($data);
        self::assertSame($withIndices, $query->value());
        self::assertSame($data, $query->parameters());

        $newQuery = $query->withoutNumericIndices();
        self::assertSame($withoutIndices, $newQuery->value());
        self::assertSame($data, $newQuery->parameters());
    }

    public function testWithoutNumericIndicesRetursSameInstance(): void
    {
        self::assertSame($this->query->withoutNumericIndices(), $this->query);
    }

    public function testWithoutNumericIndicesReturnsAnother(): void
    {
        $query = (Query::new('foo[3]'))->withoutNumericIndices();

        self::assertTrue($query->has('foo[]'));
        self::assertFalse($query->has('foo[3]'));
    }

    public function testWithoutNumericIndicesDoesNotAffectPairValue(): void
    {
        $query = Query::fromParameters(['foo' => 'bar[3]']);

        self::assertSame($query, $query->withoutNumericIndices());
    }

    public function testCreateFromParamsOnEmptyParams(): void
    {
        $query = Query::fromParameters([]);

        self::assertSame($query, $query->withoutNumericIndices());
    }

    public function testGetContentOnEmptyContent(): void
    {
        self::assertNull(Query::fromParameters([])->value());
    }

    public function testGetContentOnHavingContent(): void
    {
        self::assertSame('foo=bar', Query::fromParameters(['foo' => 'bar'])->value());
    }

    public function testGetContentOnToString(): void
    {
        self::assertSame('foo=bar', (string) Query::fromParameters(['foo' => 'bar']));
    }

    public function testWithSeperatorOnHavingSeparator(): void
    {
        $query = Query::fromParameters(['foo' => '/bar']);

        self::assertSame($query, $query->withSeparator('&'));
    }

    public function testWithoutNumericIndicesOnEmptyContent(): void
    {
        $query = Query::fromParameters([]);

        self::assertSame($query, $query->withoutNumericIndices());
    }

    public static function testSort(): void
    {
        $query = Query::new()
            ->appendTo('a', 3)
            ->appendTo('b', 2)
            ->appendTo('a', 1)
        ;

        $sortedQuery = $query->sort();

        self::assertSame('a=3&b=2&a=1', (string) $query);
        self::assertSame('a=3&a=1&b=2', (string) $sortedQuery);
        self::assertNotEquals($sortedQuery, $query);
    }

    /**
     * @dataProvider sameQueryAfterSortingProvider
     */
    public function testSortReturnSameInstance(?string $query): void
    {
        $query = Query::new($query);

        self::assertSame($query, $query->sort());
    }

    public static function sameQueryAfterSortingProvider(): array
    {
        return [
            'same already sorted' => ['a=3&a=1&b=2'],
            'empty query' => [null],
            'contains each pair key only once' => ['batman=robin&aquaman=aqualad&foo=bar&bar=baz'],
        ];
    }

    /**
     * @dataProvider provideWithPairData
     */
    public function testWithPair(?string $query, string $key, string|null|bool $value, array $expected): void
    {
        self::assertSame($expected, Query::new($query)->withPair($key, $value)->getAll($key));
    }

    public static function provideWithPairData(): array
    {
        return [
            [
                null,
                'foo',
                'bar',
                ['bar'],
            ],
            [
                'foo=bar',
                'foo',
                'bar',
                ['bar'],
            ],
            [
                'foo=bar',
                'foo',
                null,
                [null],
            ],
            [
                'foo=bar',
                'foo',
                false,
                ['false'],
            ],
        ];
    }

    public function testWithPairBasic(): void
    {
        self::assertSame('a=B&c=d', Query::new('a=b&c=d')->withPair('a', 'B')->toString());
        self::assertSame('a=B&c=d', Query::new('a=b&c=d&a=e')->withPair('a', 'B')->toString());
        self::assertSame('a=b&c=d&e=f', Query::new('a=b&c=d')->withPair('e', 'f')->toString());
    }

    /**
     * @dataProvider mergeBasicProvider
     */
    public function testMergeBasic(string $src, Stringable|string|null $dest, string $expected): void
    {
        self::assertSame($expected, Query::new($src)->merge($dest)->toString());
    }

    public static function mergeBasicProvider(): array
    {
        return [
            'merging null' => [
                'src' => 'a=b&c=d',
                'dest' => null,
                'expected' => 'a=b&c=d',
            ],
            'merging empty string' => [
                'src' => 'a=b&c=d',
                'dest' => '',
                'expected' => 'a=b&c=d&',
            ],
            'merging simple string string' => [
                'src' => 'a=b&c=d',
                'dest' => 'a=B',
                'expected' => 'a=B&c=d',
            ],
            'merging strip additional pairs with same key' => [
                'src' => 'a=b&c=d&a=e',
                'dest' => 'a=B',
                'expected' => 'a=B&c=d',
            ],
            'merging append new data if not found in src query' => [
                'src' => 'a=b&c=d',
                'dest' => 'e=f',
                'expected' => 'a=b&c=d&e=f',
            ],
            'merge can use ComponentInterface' => [
                'src' => 'a=b&c=d',
                'dest' => Query::new(),
                'expected' => 'a=b&c=d',
            ],
        ];
    }

    public function testWithPairGetterMethods(): void
    {
        $query = Query::new('a=1&a=2&a=3');
        self::assertSame('1', $query->get('a'));

        $query = $query->withPair('first', 4);
        self::assertSame('1', $query->get('a'));

        $query = $query->withPair('a', 4);
        self::assertSame('4', $query->get('a'));

        $query = $query->withPair('q', $query);
        self::assertSame('a=4&first=4', $query->get('q'));
    }

    public function testMergeGetterMethods(): void
    {
        $query = Query::new('a=1&a=2&a=3');
        self::assertSame('1', $query->get('a'));

        $query = $query->merge(Query::new('first=4'));
        self::assertSame('1', $query->get('a'));

        $query = $query->merge('a=4');
        self::assertSame('4', $query->get('a'));

        $query = $query->merge(Query::fromPairs([['q', $query->value()]]));
        self::assertSame('a=4&first=4', $query->get('q'));
    }

    /**
     * @dataProvider provideWithoutDuplicatesData
     */
    public function testWithoutDuplicates(?string $query, ?string $expected): void
    {
        self::assertSame($expected, Query::new($query)->withoutDuplicates()->value());
    }

    public static function provideWithoutDuplicatesData(): array
    {
        return [
            'empty query' => [null, null],
            'remove duplicate pair' => ['foo=bar&foo=bar', 'foo=bar'],
            'no duplicate pair key' => ['foo=bar&bar=foo', 'foo=bar&bar=foo'],
            'no duplicate pair value' => ['foo=bar&foo=baz', 'foo=bar&foo=baz'],
        ];
    }

    public function testAppendToSameName(): void
    {
        $query = Query::new();
        self::assertSame('a=b', (string) $query->appendTo('a', 'b'));
        self::assertSame('a=b&a=b', (string) $query->appendTo('a', 'b')->appendTo('a', 'b'));
        self::assertSame('a=b&a=b&a=c', (string) $query->appendTo('a', 'b')->appendTo('a', 'b')->appendTo('a', new class() {
            public function __toString(): string
            {
                return 'c';
            }
        }));
    }

    public function testAppendToWithEmptyString(): void
    {
        $query = Query::new();
        self::assertSame('', (string) $query->appendTo('', null));
        self::assertSame('=', (string) $query->appendTo('', ''));
        self::assertSame('a', (string) $query->appendTo('a', null));
        self::assertSame('a=', (string) $query->appendTo('a', ''));
        self::assertSame(
            'a&a=&&=',
            (string) $query
            ->appendTo('a', null)
            ->appendTo('a', '')
            ->appendTo('', null)
            ->appendTo('', '')
        );
    }

    public function testAppendToWithGetter(): void
    {
        $query = Query::new()
            ->appendTo('first', 1)
            ->appendTo('second', 2)
            ->appendTo('third', '')
            ->appendTo('first', 10)
        ;
        self::assertSame('first=1&second=2&third=&first=10', (string) $query);
        self::assertTrue($query->has('first'));
        self::assertSame('1', $query->get('first'));
        self::assertSame('2', $query->get('second'));
        self::assertSame('', $query->get('third'));

        $newQuery = $query->appendTo('first', 10);
        self::assertSame('first=1&second=2&third=&first=10&first=10', (string) $newQuery);
        self::assertSame('1', $newQuery->get('first'));
    }

    /**
     * @dataProvider getURIProvider
     */
    public function testCreateFromUri(Psr7UriInterface|UriInterface $uri, ?string $expected): void
    {
        self::assertSame($expected, Query::fromUri($uri)->value());
    }

    public static function getURIProvider(): iterable
    {
        return [
            'PSR-7 URI object' => [
                'uri' => Http::new('http://example.com?foo=bar'),
                'expected' => 'foo=bar',
            ],
            'PSR-7 URI object with no query' => [
                'uri' => Http::new('http://example.com'),
                'expected' => null,
            ],
            'PSR-7 URI object with empty string query' => [
                'uri' => Http::new('http://example.com?'),
                'expected' => null,
            ],
            'League URI object' => [
                'uri' => Uri::new('http://example.com?foo=bar'),
                'expected' => 'foo=bar',
            ],
            'League URI object with no query' => [
                'uri' => Uri::new('http://example.com'),
                'expected' => null,
            ],
            'League URI object with empty string query' => [
                'uri' => Uri::new('http://example.com?'),
                'expected' => '',
            ],
        ];
    }

    public function testCreateFromRFCSpecification(): void
    {
        self::assertEquals(
            Query::fromRFC3986('foo=b%20ar|foo=baz', '|'),
            Query::fromRFC1738('foo=b+ar|foo=baz', '|')
        );
    }
}
