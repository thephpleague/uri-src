---
layout: default
title: RFC3986 - RFC3987 URI Parser
---

URI Parser and Builder
=======

PHP has been relying on the `parse_url` function to split URI into its component. But the
function predates RFC3986 and as such does not fully comply to the specification. To work
around the limitation the tooklit provides the `League\Uri\UriString` class. It is a
user-land PHP URI parser and builder compliant with [RFC 3986](http://tools.ietf.org/html/rfc3986) and [RFC 3987](http://tools.ietf.org/html/rfc3987)
The class act as a drop-in replacement for PHP's `parse_url` feature.
The class provides other core features of RFC3986 like dot segment removal, URI resolution, 
and URI normalization.

## URI parsing

The parser is:

- RFC3986/RFC3987 compliant;
- returns all URI components (No extra parameters needed);
- the path component is never equal to `null`;
- makes a distinction between empty and undefined components;
- the parser throws a `League\Uri\Contracts\UriException` exception instead of returning `false` on error;

The class covers parsing URI in different context:

~~~php
UriString::parse(Stringable|string $uri): array
UriString::parseAuthority(Stringable|string $autority): array
UriString::parseNormalized(Stringable|string $uri): array
UriString::normalize(Stringable|string $uri): string
UriString::normalizeAuthority(Stringable|string $autority): string
UriString::resolve(Stringable|string $uri, Stringable|string|null $baseUri = null): string
~~~

The `Uri::parse` method is an RFC compliant replacement to `parse_url`. It returns the same array
but the parsed data is fully compliant with the RFC specificaition.

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
parses and extracts from the URI its components. Validating against scheme-specific rules is still a requirement.</p>

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

### URI resolution

<p class="message-notice">Available since version <code>7.6</code></p>

If you need to resolve your URI in the context of a Base URI the `resolve` public static method will let you
do just that. The method expects either a full URI as its single parameter or a relative URI followed by
a base URI which must be absolute, the URI will then be resolved using the base URI.

```php
$components = UriString::resolve("/foo", "https://example.com");
//returns "https://example.com/foo"
```

### URI normalization

It is possible to normalize a URI against the RFC3986 rules using two (2) methods:

- `UriString::normalize` which returns the normalized string.
- `UriString::parseNormalized`  which returns the same output as `Uri::parse` but each component is normalized.

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

$normalized = UriString::parseNormalized("https://EXAMPLE.COM/foo/../bar");
//returns the following array
//array(
//  'scheme' => 'https',
//  'user' => null,
//  'pass' => null,
//  'host' => 'example.com',
//  'port' => null,
//  'path' => '/bar',
//  'query' => null,
//  'fragment' => null,
//);

$normalizedUri = UriString::normalize("https://EXAMPLE.COM/foo/../bar");
//returns "https://example.com/bar"
```

<p class="message-notice">The class also exposes a <code>UriString::normalizeAuthority</code> method, you can use to normalize an authority string.</p>

## URI Building

~~~php
UriString::build(array $components): string
UriString::buildAuthority(array $components): string
UriString::buildUri(?string $scheme = null, ?string $authority = null, ?string $path = null, ?string $query = null, ?string $fragment = null): string
~~~

You can rebuild a URI from its hash representation returned by the `UriString::parse` method or PHP's `parse_url` function using the `UriString::build` public static method.  

<p class="message-notice">If you supply your own hash, you are responsible for providing valid encoded components without their URI delimiters.</p>

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

<p class="message-notice">The class also exposes a <code>UriString::buildAuthority</code> method you can use to build an authority from its hash representation.</p>
<p class="message-notice">The class also exposes a <code>UriString::buildUri</code> method which strictly follow the specification.</p>

## Removing dot segments

<p class="message-notice">Available since version <code>7.6</code></p>

To remove dot segments as per [RFC3986](https://tools.ietf.org/html/rfc3986#section-6), you need to explicitly call
the `UriString::removeDotSegments` method. The method takes a single argument the path string and returns
a new string which represents the path without dot segments.

~~~php
<?php

use League\Uri\UriString;

$path = UriString::removeDotSegments('path/to/./the/../the/sky%7bfoo%7d');
echo $path;  //displays 'path/to/the/sky%7Bfoo%7D'
~~~

## URI Validation

<p class="message-notice">Available since version <code>7.6</code></p>

The class exposes static methods to validate that a string:

- represents a valid scheme with the `isValidScheme()` method
- represents a valid host according to RFC3986/RFC3987 with the `isValidHost()` method
- only contains valid RFC3986 characters with the `containsRfc3986Chars()` method
- only contains valid RFC3987 characters with the `containsRfc3987Chars()` method

~~~php
<?php

use League\Uri\Uri;use League\Uri\UriString;

UriString::isValidScheme('foo '); //returns false because of the trailing space
UriString::isValidHost('333.333.333.1.333'); //returns true
UriString::containsRfc3986Chars('http://bébé.be'); //returns false non-ascii character are not allowed
UriString::containsRfc3987Chars('http://bébé.be'); //returns true
~~~
