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

namespace League\Uri\Components;

use League\Uri\Exceptions\SyntaxError;
use PHPUnit\Framework\TestCase;
use stdClass;

final class URLSearchParamsTest extends TestCase
{
    public function testBasicConstructor(): void
    {
        $params = new URLSearchParams();
        self::assertSame('', $params->toString());
        self::assertTrue($params->isEmpty());

        $params = new URLSearchParams('');
        self::assertSame('', $params->toString());
        self::assertTrue($params->isEmpty());

        $params = new URLSearchParams('a=b');
        self::assertSame('a=b', $params->toString());

        $params = new URLSearchParams('?a=b');
        self::assertSame('a=b', $params->toString());

        $params = new URLSearchParams($params);
        self::assertSame('a=b', $params->toString());

        $params = new URLSearchParams(new stdClass());
        self::assertSame('', $params->toString());
    }

    public function testTextConstructor(): void
    {
        $params = new URLSearchParams('a=b');

        self::assertTrue($params->has('a'));
        self::assertFalse($params->has('b'));

        $params = new URLSearchParams('a=b&c');

        self::assertTrue($params->has('a'));
        self::assertTrue($params->has('c'));

        $params = new URLSearchParams('&a&&& &&&&&a+b=& c&m%c3%b8%c3%b8');

        self::assertTrue($params->has('a'), 'Search params object has name "a"');
        self::assertTrue($params->has('a b'), 'Search params object has name "a b"');
        self::assertTrue($params->has(' '), 'Search params object has name " "');
        self::assertFalse($params->has('c'), 'Search params object did not have the name "c"');
        self::assertTrue($params->has(' c'), 'Search params object has name " c"');
        self::assertTrue($params->has('møø'), 'Search params object has name "møø"');

        $params = new URLSearchParams('id=0&value=%');

        self::assertTrue($params->has('id'), 'Search params object has name "id"');
        self::assertTrue($params->has('value'), 'Search params object has name "value"');
        self::assertSame('0', $params->get('id'));
        self::assertSame('%', $params->get('value'));

        $params = new URLSearchParams('b=%2sf%2a');

        self::assertTrue($params->has('b'), 'Search params object has name "b"');
        self::assertSame('%2sf*', $params->get('b'));

        $params = new URLSearchParams('b=%2%2af%2a');

        self::assertTrue($params->has('b'), 'Search params object has name "b"');
        self::assertSame('%2*f*', $params->get('b'));

        $params = new URLSearchParams('b=%%2a');

        self::assertTrue($params->has('b'), 'Search params object has name "b"');
        self::assertSame('%*', $params->get('b'));
    }

    public function testConstructorWithObjects(): void
    {
        $seed = new URLSearchParams('a=b&c=d');
        $params = new URLSearchParams($seed);

        self::assertSame('b', $params->get('a'));
        self::assertSame('d', $params->get('c'));
        self::assertFalse($params->has('d'));

        // The name-value pairs are copied when created; later updates should not be observable.
        $seed->append('e', 'f');

        self::assertFalse($params->has('e'));

        $params->append('g', 'h');

        self::assertFalse($seed->has('g'));
    }

    public function testQueryParsing(): void
    {
        $params = new URLSearchParams('a=b+c');
        self::assertSame('b c', $params->get('a'));

        $params = new URLSearchParams('a+b=c');
        self::assertSame('c', $params->get('a b'));
    }

    public function testQueryEncoding(): void
    {
        $expected = '+15555555555';
        $params = new URLSearchParams();
        $params->set('query', $expected);
        $newParams = new URLSearchParams($params->toString());

        self::assertSame('query=%2B15555555555', $params->toString());
        self::assertSame($expected, $params->get('query'));
        self::assertSame($expected, $newParams->get('query'));
    }

    public function testParseSpace(): void
    {
        $params = new URLSearchParams('a=b c');
        self::assertSame($params->get('a'), 'b c');

        $params = new URLSearchParams('a b=c');
        self::assertSame('c', $params->get('a b'));
    }

    public function testParseEncodedSpace(): void
    {
        $params = new URLSearchParams('a=b%20c');
        self::assertSame('b c', $params->get('a'));

        $params = new URLSearchParams('a%20b=c');
        self::assertSame('c', $params->get('a b'));
    }

