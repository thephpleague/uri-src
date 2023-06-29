---
layout: default
title: URI resolver - relativizer
---

BaseUri
=======

The `League\Uri\BaseUri` class is build to ease resolving or relativizing a URI against a base URI. 
The class makes it easier to transform multiple URI against the same Base URI (ie: a web page, or an HTTP client for instance).

The `BaseUri::resolve` resolves a URI as a browser would for a relative URI while the
`BaseUri::relativize` does the inverse.

~~~php
<?php

use League\Uri\Contracts\UriInterface;
use Psr\Http\Message\UriInterface as Psr7UriInterface;

public static function BaseUri::new(Stringable|string $baseUri): self
public function BaseUri::resolve(Stringable|string $uri): UriInterface|Psr7UriInterface
public function BaseUri::relativize(Stringable|string $uri): UriInterface|Psr7UriInterface
public readonly UriInterface BaseUri::$value;
~~~

<p class="message-notice">All the methods accepts string or Stringable objects like the PSR-7 or League own <code>UriInterface</code> implementing class.</p>

<p class="message-notice">If a PSR-7 <code>UriInterface</code> is given then the return value will also be
a URI object from the same class, otherwise it will be a League <code>Uri</code> instance.</p>

## Usage

~~~php
<?php

use League\Uri\BaseUri;

$baseUri = BaseUri::new('http://www.ExaMPle.com');
$uri = 'http://www.example.com/?foo=toto#~typo';

$relativeUri = $baseUri->relativize($uri);
echo $relativeUri; // display "/?foo=toto#~typo
echo $baseUri->resolve($relativeUri);
echo $baseUri->value->toString(); // display 'http://www.example.com'
// display 'http://www.example.com/?foo=toto#~typo'
~~~
