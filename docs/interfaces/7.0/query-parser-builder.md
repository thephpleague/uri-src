---
layout: default
title: URI Query string parser
description: a URI query parser and builder in PHP. 
---

Query Parser and Builder
=======

The `League\Uri\QueryString` is a PHP URI query parser and builder.

<p class="message-notice">The parsing/building algorithms preserve pairs order and uses the same algorithm used by
JavaScript <a href="https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams/URLSearchParams">UrlSearchParams</a></p>

```php
<?php

use League\Uri\QueryString;

$pairs = QueryString::parse('module=home&action=show&page=😓');
// returns [
//     ['module', 'home'],
//     ['action', 'show'],
//     ['page', '😓']
// ];

$str = QueryString::build($pairs, '|');
// returns 'module=home|action=show|page=😓'
```

## Query String Parsing

To parse a query string use the ` QueryString::parse` method as shown below:

```php
$pairs = QueryString::parse('module=home&action=show&page=😓');
// returns [
//     ['module', 'home'],
//     ['action', 'show'],
//     ['page', '😓']
// ];
```

The returned array is a collection of key/value pairs. Each pair is represented
as an array where the first element is the pair key and the second element the
pair value. While the pair key is always a string, the pair value can be a
string or the `null` value.

The `QueryString::parse` method parameters are:

