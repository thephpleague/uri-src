---
layout: default
title: URI context aware wrapper
---

BaseUri
=======

The `League\Uri\BaseUri` class is build to ease gathering information regarding a specific URI. 
The class makes it easier to transform and crawl pages containing URIs (ie: a web page, or an HTTP client for instance).

<p class="message-warning">While the class does manipulate URI it does not implement any URI related interface.</p>
<p class="message-notice">All the methods accepts string or Stringable objects.</p>
<p class="message-notice">If a PSR-7 <code>UriInterface</code> implementing instance is given then the return value
will also be a PSR-7 <code>UriInterface</code> implementing instance.</p>

## Instantiation

Instantiation is done via the `BaseUri::from` named constructor which accepts string and stringable objects alike.
Once instantiated you can get access to its underlying URI instance via the public method `BaseUri::get()`.
if a Psr7 implementing object was use for instantiation, the same instance will be return by the method.

~~~php
<?php

use League\Uri\BaseUri;
use GuzzleHttp\Psr7\Utils;

$baseUri = BaseUri::from('http://www.ExaMPle.com');
$baseUri->getUri(); // return Uri::new('http://www.ExaMPle.com');

$baseUriPsr7 = BaseUri::from(Utils::uriFor('http://www.ExaMPle.com'));
$baseUri->getUri(); // return new GuzzleHttp\Psr7\Uri('http://www.example.com/?foo=toto#~typo');
~~~

## URI resolution

The `BaseUri::resolve` resolves a URI as a browser would for a relative URI while the `BaseUri::relativize` does the opposite.

~~~php
<?php

use League\Uri\BaseUri;

$baseUri = BaseUri::from('http://www.ExaMPle.com');
$uri = 'http://www.example.com/?foo=toto#~typo';

$relativeUri = $baseUri->relativize($uri);
echo $relativeUri; // display "/?foo=toto#~typo
echo $baseUri->resolve($relativeUri);
echo $baseUri; // display 'http://www.example.com'
// display 'http://www.example.com/?foo=toto#~typo'
echo $baseUri::class; //display \League\Uri\Uri
~~~

Out of the box when submitting an object other than a `PSR-7 UriInterface`
the returned URI object will be a `League\Uri\Uri` instance. You can control this behaviour by registering
a `PSR-7 UriFactoryInterface` on `BaseUri` instantiation

~~~php
<?php

use League\Uri\BaseUri;
use GuzzleHttp\Psr7\HttpFactory;

$baseUri = BaseUri::from('http://www.ExaMPle.com', new HttpFactory());
$uri = 'http://www.example.com/?foo=toto#~typo';

$relativeUri = $baseUri->relativize($uri);
echo $relativeUri;               // display "/?foo=toto#~typo"
echo $relativeUri::class;        // display '\Leage\Uri\BaseUri'
echo $relativeUri->getUri()::class; //display \GuzzleHttp\Psr7\Uri

$resolvedUri = $baseUri->withoutUriFactory()->resolve("/?foo=toto#~typo");
echo $resolvedUri;               // display 'http://www.example.com/?foo=toto#~typo'
echo $resolvedUri::class;        // display '\Leage\Uri\BaseUri'
echo $resolvedUri->getUri()::class; // display \League\Uri\Uri
~~~

You can always switch back to using the `Uri` object by unregistering the factory using `BaseUri::withoutUriFactory`.

## URI information

The class contains a list of public methods which returns the URI state.

### BaseUri::isAbsolute

Tells whether the URI represents an absolute URI.

~~~php
<?php

use League\Uri\Uri;
use League\Uri\BaseUri;

BaseUri::from(Uri::fromServer($_SERVER))->isAbsoulte(); //returns true
BaseUri::from("/🍣🍺")->isAbsolute(); //returns false
~~~

### BaseUri::isAbsolutePath

Tells whether the URI represents an absolute URI path.

~~~php
BaseUri::from(Uri::fromServer($_SERVER))->isAbsolutePath(); //returns false
BaseUri::from(Http::new("/🍣🍺"))->isAbsolutePath(); //returns true
~~~

### BaseUri::isNetworkPath

Tells whether the URI represents a network path URI.

~~~php
BaseUri::from("//example.com/toto")->isNetworkPath(); //returns true
BaseUri::from("/🍣🍺")->isNetworkPath(); //returns false
~~~

### BaseUri::isRelativePath

Tells whether the given URI object represents a relative path.

~~~php
BaseUri::from("🏳️‍🌈")->isRelativePath(); //returns true
BaseUri::from("/🍣🍺")->isRelativePath(); //returns false
~~~

### BaseUri::isSameDocument

Tells whether the given URI object represents the same document.

~~~php
BaseUri::from(Http::new("example.com?foo=bar#🏳️‍🌈"))->isSameDocument("exAMpLE.com?foo=bar#🍣🍺"); //returns true
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

BaseUri::from(Utils::uriFor('blob:http://xn--bb-bjab.be./path'))
    ->isCrossOrigin(new Uri('http://Bébé.BE./path')); // returns false

BaseUri::from('https://example.com/123')
    ->isCrossOrigin(new Uri('https://www.example.com/')); // returns true
~~~

The method takes into account i18n while comparing both URI if the `intl-extension` is installed.

### BaseUri::origin

Returns the URI origin used for comparison when calling the `isCrossOrigin` method. The algorithm used is defined by
the [WHATWG URL Living standard](https://url.spec.whatwg.org/#origin)

~~~php
<?php

use League\Uri\Http;
use League\Uri\Uri;
use League\Uri\BaseUri;

echo BaseUri::from(Http::new('https://uri.thephpleague.com/uri/6.0/info/'))->origin(); //display 'https://uri.thephpleague.com';
echo BaseUri::from('blob:https://mozilla.org:443')->origin();       //display 'https://mozilla.org'
BaseUri::from(Uri::new('file:///usr/bin/php'))->origin();           //returns null
BaseUri::from('data:text/plain,Bonjour%20le%20monde%21')->origin(); //returns null
~~~

<p class="message-info">For absolute URI with the <code>file</code> scheme the method will return <code>null</code> (as this is left to the implementation decision)</p>
Because the origin property does not exist in the RFC3986 specification this additional steps is implemented:

- For non-absolute URI the method will return `null`

~~~php
<?php

use League\Uri\Http;
use League\Uri\BaseUri;

BaseUri::from((Http::new('/path/to/endpoint'))->origin(); //returns null
~~~