    public function testNewInstanceWithSequenceOfSequencesOfString(): void
    {
        $params = new URLSearchParams([]);
        self::assertSame('', (string) $params);

        $params = new URLSearchParams([['a', 'b'], ['c', 'd']]);
        self::assertSame('b', $params->get('a'));
        self::assertSame('d', $params->get('c'));
    }

    /**
     * @dataProvider providesInvalidSequenceOfSequencesOfString
     */
    public function testNewInstanceWithSequenceOfSequencesOfStringFails(array $sequences): void
    {
        $this->expectException(SyntaxError::class);

        new URLSearchParams($sequences);
    }

    public static function providesInvalidSequenceOfSequencesOfString(): iterable
    {
        return [
            [
                [[1]],
            ],
            [
                [[1, 2, 3]],
            ],
        ];
    }

    /**
     * @dataProvider providesComplexConstructorData
     */
    public function testComplexConstructor(string $json): void
    {
        /** @var object{input: string, output: array<array{0: string, 1: string}>, name: string} $res */
        $res = json_decode($json);

        $params = new URLSearchParams($res->input);

        self::assertSame($res->output, [...$params], 'Invalid '.$res->name);
    }

    public static function providesComplexConstructorData(): iterable
    {
        return [
            ['{ "input": {"+": "%C2"}, "output": [["+", "%C2"]], "name": "object with +" }'],
            ['{ "input": {"c": "x", "a": "?"}, "output": [["c", "x"], ["a", "?"]], "name": "object with two keys" }'],
            ['{ "input": [["c", "x"], ["a", "?"]], "output": [["c", "x"], ["a", "?"]], "name": "array with two keys" }'],
            //['{ "input": {"\uD835x": "1", "xx": "2", "\uD83Dx": "3"}, "output": [["\uFFFDx", "3"], ["xx", "2"]], "name": "2 unpaired surrogates (no trailing)" }'],
            //['{ "input": {"x\uDC53": "1", "x\uDC5C": "2", "x\uDC65": "3"}, "output": [["x\uFFFD", "3"]], "name": "3 unpaired surrogates (no leading)" }'],
            //['{ "input": {"a\0b": "42", "c\uD83D": "23", "d\u1234": "foo"}, "output": [["a\0b", "42"], ["c\uFFFD", "23"], ["d\u1234", "foo"]], "name": "object with NULL, non-ASCII, and surrogate keys" }']
        ];
    }


    public function testItCanAppendSameName(): void
    {
        $params = new URLSearchParams();
        $params->append('a', 'b');
        self::assertSame('a=b', $params->toString());

        $params->append('a', 'b');
        self::assertSame('a=b&a=b', $params->toString());

        $params->append('a', 'c');
        self::assertSame('a=b&a=b&a=c', $params->toString());
    }

    public function testItCanAppendEmptyString(): void
    {
        $params = new URLSearchParams();
        $params->append('', '');
        self::assertSame('=', $params->toString());

        $params->append('', '');
        self::assertSame('=&=', $params->toString());
    }

    public function testItCanAppendNull(): void
    {
        $params = new URLSearchParams();
        $params->append(null, null);
        self::assertSame('null=null', $params->toString());

        $params->append(null, null);
        self::assertSame('null=null&null=null', $params->toString());
    }

    public function testItCanAppendMultipleParameters(): void
    {
        $params = new URLSearchParams();
        $params->append('first', 1);
        $params->append('second', 2);
        $params->append('third', '');
        $params->append('first', 10);

        self::assertTrue($params->has('first'));
        self::assertSame('1', $params->get('first'));
        self::assertSame('2', $params->get('second'));
        self::assertSame('', $params->get('third'));

        $params->append('first', 10);

        self::assertSame('1', $params->get('first'));
        self::assertSame(['1', '10', '10'], [...$params->getAll('first')]);
    }

