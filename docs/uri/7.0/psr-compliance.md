---
layout: default
title: PSR-7 compliant URI Object API
---

PSR interoperability
=======

As we are dealing with URI, the package provides classes compliant
with [PSR-7](https://www.php-fig.org/psr/psr-7/) and [PSR-17](https://www.php-fig.org/psr/psr-17/) and [PSR-13](https://www.php-fig.org/psr/psr-13/). This
is done to allow more interoperability amongst PHP packages.

## PSR-7 compatibility

The `Http` class implements the PSR-7 `UriInterface` interface. This means that you can
use this class anytime you need a PSR-7 compliant URI object. Behind the scene, the
implementation uses the [Uri object](/uri/7.0/rfc3986/) and thus presents the same
features around URI validation, modification and normalization.

<p class="message-notice">The class handles all URI schemes <strong>BUT</strong> default
to HTTP(s) rules if the scheme is not present and not recognized as special.</p>

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
echo $uri; // display 'http://example.com/path/to?q=foo%20bar#section-4'

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
$uri = Http::fromBaseUri("./p#~toto", "http://www.example.com/path/to/the/sky/");
echo $uri; //displays "http://www.example.com/path/to/the/sky/p#~toto"

$template = 'https://example.com/hotels/{hotel}/bookings/{booking}';
$variables = ['booking' => '42', 'hotel' => 'Rest & Relax'];
echo Http::fromTemplate($template, $variables);
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

In addition to PSR-7 compliance, the class implements PHP's `JsonSerializable` interface.

~~~php
echo json_encode(Http::new('http://example.com/path/to?q=foo%20bar#section-42'));
// display "http:\/\/example.com\/path\/to?q=foo%20bar#section-42"
~~~

<p class="message-notice">The <code>when</code> method is available since version <code>7.6.0</code></p>
To ease building the instance, the `when` method is added to conditionally create your component.

```php
use League\Uri\Http;

$foo = 'boo';
echo Http::new('https://uri.thephpleague.com/components/7.0/modifiers/')
    ->when(
        '' !== $foo, 
        fn (Http $uri) => $uri->withPath('/'.$foo),  //on true
        fn (Http $uri) => $uri->withPath('/default'), //on false
    )
    ->toString();
// returns 'https://uri.thephpleague.com/boo';
```

### Differences with the Generic RFC3986 URI

Because of its normalization rules a `PSR-7` UriInterface implementing object
may return a different URI representation than a generic URI implementing class.

~~~php
echo (string) Http::new('http://example.com/path/to?#');
// returns 'http://example.com/path/to

echo (string) Uri::new('http://example.com/path/to?#');
// returns 'http://example.com/path/to?#'
~~~

<p class="message-info">This improved compliance is available since version <code>7.5.0</code></p>

## PSR-17 compatibility

The package also provides an implementation of the `UriFactoryInterface` from [PSR-17](https://www.php-fig.org/psr/psr-17/)

~~~php
<?php
use League\Uri\HttpFactory;

$uriFactory = new HttpFactory();
$uri = $uriFactory->createUri('http://example.com/path/to?q=foo%20bar#section-42');
echo $uri::class; // display League\Uri\Http
~~~

## PSR-13 compatibility

<p class="message-notice">Available since <code>version 7.6</code></p>

To allow easier integration with other PHP packages and especially [PSR-13](https://www.php-fig.org/psr/psr-13/)
the `UriTemplate` class implements the `Stringable` interface.

~~~php
use League\Uri\UriTemplate;
use Symfony\Component\WebLink\Link;

$uriTemplate = new UriTemplate('https://google.com/search{?q*}');

$link = (new Link())
    ->withHref($uriTemplate)
    ->withRel('next')
    ->withAttribute('me', 'you');

// Once serialized will return
// '<https://google.com/search{?q*}>; rel="next"; me="you"'
~~~

The `Symfony\Component\WebLink\Link` package implements `PSR-13` interfaces.

<p class="message-info">You could already use a <code>Uri</code> instance if the link must use a
concrete URI instead as the class also implements the <code>Stringable</code> interface.</p>
