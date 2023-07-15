---
layout: default
title: PSR-7 compliant URI Object API
---

PSR-7 URI
=======

The package provides an expressive and efficient API around building and manipulating URI compliant
with [PSR-7](https://www.php-fig.org/psr/psr-7/). It allows the easy creation of URI in multiple contexts 
to increase your DX while working with URI.

<p class="message-notice">The class handles all URI schemes and default to HTTP(s) rules if the
scheme is not present and not recognized as special.</p>

## Instantiation

While the default constructor is private and can not be accessed to instantiate a new object,
the `League\Uri\Http` class comes with named constructors to ease instantiation.

The following examples show how to use the different named constructors:

~~~php
<?php

use League\Uri\Http;
use League\Uri\UriString;
use Laminas\Diactoros\Uri as LaminasUri;

// using a string or an object which expose the `__toString` method

$uri = Http::new('http://example.com/path/to?q=foo%20bar#section-42');
$uri->toString(); // display 'http://example.com/path/to?q=foo%20bar#section-4'

$laminasUri = new LaminasUri("http://www.example.com/path/to/the/sky");
$laminasUri->getQuery(); //return '';

Http::new($laminasUri)->getQuery(); //return '';

// using `parse_url` or the package `UriString::parse` static method.

$uri = Http::fromComponents(UriString::parse("http://uri.thephpleague/7.0/uri/api"));

//don't forget to provide the $_SERVER array
$uri = Http::fromServer($_SERVER);
~~~

<p class="message-warning">If you supply your own hash to <code>fromComponents</code>,
you are responsible for providing well parsed components without their URI delimiters.</p>

<p class="message-warning">The <code>fromServer</code> method only relies on the server's 
safe parameters to determine the current URI. If you are using the library behind a 
proxy the result may differ from your expectation as no <code>$_SERVER['HTTP_X_*']</code>
header is taken into account for security reasons.</p>

You can also return a URI based on standard specifications:

~~~php
<?php

use League\Uri\Http;

$uri = Http::fromBaseUri("./p#~toto", "http://www.example.com/path/to/the/sky/");
echo $uri; //displays "http://www.example.com/path/to/the/sky/p#~toto"

$template = 'https://example.com/hotels/{hotel}/bookings/{booking}';
$variables = ['booking' => '42', 'hotel' => 'Rest & Relax'];
echo Http::fromTemplate($template, $variables)->toString();
//displays "https://example.com/hotels/Rest%20%26%20Relax/bookings/42"
~~~

The `fromBaseUri` method resolves URI using the same logic behind URL construction
in a browser and [is inline with how the Javascript](https://developer.mozilla.org/en-US/docs/Web/API/URL/URL) `URL` object constructor works.
If no base URI is provided, the URI to resolve **MUST** be absolute. Otherwise, the base URI **MUST** be absolute.

The `fromTemplate` method resolves a URI using the rules and variable from the
[URITemplate specification RFC6570](http://tools.ietf.org/html/rfc6570):
The method expects at most two parameters. The URI template to resolve and the variables use
for resolution. You can get a more in-depth understanding of
[URI Template](/uri/7.0/uri-template) in its dedicated section of the documentation.

## Relation with PSR-7

The `Http` class implements the PSR-7 `UriInterface` interface. This means that you can 
use this class anytime you need a PSR-7 compliant URI object.

## URI normalization

Out of the box the package normalizes any given URI according to the non-destructive rules
of [RFC3986](https://tools.ietf.org/html/rfc3986).

These non-destructive rules are:

- scheme and host components are lowercase;
- the host is converted to its ascii representation using punycode if needed
- query, path, fragment components are URI encoded if needed;
- the port number is removed from the URI string representation if the standard port is used;

~~~php
<?php

use League\Uri\Http;

$uri = Http::new("hTTp://www.ExAmPLE.com:80/hello/./wor ld?who=f 3#title");
echo $uri; //displays http://www.example.com/hello/./wor%20ld?who=f%203#title

$uri = Http::fromComponent(parse_url("hTTp://www.bébé.be?#"));
echo $uri; //displays http://xn--bb-bjab.be?#
~~~

<p class="message-info">The last example depends on the presence of the <code>ext-intl</code> extension, otherwise the code will trigger a <code>IdnSupportMissing</code> exception</p>

In addition to PSR-7 compliance, the class implements PHP's `JsonSerializable` interface.

~~~php
<?php

echo json_encode(\League\Uri\Http::new('http://example.com/path/to?q=foo%20bar#section-42'));
// display "http:\/\/example.com\/path\/to?q=foo%20bar#section-42"
~~~
