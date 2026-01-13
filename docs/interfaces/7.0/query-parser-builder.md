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
- `$coercionMode` takes one of the value of the `StringCoercionMode` Enum introduced in version **7.8.0**
Depending on the coercionMode selected `Native` (for PHP coercion to string) or `EcmaScript` (for JavaScript coercion), the type of parameter accepted and the coercion rules used to convert the value to a string are different.

```php
$pairs = [
    ['module', 'home'],
    ['action', false],
];

echo QueryString::build($pairs, coercionMode: StringCoercionMode::Native);
// returns 'module=home&action=0'
echo QueryString::build($pairs, coercionMode: StringCoercionMode::EcmaScript);
// returns 'module=home&action==false'
```

**For the same input the generated query string is different.**


The function returns the `null` value if the submitted collection is empty.

<p class="message-warning">The <code>$separator</code> argument cannot be the empty string.</p>

## Extracting PHP variables

`QueryString::parse` and `QueryString::build` preserve the query string pairs content and order. If you want to extract PHP variables from the query string *à la* `parse_str` you can use:

```php
<?php
use League\Uri\QueryExtractMode;

public static function QueryString::extract(?string $query, string $separator = '&', int $enc_type = PHP_QUERY_RFC3986, QueryExtractMode $extractMode = QueryExtractMode::Unmangled): array;
public static function QueryString::convert(iterable $pairs, QueryExtractMode $extractMode = QueryExtractMode::Unmangled): array;
```

- The `QueryString::extract` method takes the same parameters as `QueryString::parse`
- The `QueryString::convert` method takes the result of `QueryString::parse`

Both methods take an optional parsing mode that dictates how the parameters are constructired:

- `QueryExtractMode::Native` generates an array like `parse_str` the only difference is that you can specify the separator character;
- `QueryExtractMode::Unmangled` generates an array like `parse_str` but does not allow parameters key mangling in the returned array;
- `QueryExtractMode::LossLess` Use the same rules used for `QueryExtractMode::Unmangled` and adds the facts that `null` values are not converted to the empty string;

By default, both methods use the `QueryExtractMode::Unmangled` mode.

```php
$query = 'module=show&arr.test[1]=sid&arr test[4][two]=fred&+module+=hide&null';

$native = QueryString::extract($query, '&', PHP_QUERY_RFC1738, QueryExtractMode::Native);
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

$unmangled = QueryString::extract($query, '&', PHP_QUERY_RFC1738, QueryExtractMode::Unmangled);
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

$preserved = QueryString::extract($query, '&', PHP_QUERY_RFC1738, QueryExtractMode::LossLess);
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

## Composing of PHP variables

<p class="message-notice">Available since version <code>7.8.0</code></p>

```php
<?php
use League\Uri\QueryComposeMode;

public static function QueryString::compose(
    array|object $data,
    string $separator = '&',
    int $encType = PHP_QUERY_RFC3986,
    QueryComposeMode $composeMode = QueryComposeMode::Native
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
The `QueryString::compose` method is a userland implementation of the `http_build_query`
functions with some differences. The function does not handle any prefixing or the variable
and allow for a range of algorithm compositions:

The method parameters are:

- `$data` an `array` or an `object` as describe in the `http_build_query` functions.
- `$separator` is a string; by default, it is the `&` character;
- `$encType` is one of PHP's constant `PHP_QUERY_RFC3968` or `PHP_QUERY_RFC1738` which represented the supported encoding algoritm
    - If you specify `PHP_QUERY_RFC3986` encoding will be done using [RFC3986](https://tools.ietf.org/html/rfc3986#section-3.4) rules;
    - If you specify `PHP_QUERY_RFC1738` encoding will be done using [application/x-www-form-urlencoded](https://url.spec.whatwg.org/#urlencoded-parsing) rules;
- `$composeMode` a Enum to describe how query serialization will be performed.

The `$composeMode` parameter dictates how the `$data` argument will be converted into a URI query string.
If its value is equal to `QueryComposeMode::Safe`:

- the function returns the `null` value if the submitted `array is the empty array. Otherwise in with other compose modes, the empty string is returned instead.
- the function disallows the use of resources or objects (with the notable exception of `BackedEnum`).

Three other modes, `QueryComposeMode::Compatible`, `QueryComposeMode::EnumLenient` and `QueryComposeMode::EnumCompatible`, exists to allow
migrating your codebase from the historical encoding algorithm prior to PHP8.4, if you use the `QueryComposeMode::Compatible` mode
to the current PHP8.4+ behaviour represented by the `QueryComposeMode::EnumCompatible` algorithm or the `QueryComposeMode::Native` mode.
A `QueryComposeMode::EnumLient` mode exist to allow a working with invalid type without triggering
any PHP exception.

<p class="message-warning">The <code>$separator</code> argument cannot be the empty string.</p>

The `QueryString::compose` method is a userland implementation of the `http_build_query` functions with the following
differences:

```php
use League\Uri\QueryComposeMode;
use League\Uri\QueryString;

$data = ['module' => null, 'action' => '', 'page' => true];

echo QueryString::compose($data, QueryComposeMode::Safe);
// display 'module&action=&page=1';

echo QueryString::compose($data, QueryComposeMode::Native);
echo http_build_query($data);
// both call will display 'action=&page=1';
```

## Advance usages

Starting with version <code>7.1</code> you can have an improved control over the characters conversion
by using the `League\Uri\KeyValuePair\Converter` class. The class is responsible for parsing the string into key/value
pair and for converting key/value pairs into string adding an extra string replacement before parsing and 
after building the string.

```php
use League\Uri\KeyValuePair\Converter;
use League\Uri\QueryString;

$converter = Converter::new(';')
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