    public function testDeleteBasics(): void
    {
        $params = new URLSearchParams('a=b&c=d');
        $params->delete('a');
        self::assertSame($params->toString(), 'c=d');

        $params = new URLSearchParams('a=a&b=b&a=a&c=c');
        $params->delete('a');
        self::assertSame($params->toString(), 'b=b&c=c');

        $params = new URLSearchParams('a=a&=&b=b&c=c');
        $params->delete('');
        self::assertSame($params->toString(), 'a=a&b=b&c=c');

        $params = new URLSearchParams('a=a&null=null&b=b');
        $params->delete(null);
        self::assertSame($params->toString(), 'a=a&b=b');

        $params = new URLSearchParams('a=a&null=null&b=b');
        $params->delete(null);
        self::assertSame($params->toString(), 'a=a&b=b');
    }

    public function testDeleteAppendedMultiple(): void
    {
        $params = new URLSearchParams();
        $params->append('first', 1);
        self::assertTrue($params->has('first'), 'Search params object has name "first"');
        self::assertSame($params->get('first'), '1', 'Search params object has name "first" with value "1"');
        $params->delete('first');
        self::assertCount(0, $params);
        self::assertFalse($params->has('first'), 'Search params object has no "first" name');
        $params->append('first', 1);
        $params->append('first', 10);
        self::assertCount(2, $params);
        $params->delete('first');
        self::assertFalse($params->has('first'), 'Search params object has no "first" name');

        $params = new URLSearchParams('param1&param2');
        $params->delete('param1');
        $params->delete('param2');
        self::assertCount(0, $params);

        self::assertSame($params->toString(), '', 'Search params object has name "first" with value "1"');
    }

    public function testTwoArgumentDelete(): void
    {
        $params = new URLSearchParams();
        $params->append('a', 'b');
        $params->append('a', 'c');
        $params->append('a', 'd');
        $params->delete('a', 'c');

        self::assertSame($params->toString(), 'a=b&a=d');
        self::assertCount(2, $params);
    }

    public function testForEachCheck(): void
    {
        $params = new URLSearchParams('a=1&b=2&c=3');
        $keys = [];
        $values = [];
        $params->each(function ($value, $key) use (&$keys, &$values) {
            $keys[] = $key;
            $values[] = $value;
        });

        self::assertSame(['a', 'b', 'c'], $keys);
        self::assertSame(['1', '2', '3'], $values);
    }

    public function testForOfCheck(): void
    {
        $params = URLSearchParams::fromUri('http://a.b/c?a=1&b=2&c=3&d=4');
        self::assertSame([
            ['a', '1'],
            ['b', '2'],
            ['c', '3'],
            ['d', '4'],
        ], [...$params]);
    }

    public function testGetMethod(): void
    {
        $params = new URLSearchParams('a=b&c=d');

        self::assertSame($params->get('a'), 'b');
        self::assertSame($params->get('c'), 'd');
        self::assertSame($params->get('e'), null);

        $params = new URLSearchParams('a=b&c=d&a=e');

        self::assertSame($params->get('a'), 'b');

        $params = new URLSearchParams('=b&c=d');

        self::assertSame($params->get(''), 'b');

        $params = new URLSearchParams('a=&c=d&a=e');

        self::assertSame($params->get('a'), '');

        $params = new URLSearchParams('first=second&third&&');

        self::assertTrue($params->has('first'), 'Search params object has name "first"');
        self::assertSame($params->get('first'), 'second', 'Search params object has name "first" with value "second"');
        self::assertSame($params->get('third'), '', 'Search params object has name "third" with the empty value.');
        self::assertSame($params->get('fourth'), null, 'Search params object has no "fourth" name and value.');
    }

