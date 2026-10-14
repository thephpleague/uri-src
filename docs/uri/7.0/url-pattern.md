---
layout: default
title: URL Pattern 
description: URL Pattern matches URIs or parts of URIs against a pattern.
---

URL Pattern
=========

<p class="message-notice">Available since version <code>7.9.0</code></p>

The URL Pattern API defines a syntax that is used to create URL pattern matchers.
These patterns can be matched against URLs or individual URL components.

## Introduction

Patterns are specified using the `URLPattern` class. The pattern syntax is based on the syntax from the [path-to-regexp library](https://github.com/pillarjs/path-to-regexp). 

### Available patterns

Patterns can contain:

- **Literal strings:** `/books` — matches exactly.
- **Wildcards:** `/books/*` — matches any characters.
- **Named groups:** `/books/:id` — captures a value as id.
- **Groups:** `/books{/old}` — groups part of a pattern.
- **Regex groups:** `/books/:id(\d+)` — constrains a captured value with a regular expression.
- **Optional groups:** `/books/:id?` — makes a group optional.
- **Repeated groups:** `/books/:id+` — matches one or more occurrences; 
- **Repeated groups:** `/books/:id*` — matches zero or more occurrences.

### URLPattern API

The API follows [the WHATWG URL Pattern specification](https://urlpattern.spec.whatwg.org/). The PHP implementation
uses PHP-oriented names and types where they differ from the WHATWG API.

| WHATWG API                          | PHP implementation                        |
|-------------------------------------|-------------------------------------------|
| `URLPattern::test()`                | `UrlPattern::match()`                     |
| `URLPattern::exec()`                | `UrlPattern::extract()`                   |
| `URLPattern::protocol`              | `UrlPattern::scheme`                      |
| `URLPattern::hostname`              | `UrlPattern::host`                        |
| `URLPattern::pathname`              | `UrlPattern::path`                        |
| `URLPattern::hash`                  | `UrlPattern::fragment`                    |
| `URLPatternResult::protocol`        | `UrlPattern\Result::scheme`               |
| `URLPatternResult::hostname`        | `UrlPattern\Result::host`                 |
| `URLPatternResult::pathname`        | `UrlPattern\Result::path`                 |
| `URLPatternResult::hash`            | `UrlPattern\Result::fragment`             |
| `URLPatternComponentResult::groups` | `UrlPattern\ComponentResult::variables()` |

- `UrlPattern\Result` contain only the extracted values; they do not retain the `UrlPattern` instance or the pattern strings used for extraction.

## Usage

The package comes bundled with the `League\Uri\UrlPattern` to generate and process `UrlPattern` instances.

```php
use League\Uri\UrlPattern;

// create a new instance
$pattern = UrlPattern::from('/book/:id?', 'https://example.com');

// test if the submitted URI matches
if ($pattern->match('https://example.com/books/123')) {
    echo "The URI matches", PHP_EOL;
}

// Since the URI matches we can extract the match variables
$result = $pattern->extract('https://example.com/books/123');
$result->path['id']; // "123"
$result->path->integer('id'); // 123
```

### Using a Base URL

URLPattern generated from the `UrlPattern` class must always be absolute, just like with WHATWG URL.
To get an absolute URL, you either submit a pattern containing at least a scheme or you provide a base URL.

For URL Patterns, the base URL affects only the scheme, host, port, and path components. All other
components are unaffected. Please refer to [MDN URL Pattern API](https://developer.mozilla.org/en-US/docs/Web/API/URL_Pattern_API#inheritance_from_a_base_url) for a complete
explanation on how base URL resolution works and differs from URI Base resolution.

```php
use League\Uri\UrlPattern;
use League\Uri\UrlPatternBuilder;

// create a new instance using the UrlPattern class
$pattern = UrlPattern::from("/books/:id(\\d+)", "https://example.com"); // the base URL

$pattern->match("https://example.com/books/123");
// true

$pattern->match("https://example.com/books/abc");
// false

$pattern->match("https://example.com/books/");
// false
```

### Case sensitivity

The URL Pattern API treats many parts of the URL as case-sensitive by default when matching.

Matching is case-sensitive by default. The `League\Uri\UrlPattern\MatchMode` enum allows you
to enable case-insensitive matching. The selected mode is immutable once the `UrlPattern`
has been created.You can retrieve its state via the `UrlPattern::matchMode` public
read-only property.

~~~php
use League\Uri\UrlPattern;
use League\Uri\UrlPattern\MatchMode;

$pattern = UrlPattern::from(
    pattern: "https://example.com/2022/feb/*",
    matchMode: MatchMode::CaseInsensitive
);
$pattern->match("https://example.com/2022/feb/xc44rsz"); // true
$pattern->match("https://example.com/2022/Feb/xc44rsz"); // true
$pattern->matchMode; // UrlPattern\MatchMode::CaseInsensitive

$pattern = UrlPattern::from("https://example.com/2022/feb/*");
$pattern->match("https://example.com/2022/feb/xc44rsz"); // true
$pattern->match("https://example.com/2022/Feb/xc44rsz"); // false
$pattern->matchMode; // UrlPattern\MatchMode::CaseSensitive
~~~

### Matching specific components

The `League\Uri\UrlPatternBuilder` class allows specifying individual pattern
per URI component. Unlike `UrlPattern::from()`, which requires an absolute
pattern, the builder can produce either an absolute or a relative pattern.

~~~php
use League\Uri\UrlPatternBuilder;
use League\Uri\UrlPatternBuilder\PatternType;

$patternRelative = new UrlPatternBuilder()
    ->path("/books/:id(\\d+)")
    ->host("{*.}?example.com")
    ->build();
    
$patternRelative->type; // PatternType::Relative
    
$patternAbsolute = UrlPattern::from(
    pattern: "https://example.com/2022/feb/*",
    matchMode: MatchMode::CaseInsensitive
);
$patternAbsolute->type // PatternType::Absolute
~~~

Once instantiated you can retrieve each pattern per component using the component name public
read-only property. Per default, if no pattern was given to a specific component the `*` is
returned.

~~~php
use League\Uri\UrlPatternBuilder;

$pattern = new UrlPatternBuilder()
    ->path("/books/:id(\\d+)")
    ->host("{*.}?example.com")
    ->build();

$pattern->match("https://shop.example.com/books/123"); // true
$pattern->match("https://www.example.com/books/123?search=foo#fragment"); // true
$pattern->match("https://shop.example.com/books/abc"); // false
$pattern->match("https://shop.my-blog.com/books/123"); // false
$pattern->scheme;   // "*"
$pattern->username; // "*"
$pattern->password; // "*"
$pattern->host;     // "{*.}?example.com"
$pattern->port;     // "*"
$pattern->path;     // "/books/:id(\\d+)"
$pattern->query;    // "*"
$pattern->fragment; // "*"
~~~

You can, alternatively, use the constructor to set each component patter:

~~~php
use League\Uri\UrlPatternBuilder;

$pattern = new UrlPatternBuilder(
    path: "/books/:id(\\d+)",
    host: "{*.}?example.com",
)->build();
~~~

This is an alternate rewrite of the previous pattern. Of course both notation can be used
simultaneously>

The `UrlPattern` returned by the `UrlPatternBuilder::build()` method will only match
the submitted URI against the specified URI components pattern. The `UrlPatternBuilder::build()`
takes two optional enums:

- to allow setting the case sensitivity via the  `UrlPattern\MatchMode` enum
- to force the UrlPattern object to be abosulte or not via The `UrlPattern\PatternType` enum.

~~~php
use League\Uri\UrlPattern\MatchMode;
use League\Uri\UrlPattern\PatternType;
use League\Uri\UrlPatternBuilder;

$pattern = new UrlPatternBuilder()
    ->path("/2022/feb/*")
    ->baseUrl("https://example.com/users")
    ->build(matchMode: MatchMode::CaseInsensitive);

$pattern->match("https://example.com/2022/feb/xc44rsz"); // true
$pattern->match("https://example.com/2022/Feb/xc44rsz"); // true
$pattern->matchMode; // MatchMode::CaseInsensitive
$pattern->type;      // PatternType::Absolute
~~~

In the example above if the return object was not an absolute URLPattern, the `build`
method would have thrown a `SyntaxError` exception.

## Extracted values

`League\Uri\UrlPattern::extract()`: 

- returns an `League\Uri\UrlPattern\Result` which is a container for every component individual results. Each result is a `League\Uri\UrlPattern\ComponentResult` instance.
- returns `null` if the submitted URI does not match the pattern
- throws an exception if the submitted URI is invalid

Each component of a `Result` is represented by a `ComponentResult` containing:

- the values extracted for that component
- the source pattern used to extract them.

~~~php
use League\Uri\UrlPatternBuilder;

$pattern = new UrlPatternBuilder(path: '/book/:id?')->build();
$pattern->hasVariable(); // returns true    
    
$result = $pattern->extract('https://example.com/books/123');
$result->hasValue();
// return true

$result->path->input; 
// '/book/123'

$result->path->variables(); 
// ["book" => "123"]
~~~

The `Result::hasValue()` method returns `true` if at least one `ComponentResult` instance has extracted values.

~~~php
$pattern = UrlPattern::from('/book/123', 'http://example,com');
$pattern->match('http://example,com/book/123');
// true

$result = $pattern->extract('http://example,com/book/123');
$result->hasValue();
// false
~~~

### Value presence

As shown previously, not all patterns yield values. To quickly determine whether a pattern can produce values during
extraction, you can use the `UrlPattern::hasVariable()` method. It returns `true` if the pattern contains a variable
that can be extracted, and `false` otherwise.

```php
use League\Uri\UrlPatternBuilder;

$pattern = new UrlPatternBuilder(host: 'www.example.com')
    ->build();

$pattern->hasVariable();
// false

$pattern->match('https://www.example.com/2022/feb/xc44rsz');
// true

$result = $pattern->extract('https://www.example.com/2022/feb/xc44rsz');
// $result is a Result instance whose ComponentResult objects contain no extracted values.
```

### Value Access

If the extraction was successful and values are present, the `ComponentResult::hasValue()` method will return `true`.
You may access each variable individually using the `ArrayAccess` interface.

~~~php
use League\Uri\UrlPatternBuilder;

// create a new instance
$pattern = new UrlPatternBuilder(path: '/book/:id?')->build();

// Since the URI matches we can extract the matching part
$result = $pattern->extract('https://example.com/books/123');
$result->host->hasValue(); 
// false 

$result->path->hasValue();
// true 

count($result->path); 
// 1

$result->path['id'];
// "123"
~~~

Extracted values are decoded according to the rules of their URL component. Percent-encoded
sequences are decoded before the values are returned.

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

$pattern = new UrlPatternBuilder(
    path: '/:foo',
    host: '{:foo}.example.com'
)
    ->build();
$result = $pattern->extract('http://xn--fi8h.example.com/%F0%9F%8D%85');
$result->path['foo'];
// 🍅
$result->host['foo'];
// 🍅
```

`ComponentResult::variables()` returns all extracted values as an associative array.
Each key is the variable name or index and each value is the corresponding extracted value.

```php
$result->variables();
// [
//   "hotel" => "Rest & Relax"
// ]
```

If no pattern is specified for a URL component, the `*` wildcard is used as
its component pattern. This allows the component's value to be
extracted when present via the `0` index.

```php
use League\Uri\UrlPatternBuilder;

$pattern = new UrlPatternBuilder(
    path: '/hello/{:name}',
    host: '{:subdomain.}?localhost',
    port: '*',
)->build();

$result = $pattern->extract('http://api.localhost:4000/hello/john?search=world');

$result->path->string('name', 'World');    // "john"
$result->host->string('subdomain', 'www'); // "api"
$result->query->string(0);                 // "search=world
$result->fragment->string(0);              // null
```

In this example, the `query` component has an implicit value, while
the `fragment` component does not because the URL contains no fragment.

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
$pattern = new UrlPatternBuilder(path: '/book/:id?')->build();

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
