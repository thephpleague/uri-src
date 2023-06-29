---
layout: default
title: URI resolver - relativizer
---

BaseUri
=======

The `League\Uri\BaseUri` class enable resolving or relativizing a URI against a base URI. 

<p class="message-notice">All the methods accepts string or Stringable objects like the PSR-7 or League own <code>UriInterface</code> implementing class.</p>

## Instantiation

While the default constructor is private and can not be accessed to instantiate a new object,
the `League\Uri\BaseUri` class comes with the following named constructor.

~~~php
<?php

public static function Http::new(Stringable|string $uri = ''): self
~~~

## Resolving a relative URI

The `BaseUri::resolve` method provides the mean of resolving a URI as a
browser would for a relative URI. When performing URI resolution the returned URI is
normalized according to RFC3986 rules.

~~~php
<?php

use League\Uri\Http;
use League\Uri\BaseUri;

$baseUri = BaseUri::new("http://www.example.com/path/to/the/sky/");
$newUri = $baseUri->resolve("./p#~toto"); //returns an League\Uri\Uri object

echo $newUri; //displays "http://www.example.com/path/to/the/sky/p#~toto"
~~~

## Relativize an URI

The `BaseUri::relativize` method provides the mean to construct a relative
URI that when resolved against the same URI yields the same given URI. This
modifier does the inverse of the `resolve` method.

~~~php
<?php

use League\Uri\Http;
use League\Uri\BaseUri;

$baseUri = BaseUri::new('http://www.example.com');
$uri = 'http://www.example.com/?foo=toto#~typo';

$relativeUri = $baseUri->relativize($uri);
echo $relativeUri; // display "/?foo=toto#~typo
echo $baseUri->resolve($relativeUri);
// display 'http://www.example.com/?foo=toto#~typo'
~~~