- `$query` can be the `null` value, any scalar or object which is stringable;
- `$separator` is a string; by default it is the `&` character;
- `$enc_type` is one of PHP's constant `PHP_QUERY_RFC3968` or `PHP_QUERY_RFC1738` which represented the supported encoding algoritm
    - If you specify `PHP_QUERY_RFC3968` decoding will be done using [RFC3986](https://tools.ietf.org/html/rfc3986#section-3.4) rules;
    - If you specify `PHP_QUERY_RFC1738` decoding will be done using [application/x-www-form-urlencoded](https://url.spec.whatwg.org/#urlencoded-parsing) rules;

Here's a simple example showing how to use all the given parameters:

```php
$pairs = QueryString::parse(
    'module=home:action=show:page=toto+bar&action=hide',
    ':',
    PHP_QUERY_RFC1738
);
// returns [
//     ['module', 'home'],
//     ['action', 'show'],
//     ['page', 'toto bar'],
//     ['action', 'hide'],
// ];
```

<p class="message-warning">The <code>$separator</code> argument cannot be the empty string.</p>

## Query String Building

To convert back the collection of key/value pairs into a valid query string or the `null` value
you can use the static public `QueryString::build` method.

```php
$pairs = QueryString::build([
    ['module', 'home'],
    ['action', 'show'],
    ['page', 'toto bar'],
    ['action', 'hide'],
], '|', PHP_QUERY_RFC3986);

// returns 'module=home|action=show|page=toto%20bar|action=hide';
```

The static public `QueryString::build` method parameters are:

- `$pairs` an iterable structure containing a collection of key/pair pairs as describe in the returned array of the `QueryString::parse` method.
- `$separator` is a string; by default, it is the `&` character;
- `$enc_type` is one of PHP's constant `PHP_QUERY_RFC3968` or `PHP_QUERY_RFC1738` which represented the supported encoding algoritm
    - If you specify `PHP_QUERY_RFC3968` encoding will be done using [RFC3986](https://tools.ietf.org/html/rfc3986#section-3.4) rules;
    - If you specify `PHP_QUERY_RFC1738` encoding will be done using [application/x-www-form-urlencoded](https://url.spec.whatwg.org/#urlencoded-parsing) rules;

- the function returns the `null` value if the submitted collection is empty.

<p class="message-warning">The <code>$separator</code> argument cannot be the empty string.</p>

## Extracting PHP variables

`QueryString::parse` and `QueryString::build` preserve the query string pairs content and order. If you want to extract PHP variables from the query string *à la* `parse_str` you can use:

```php
<?php
use League\Uri\QueryParsingMode;

public static function QueryString::extract($query, string $separator = '&', int $enc_type = PHP_QUERY_RFC3986, QueryParsingMode $queryParsingMode = QueryParsingMode::Unmangled): array;
public static function QueryString::convert(iterable $pairs, QueryParsingMode $queryParsingMode = QueryParsingMode::Unmangled): array;
```

- The `QueryString::extract` method takes the same parameters as `QueryString::parse`
- The `QueryString::convert` method takes the result of `QueryString::parse`

Both methods take an optional parsing mode that dictates how the parameters are constructired:

- `QueryParsingMode::Native` generates an array like `parse_str` the only difference is that you can specify the separator character;
- `QueryParsingMode::Unmangled` generates an array like `parse_str` but does not allow parameters key mangling in the returned array;
- `QueryParsingMode::PreserveNull` Use the same rules used for `QueryParsingMode::Unmangled` and adds the facts that `null` values as not converted to the empty string;

By default, both methods use the `QueryParsingMode::Unmangled` mode.

```php
$query = 'module=show&arr.test[1]=sid&arr test[4][two]=fred&+module+=hide&null';

$native = QueryString::extract($query, '&', PHP_QUERY_RFC1738, QueryParsingMode::Native);
// $native contains [
//     'module' = 'show',
//     'arr_test' => [
//         1 => 'sid',
//         4 => [
//             'two' => 'fred',
//         ],
//     ],
//     'module_' = 'hide',
//     'null' => '',
// ];

$unmangled = QueryString::extract($query, '&', PHP_QUERY_RFC1738, QueryParsingMode::Unmangled);
// $unmangled contains [
//     'module' = 'show',
//     'arr.test' => [
//         1 => 'sid',
//     ],
//     'arr test' => [
//         4 => [
//             'two' => 'fred',
//         ]
//     ],
//     ' module ' => 'hide',
//     'null' => '',
// ];

$preserved = QueryString::extract($query, '&', PHP_QUERY_RFC1738, QueryParsingMode::PreserveNull);
// $preserved contains [
//     'module' = 'show',
//     'arr.test' => [
//         1 => 'sid',
//     ],
//     'arr test' => [
//         4 => [
//             'two' => 'fred',
//         ]
//     ],
//     ' module ' => 'hide',
//     'null' => null,
// ];
```

## Building from PHP variables

<p class="message-notice">Available since version <code>7.8.0</code></p>

```php
<?php
use League\Uri\QueryBuildingMode;

public static function QueryString::compose(
    array|object $data,
    string $separator = '&',
    int $encType = PHP_QUERY_RFC3986,
    QueryBuildingMode $queryBuildingMode = QueryBuildingMode::Native
): ?string;
```

To convert back an array or an object into a valid query string or the `null` value
you can use the static public `QueryString::compose` method.

```php
echo QueryString::compose([
    'module' => 'home',
    'action' => 'show',
    'page' => 'toto bar',
], '|', PHP_QUERY_RFC3986);

// display 'module=home|action=show|page=toto%20bar';
```

The static public `QueryString::compose` method parameters are:

- `$data` an `array` or an `object` as describe in the `http_build_query` functions.
- `$separator` is a string; by default, it is the `&` character;
- `$encType` is one of PHP's constant `PHP_QUERY_RFC3968` or `PHP_QUERY_RFC1738` which represented the supported encoding algoritm
    - If you specify `PHP_QUERY_RFC3986` encoding will be done using [RFC3986](https://tools.ietf.org/html/rfc3986#section-3.4) rules;
    - If you specify `PHP_QUERY_RFC1738` encoding will be done using [application/x-www-form-urlencoded](https://url.spec.whatwg.org/#urlencoded-parsing) rules;
- `$queryBuildingMode` a Enum to describe how query serialization will be performed.

if `$queryBuildingMode` is equal to `QueryBuildingMode::ValueOnly`:

- the function returns the `null` value if the submitted `array is the empty array. Otherwise in any other building mode, the empty string will be used.
- the function disallows the use of resources or objects (with the notable exception of `BackedEnum`).

Two other modes, `QueryBuildingMode::Compatible` and `QueryBuildingMode::EnumCompatible`, exists to allow
migrating your codebase from the historical encoding algorithm to the current algorithm.

<p class="message-warning">The <code>$separator</code> argument cannot be the empty string.</p>

The `QueryString::compose` method is a userland implementation of the `http_build_query` functions with the following
differences:

- if a resource is used, a `TypeError` is thrown, `http_build_query` returns an empty string as query string.
- if a recursion is detected a `ValueError` is thrown, `http_build_query` returns an empty string as query string.
- the method preserves value with `null` value, `http_build_query` skips the name/value association.
- the method does not handle prefix usage
- By default, the method uses `PHP_QUERY_RFC3968`; `http_build_query` uses `PHP_QUERY_RFC1738`.

```php
echo QueryString::compose([
    'module' => null,
    'action' => '',
    'page' => true,
]);

// display 'module&action=&page=1';
```

## Advance usages

Starting with version <code>7.1</code> you can have an improved control over the characters conversion
by using the `League\Uri\KeyValuePair\Converter` class. The class is responsible for parsing the string into key/value
pair and for converting key/value pairs into string adding an extra string replacement before parsing and 
after building the string.

```php
use League\Uri\KeyValuePair\Converter as KeyValuePairConverter;
use League\Uri\QueryString;

$converter = KeyValuePairConverter::new(';')
    ->withEncodingMap([
        '%3F' => '?',
        '%2F' => '/',
        '%40' => '@',
        '%3A' => ':',
        '%5B' => '[',
        '%5D' => ']',
        '%3D' => '=',
        '%23' => '#',
    ]);

$keyValuePairs = QueryString::parseFromValue('foo=bar&url=https://example.com?foo[2]=bar#fragment');

echo QueryString::buildFromPairs($keyValuePairs, $converter));
// displays foo=bar;url=https://example.com?foo[2]=bar#fragment
```

You can use the class on the following methods as the second argument:

- `buildFromPairs` improved version of `build`
- `extractFromValue` improved version of `extract`
- `parseFromValue` improved version of `parse`
- `composeFromValue` improved version of `compose` **since version 7.8**

## Exceptions

All exceptions extend the `League\Uri\Exceptions\UriException` marker class which extends PHP's `Throwable` class.

```php
try {
    QueryString::extract('foo=bar', '&', 42);
} catch (UriException $e) {
    //$e is an instanceof League\Uri\Exceptions\SyntaxError
}
```
