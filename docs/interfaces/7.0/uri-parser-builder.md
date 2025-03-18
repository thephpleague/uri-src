---
layout: default
title: RFC3986 - RFC3987 URI Parser
---

URI Parser and Builder
=======

PHP has been relying on the `parse_url` function to split URI into its component. But the
function predates RFC3986 and as such does not fully comply to the specification. To work
around this limitation the tooklit provides the `League\Uri\UriString` class. It is a
user-land PHP URI parser and builder compliant with [RFC 3986](http://tools.ietf.org/html/rfc3986) and [RFC 3987](http://tools.ietf.org/html/rfc3987)
The class act as a drop-in replacement for PHP's `parse_url` feature.

## URI parsing

~~~php
UriString::parse(string $uri): array
UriString::parseAuthority(string $autority): array
UriString::normalize(string $uri): string
UriString::normalizeAuthority(string $autority): string
UriString::resolve(string $uri, ?string $baseUri = null): string
~~~

The parser is:

- RFC3986/RFC3987 compliant;
- returns all URI components (No extra parameters needed);
- the path component is never equal to `null`;
- makes a distinction between empty and undefined components;
- the parser throws a `League\Uri\Contracts\UriException` exception instead of returning `false` on error;

~~~php
<?php

use League\Uri\UriString;

var_export(UriString::parse('http://foo.com?@bar.com/#'));
//returns the following array
//array(
//  'scheme' => 'http',
//  'user' => null,
//  'pass' => null,
//  'host' => 'foo.com',
//  'port' => null,
//  'path' => '',
//  'query' => '@bar.com/',
//  'fragment' => '',
//);
~~~

<p class="message-warning">Just like <code>parse_url</code>, the <code>League\Uri\UriString</code> only
parses and extracts from the URI its components. Validating against scheme specific rules is still a requirement.</p>

~~~php
var_export(UriString::parse('http:www.example.com'));
//returns the following array
//array(
//  'scheme' => 'http',
//  'user' => null,
//  'pass' => null,
//  'host' => null,
//  'port' => null,
//  'path' => 'www.example.com',
//  'query' => null,
//  'fragment' => null,
//);
~~~

<p class="message-warning">This invalid HTTP URI is successfully parsed.</p>
<p class="message-notice">The class also exposes a <code>UriString::parseAuthority</code> you can use to parse an authority string.</p>

If you need to resolve your URI in the context of a Base URI the `resolve` public static method will let you
do just that. The method expect either a full URI as its single parameter or a relative URI following by
a base URI which must be absolute, the URI will then be resolved using the base URI.

```php
$components = UriString::resolve("/foo", "https://example.com");
//returns "https://example.com/foo"
```

It is possible to normalize a URI against the RFC3986 rules using the `UriString::normalize` method.
The method expects a string and will return the same array as `UriString::parse` but each component will
have been normalized.

```php
use League\Uri\UriString;

$parsed = UriString::parse("https://EXAMPLE.COM/foo/../bar");
//returns the following array
//array(
//  'scheme' => 'http',
//  'user' => null,
//  'pass' => null,
//  'host' => 'EXAMPLE.COM',
//  'port' => null,
//  'path' => '/foo/../bar',
//  'query' => null,
//  'fragment' => null,
//);

$normalized = UriString::normalize("https://EXAMPLE.COM/foo/../bar");
//returns "https://example.com/bar"
```

## URI Building

~~~php
UriString::build(array $components): string
UriString::buildAuthority(array $components): string
UriString::buildUri(?string $scheme, ?string $authority, string $path, ?string $query, ?string $fragment): string
~~~

You can rebuild a URI from its hash representation returned by the `UriString::parse` method or PHP's `parse_url` function using the `UriString::build` public static method.  

<p class="message-notice">If you supply your own hash you are responsible for providing valid encoded components without their URI delimiters.</p>

~~~php
$components = UriString::parse('http://hello:world@foo.com?@bar.com/');
//returns the following array
//array(
//  'scheme' => 'http',
//  'user' => 'hello',
//  'pass' => 'world',
//  'host' => 'foo.com',
//  'port' => null,
//  'path' => '',
//  'query' => '@bar.com/',
//  'fragment' => null,
//);

echo UriString::build($components); //displays http://hello:world@foo.com?@bar.com/
~~~

The `build` method provides similar functionality to the `http_build_url()` function from v1.x of the [`pecl_http`](https://pecl.php.net/package/pecl_http) PECL extension.

<p class="message-notice">The class also exposes a <code>UriString::buildAuthority</code> you can use to build an authority from its hash representation.</p>
