---
layout: default
title: URL Pattern 
description: URL Pattern matches URIs or parts of URIs against a pattern.
---

URLPattern
=========

The URL Pattern API defines a syntax that is used to create URL pattern matchers. These patterns can be matched against URLs or individual URL components.

## Concept

Patterns are specified using the `URLPattern` class. The pattern syntax is based on the syntax from the [path-to-regexp library](https://github.com/pillarjs/path-to-regexp). 

Patterns can contain:

- **Literal strings:** `/books` — matches exactly.
- **Wildcards:** `/posts/*` — matches any characters.
- **Named groups:** `/books/:id` — captures a value as id.
- **Groups:** `/books{/old}` — groups part of a pattern.
- **Regex groups:** `/books/:id(\d+)` — constrains a captured value with a regular expression.
- **Optional groups:** `/books/:id?` — makes a group optional.
- **Repeated groups:** `/books/:id+` — matches one or more occurrences; * matches zero or more.

The API follows [the WHATWG URL Pattern specification](https://urlpattern.spec.whatwg.org/). The PHP implementation
uses PHP-oriented names and types where they differ from the WHATWG API.

| WHATWG API                          | PHP implementation                                              |
|-------------------------------------|-----------------------------------------------------------------|
| `URLPattern::test()`                | `UrlPattern::match()`                                           |
| `URLPattern::exec()`                | `UrlPattern::extract()`                                         |
| `URLPattern::protocol`              | `UrlPattern::scheme`                                            |
| `URLPattern::hostname`              | `UrlPattern::host`                                              |
| `URLPattern::pathname`              | `UrlPattern::path`                                              |
| `URLPattern::hash`                  | `UrlPattern::fragment`                                          |
| `URLPatternResult::protocol`        | `UrlPattern\Result::scheme`                                     |
| `URLPatternResult::hostname`        | `UrlPattern\Result::host`                                       |
| `URLPatternResult::pathname`        | `UrlPattern\Result::path`                                       |
| `URLPatternResult::hash`            | `UrlPattern\Result::fragment`                                   |
| `URLPatternComponentResult::groups` | `UrlPattern\ComponentResult` implements `ArrayAccess` interface |

`UrlPattern\Result` and `UrlPattern\ComponentResult` contain only the extracted values; they do not retain
the `UrlPattern` instance or the pattern strings used for extraction.

## Basic Usage

The package comes bundles with two classes `UrlPatternBuilder` and `UrlPattern`, the former is a builder class to
ease generating `UrlPattern` instances.

The `UrlPatternBuilder` shares a similar logic as `UriBuilder` and using tis `build` method to returns an
instantiated `UrlPattern` instance.

```php
use League\Uri\UrlPatternBuilder;

// create a new instance
$pattern = new UrlPatternBuilder()
    ->path('/book/:id?')
    ->build();

// test if the submitted URI matches
if ($pattern->match('https://example.com/books/123')) {
    echo "The URI matches", PHP_EOL;
}

// Since the URI matches we can extract the matching part
$result = $pattern->extract('https://example.com/books/123');
$result->path['id']; // "123"
$result->path->integer('id'); // 123
```

You may generate a `UrlPattern` instance from a base URL

```php
use League\Uri\UrlPattern;
use League\Uri\UrlPatternBuilder;

// create a new instance using the UrlPattern class
$pattern = UrlPattern::from("/books/:id(\\d+)", "https://example.com"); // the base URL

// or using the UrlPatternBuilder builder
$pattern = UrlPatternBuilder::from("/books/:id(\\d+)")->build("https://example.com")

$pattern->match("https://example.com/books/123");
// true

$pattern->match("https://example.com/books/abc");
// false

$pattern->match("https://example.com/books/");
// false
```

## Extracted values

~~~php
use League\Uri\UrlPatternBuilder;

$pattern = new UrlPatternBuilder()
    ->path('/book/:id?')
    ->build();

$result = $pattern->extract('https://example.com/books/123');
$result->path['id']; // "123"
$result->path->integer('id'); // 123
~~~

### Value Access

`League\Uri\UrlPattern::extract()` returns an `League\Uri\UrlPattern\Result` which is a container for every component
individual results. Each result is a `League\Uri\UrlPattern\ComponentResult` instance.

If the extraction was successful the `ComponentResult::isEmpty()` method will return `false`.
You may access each variable individually using the `ArrayAccess` interface.

~~~php
use League\Uri\UrlPatternBuilder;

// create a new instance
$pattern = new UrlPatternBuilder()
    ->path('/book/:id?')
    ->build();

// Since the URI matches we can extract the matching part
$result = $pattern->extract('https://example.com/books/123');
$resut->host->isEmpty(); 
// true 

$resut->path->isEmpty();
// false 

count($resut->path); 
// 1

$result->path['id'];
// "123"
~~~

Extracted values are **URL-decoded**. Percent-encoded sequences in the input are decoded
before the values are returned.

```php
use League\Uri\UrlPatternBuilder;

$pattern = new UrlPatternBuilder()
    ->path('/hotels/:hotel?')
    ->build();

$result = $pattern->extract('/hotels/Rest%20%26%20Relax');
$result->path['hotel'];
// 'Rest & Relax'
```

The decoding is component specific

```php
use League\Uri\UrlPatternBuilder;

$pattern = new UrlPatternBuilder()
    ->path('/:foo')
    ->host('{:foo}.example.com')
    ->build();
$result = $pattern->extract('http://xn--fi8h.example.com/%F0%9F%8D%85');
$result->path['foo'];
// 🍅
$result->host['foo'];
// 🍅
```

`ComponentResult::variables()` returns all extracted values as an associative array.
Each key is a variable name and each value is the corresponding extracted value.

```php
$result->variables();
// [
//   "hotel" => "Rest & Relax"
// ]
```

### Type Inference

`ComponentResult` also provides typed accessors for retrieving an extracted value in a
specific type:

* `ComponentResult::string()`
* `ComponentResult::boolean()`
* `ComponentResult::integer()`
* `ComponentResult::float()`
* `ComponentResult::date()`
* `ComponentResult::enum()`

These methods retrieve an extracted value and attempt to convert it to the requested PHP type.
If the value is missing or cannot be converted, they return `null`.

~~~php
use League\Uri\UrlPatternBuilder;

// create a new instance
$pattern = new UrlPatternBuilder()
    ->path('/book/:id?')
    ->build();

// Since the URI matches we can extract the matching part
$result = $pattern->extract('https://example.com/books/123');

$result->path['id'];
// "123"

$result->path->integer('id');
// 123

$result->path->float('id');
// 123.0
~~~

This PHP implementation is inspired by the [urlpattern-polyfill](https://github.com/kenchris/urlpattern-polyfill) package.
