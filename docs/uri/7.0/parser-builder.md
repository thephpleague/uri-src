---
layout: default
title: RFC3986 - RFC3987 Parser
---

URI parser and builder
=======

The `League\Uri\UriString` class is a userland PHP URI parser and builder compliant with [RFC 3986](http://tools.ietf.org/html/rfc3986) and [RFC 3987](http://tools.ietf.org/html/rfc3987) to replace PHP's `parse_url` function.

## URI parsing

~~~php
UriString::parse(string $uri): array
UriString::parseAuthority(string $autority): array
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
$uri = 'http:www.example.com';
var_export(UriString::parse($uri));
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

## URI Building

~~~php
UriString::build(array $components): string
UriString::buildAuthority(array $components): string
~~~

You can rebuild a URI from its hash representation returned by the `UriString::parse` method or PHP's `parse_url` function using the `UriString::build` public static method.  

<p class="message-notice">If you supply your own hash you are responsible for providing valid encoded components without their URI delimiters.</p>

~~~php
$base_uri = 'http://hello:world@foo.com?@bar.com/';
$components = UriString::parse($base_uri);
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
