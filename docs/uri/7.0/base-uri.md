---
layout: default
title: URI resolver - relativizer
---

BaseUri
=======

The `League\Uri\BaseUri` class is build to ease gathering information regarding a specific URI. 
The class makes it easier to transform and crawl pages containing URIs (ie: a web page, or an HTTP client for instance).

<p class="message-warning">While the class does manipulate URI it does not implement any URI related interface.</p>

## Public API

~~~php
<?php

use League\Uri\Contracts\UriInterface;
use Psr\Http\Message\UriInterface as Psr7UriInterface;

public static function BaseUri::new(Stringable|string $baseUri): self
public function BaseUri::uri(): Psr7UriInterface|UriInterface
public function BaseUri::origin(): ?self
public function BaseUri::resolve(Stringable|string $uri): self
public function BaseUri::relativize(Stringable|string $uri): self
public function BaseUri::isAbsolute(): bool
public function BaseUri::isNetworkPath(): bool
public function BaseUri::isAbsolutePath(): bool
public function BaseUri::isRelativePath(): bool
public function BaseUri::isSameDocument(Stringable|string $uri): bool
public function BaseUri::isCrossOrigin(Stringable|string $uri): bool
public function BaseUri::jsonSerialize(): string
public function BaseUri::__toString(): string
~~~

<p class="message-notice">All the methods accepts string or Stringable objects like the PSR-7 or League own <code>UriInterface</code> implementing class.</p>

<p class="message-notice">If a PSR-7 <code>UriInterface</code> is given then the return value will also be
a URI object from the same class, otherwise it will be a League <code>Uri</code> instance.</p>

## Usage

Once instantiated you can get access to its underlying URI instance via the public method `BaseUri::uri()`.
if a Psr7 implementing object was use for instantiation, the same instance
will be return by the property.

~~~php
<?php

use League\Uri\BaseUri;
use GuzzleHttp\Psr7\Utils;

$baseUri = BaseUri::new('http://www.ExaMPle.com');
$baseUri->uri(); // return Uri::new('http://www.ExaMPle.com');

$baseUriPsr7 = BaseUri::new(Utils::uriFor('http://www.ExaMPle.com'));
$baseUri->uri(); // return new GuzzleHttp\Psr7\Uri('http://www.example.com/?foo=toto#~typo');
~~~

### URI resolution

The `BaseUri::resolve` resolves a URI as a browser would for a relative URI while the
`BaseUri::relativize` does the inverse.

~~~php
<?php

use League\Uri\BaseUri;

$baseUri = BaseUri::new('http://www.ExaMPle.com');
$uri = 'http://www.example.com/?foo=toto#~typo';

$relativeUri = $baseUri->relativize($uri);
echo $relativeUri; // display "/?foo=toto#~typo
echo $baseUri->resolve($relativeUri);
echo $baseUri; // display 'http://www.example.com'
// display 'http://www.example.com/?foo=toto#~typo'
~~~

The class contains a list of public methods which returns the URI state.

### BaseUri::isAbsolute

Tells whether the URI represents an absolute URI.

~~~php
<?php

use League\Uri\Uri;
use League\Uri\BaseUri;

BaseUri::new(Uri::fromServer($_SERVER))->isAbsoulte(); //returns true
BaseUri::new("/🍣🍺")->isAbsolute(); //returns false
~~~

### BaseUri::isAbsolutePath

Tells whether the URI represents an absolute URI path.

~~~php
BaseUri::new(Uri::fromServer($_SERVER))->isAbsolutePath(); //returns false
BaseUri::new(Http::new("/🍣🍺"))->isAbsolutePath(); //returns true
~~~

### BaseUri::isNetworkPath

Tells whether the URI represents a network path URI.

~~~php
BaseUri::new("//example.com/toto")->isNetworkPath(); //returns true
BaseUri::new("/🍣🍺")->isNetworkPath(); //returns false
~~~

### BaseUri::isRelativePath

Tells whether the given URI object represents a relative path.

~~~php
BaseUri::new("🏳️‍🌈")->isRelativePath(); //returns true
BaseUri::new("/🍣🍺")->isRelativePath(); //returns false
~~~

### BaseUri::isSameDocument

Tells whether the given URI object represents the same document.

~~~php
BaseUri::new(Http::new("example.com?foo=bar#🏳️‍🌈"))->isSameDocument("exAMpLE.com?foo=bar#🍣🍺"); //returns true
~~~

### BaseUri::origin

Returns the URI origin as defined by the [WHATWG URL Living standard](https://url.spec.whatwg.org/#origin)

~~~php
<?php

use League\Uri\Http;
use League\Uri\Uri;
use League\Uri\BaseUri;

BaseUri::new(Http::new('https://uri.thephpleague.com/uri/6.0/info/'))->origin(); //returns BaseUri::new(Http::new('https://uri.thephpleague.com'));
BaseUri::new('blob:https://mozilla.org:443')->origin(); //returns  BaseUri::new('https://mozilla.org')
BaseUri::new(Uri::new('file:///usr/bin/php'))->origin(); //returns null
BaseUri::new('data:text/plain,Bonjour%20le%20monde%21')->origin(); //returns null
~~~

<p class="message-info">For absolute URI with the <code>file</code> scheme the method will return <code>null</code> (as this is left to the implementation decision)</p>

Because the origin property does not exist in the RFC3986 specification this additional steps is implemented:

- For non-absolute URI the method will return `null`

~~~php
<?php

use League\Uri\Http;
use League\Uri\BaseUri;

BaseUri::new((Http::new('/path/to/endpoint'))->origin(); //returns null
~~~

### BaseUri::isCrossOrigin

Tells whether the given URI object represents different origins.
According to [RFC9110](https://www.rfc-editor.org/rfc/rfc9110#section-4.3.1) The "origin"
for a given URI is the triple of scheme, host, and port after normalizing
the scheme and host to lowercase and normalizing the port to remove
any leading zeros.

~~~php
<?php

use GuzzleHttp\Psr7\Utils;
use League\Uri\BaseUri;
use Nyholm\Psr7\Uri;

BaseUri::new(Utils::uriFor('blob:http://xn--bb-bjab.be./path'))
    ->isCrossOrigin(new Uri('http://Bébé.BE./path')); // returns false

BaseUri::new('https://example.com/123')
    ->isCrossOrigin(new Uri('https://www.example.com/')); // returns true
~~~

The method takes into account i18n while comparing both URI if the `intl-extension` is installed.