    public function testGetAllMethod(): void
    {
        $params = new URLSearchParams('a=b&c=d');

        self::assertSame($params->getAll('a'), ['b']);
        self::assertSame($params->getAll('c'), ['d']);
        self::assertSame($params->getAll('e'), []);

        $params = new URLSearchParams('a=b&c=d&a=e');

        self::assertSame($params->getAll('a'), ['b', 'e']);

        $params = new URLSearchParams('=b&c=d');

        self::assertSame($params->getAll(''), ['b']);

        $params = new URLSearchParams('a=&c=d&a=e');

        self::assertSame($params->getAll('a'), ['', 'e']);

        $params = new URLSearchParams('a=1&a=2&a=3&a');

        self::assertTrue($params->has('a'), 'Search params object has name "a"');

        $matches = $params->getAll('a');

        self::assertCount(4, $matches, 'Search params object has values for name "a"');
        self::assertSame($matches, ['1', '2', '3', ''], 'Search params object has expected name "a" values');

        $params->set('a', 'one');

        self::assertSame($params->get('a'), 'one', 'Search params object has name "a" with value "one"');

        $matches = $params->getAll('a');

        self::assertCount(1, $matches, 'Search params object has values for name "a"');
        self::assertSame($matches, ['one'], 'Search params object has expected name "a" values');
    }

    public function testSetMethod(): void
    {
        $params = new URLSearchParams('a=b&c=d');
        $params->set('a', 'B');
        self::assertSame($params->toString(), 'a=B&c=d');
        $params = new URLSearchParams('a=b&c=d&a=e');
        $params->set('a', 'B');
        self::assertSame($params->toString(), 'a=B&c=d');
        $params->set('e', 'f');
        self::assertSame($params->toString(), 'a=B&c=d&e=f');

        $params = new URLSearchParams('a=1&a=2&a=3');
        self::assertTrue($params->has('a'), 'Search params object has name "a"');
        self::assertSame($params->get('a'), '1', 'Search params object has name "a" with value "1"');
        $params->set('first', 4);
        self::assertTrue($params->has('a'), 'Search params object has name "a"');
        self::assertSame($params->get('a'), '1', 'Search params object has name "a" with value "1"');
        $params->set('a', 4);
        self::assertTrue($params->has('a'), 'Search params object has name "a"');
        self::assertSame($params->get('a'), '4', 'Search params object has name "a" with value "4"');
    }

    public function testSerialize(): void
    {
        $params = new URLSearchParams();
        $params->append('a', 'b c');
        self::assertSame($params->toString(), 'a=b+c');
        $params->delete('a');
        $params->append('a b', 'c');
        self::assertSame($params->toString(), 'a+b=c');
    }

    /**
     * @dataProvider provideSortingPayload
     */
    public function testSorting(string $input, array $output): void
    {
        if ('ﬃ&🌈' === $input) {
            self::markTestSkipped('Codepoint sorting is not yet supported.');
        }

        $params = new URLSearchParams($input);
        $params->sort();

        self::assertSame($output, [...$params]);
    }

    public static function provideSortingPayload(): iterable
    {
        $json = <<<JSON
[
  {
    "input": "z=b&a=b&z=a&a=a",
    "output": [["a", "b"], ["a", "a"], ["z", "b"], ["z", "a"]]
  },
  {
    "input": "\uFFFD=x&\uFFFC&\uFFFD=a",
    "output": [["\uFFFC", ""], ["\uFFFD", "x"], ["\uFFFD", "a"]]
  },
  {
    "input": "ﬃ&🌈",
    "output": [["🌈", ""], ["ﬃ", ""]]
  },
  {
    "input": "é&e\uFFFD&e\u0301",
    "output": [["e\u0301", ""], ["e\uFFFD", ""], ["é", ""]]
  },
  {
    "input": "z=z&a=a&z=y&a=b&z=x&a=c&z=w&a=d&z=v&a=e&z=u&a=f&z=t&a=g",
    "output": [["a", "a"], ["a", "b"], ["a", "c"], ["a", "d"], ["a", "e"], ["a", "f"], ["a", "g"], ["z", "z"], ["z", "y"], ["z", "x"], ["z", "w"], ["z", "v"], ["z", "u"], ["z", "t"]]
  },
  {
    "input": "bbb&bb&aaa&aa=x&aa=y",
    "output": [["aa", "x"], ["aa", "y"], ["aaa", ""], ["bb", ""], ["bbb", ""]]
  },
  {
    "input": "z=z&=f&=t&=x",
    "output": [["", "f"], ["", "t"], ["", "x"], ["z", "z"]]
  },
  {
    "input": "a🌈&a💩",
    "output": [["a🌈", ""], ["a💩", ""]]
  }
]
JSON;
        yield from json_decode($json, true);
    }
}
