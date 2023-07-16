---
layout: default
title: Upgrading from 6.x to 7.x
---

# Upgrading from 6.x to 7.x

`League\Uri 7.0` is a new major version that comes with backward compatibility breaks.

This guide will help you migrate from a 6.x version to 7.0. It will only explain backward compatibility breaks, it will not present the new features ([read the documentation for that](/7.0/)).

## Installation

If you are using composer then you should update the `require` section of your `composer.json` file.

~~~
composer require league/uri:^7.0
~~~

This will edit (or create) your `composer.json` file.

## PHP version requirement

`League\Uri 7.0` requires a PHP version greater or equal than 8.1.0 (was previously 7.2.0).

## Signature changes

`UriTemplate::expand` use to return a `League\Uri\Contracts\UriInterface` implementing class.
Starting with version `7.0.0` it will return a string. It is still possible to retain the old 
behaviour:

Before:

~~~php
<?php

use League\Uri\Uri;
use League\Uri\UriTemplate;

$template = 'https://example.com/hotels/{hotel}/bookings/{booking}';
$params = ['booking' => '42', 'hotel' => 'Rest & Relax'];

$uriTemplate = new UriTemplate($template);
$uriTemplate->expand($params); //instance of League\Uri\Uri
~~~

After:

~~~php
<?php

use League\Uri\Uri;
use League\Uri\UriTemplate;

$template = 'https://example.com/hotels/{hotel}/bookings/{booking}';
$params = ['booking' => '42', 'hotel' => 'Rest & Relax'];

$uriTemplate = new UriTemplate($template);
Uri::fromTemplate($uriTemplate, $params);  //instance of League\Uri\Uri
~~~

## Deprecated methods

The following methods are marked as deprecated. They are still present to allow an easier upgrade path
to version `7.0`, but it is recommended not to use them for new projects.

| Deprecated methods           | New stable methods      |
|------------------------------|-------------------------|
| `Uri::createFromString`      | `Uri::new`              |
| `Uri::createFromUri`         | `Uri::new`              |
| `Uri::createFromComponents`  | `Uri::fromComponents`   |
| `Uri::createFromServer`      | `Uri::fromServer`       |
| `Uri::createFromBaseUri`     | `Uri::fromBaseUri`      |
| `Uri::createFromDataPath`    | `Uri::fromFileContents` |
| `Uri::createFromUnixPath`    | `Uri::fromUnixPath`     |
| `Uri::createFromWindowsPath` | `Uri::fromWindowsPath`  |
| `Http::createFromString`     | `Http::new`             |
| `Http::createFromUri`        | `Http::new`             |
| `Http::createFromComponents` | `Http::fromComponents`  |
| `Http::createFromServer`     | `Http::fromServer`      |
| `Http::createFromBaseUri`    | `Http::fromBaseUri`     |

## Deprecated Classes

The `League\Uri\UriResolver` class is deprecated in favor of the `League\Uri\BaseUri` class:

Before:

~~~php
<?php

use League\Uri\Uri;
use League\Uri\UriResolver;

$uri = Uri::createFromString('http://www.example.com/?foo=toto#~typo');
$baseUri = Uri::createFromString('http://www.example.com');

$relativeUri = UriResolver::relativize($uri, $baseUri);
echo $relativeUri; // display "/?foo=toto#~typo
echo UriResolver::resolve($relativeUri, $baseUri);
// display 'http://www.example.com/?foo=toto#~typo'
~~~

After:

~~~php
<?php

use League\Uri\BaseUri;

$uri = 'http://www.example.com/?foo=toto#~typo';
$baseUri = BaseUri::new('http://www.example.com');

$relativeUri = $baseUri->relativize($uri);
echo $relativeUri; // display "/?foo=toto#~typo
echo $baseUri->resolve($relativeUri);
// display 'http://www.example.com/?foo=toto#~typo'
~~~

The `League\Uri\UriInfo` class is deprecated in favor of the `League\Uri\BaseUri` class:

Before:

~~~php
<?php

use League\Uri\Http;
use League\Uri\Uri;
use League\Uri\UriIfo;

UriInfo::isNetworkPath(Http::createFromString("//example.com/toto")); //returns true
UriInfo::isNetworkPath(Uri::createFromString("/🍣🍺")); //returns false
UriInfo::isSameDocument(
    Http::createFromString("example.com?foo=bar#🏳️‍🌈"),
    Http::createFromString("exAMpLE.com?foo=bar#🍣🍺")
); //returns true
UriInfo::getOrigin(Uri::createFromString('blob:https://mozilla.org:443')); //returns 'https://mozilla.org'
UriInfo::getOrigin(Http::createFromString('file:///usr/bin/php')); //returns null
~~~

After:

~~~php
<?php

use League\Uri\BaseUri;

BaseUri::new("//example.com/toto")->isNetworkPath(); //returns true
BaseUri::new("/🍣🍺")->isNetworkPath(); //returns false
BaseUri::new("example.com?foo=bar#🏳️‍🌈")->isSameDocument("exAMpLE.com?foo=bar#🍣🍺"); //returns true
BaseUri::new(Uri::new('blob:https://mozilla.org:443'))->origin(); //returns BaseUri::new(Uri::new('https://mozilla.org'))
BaseUri::new(Http::new('file:///usr/bin/php'))->origin(); //returns null
~~~

All the static public methods are now attached to the `BaseUri` as method to the instantiated object.

## Removed functionalities

- `__set_state` named constructors is removed without replacements.

| Removed Classes                                  | New Classes                                       |
|--------------------------------------------------|---------------------------------------------------|
| `League\Uri\Exceptions\TemplateCanNotBeExpanded` | `League\Uri\UriTemplate\TemplateCanNotBeExpanded` |
