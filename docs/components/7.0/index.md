---
layout: default
title: URI components
redirect_from:
    - /components/
---

Uri Components
=======

[![Latest Version](https://img.shields.io/github/release/thephpleague/uri-components.svg?style=flat-square)](https://github.com/thephpleague/uri-components/releases)

While working with URI, you may stumble on some tasks, such as parsing its query string or updating its host,
that are not covered by the URI package.
Thankfully, the URI component package allows you to easily parse, create, manipulate URI component as well as partially
update URIs. By using the package, your application can safely perform tasks around your URIs and provide a better 
user experience to your developers.

The League URI components provides at the same time a unified way to access all URI components while exposing more
specific methods to regularly used components like URI queries, URI domains and URI paths.

~~~php
use League\Uri\Components\Query;
use League\Uri\UriModifier;

$newUri = UriModifier::appendQuery('http://example.com?q=value#fragment', 'q=new.Value');
echo $newUri->toString(); // 'http://example.com?q=value&q=new.Value#fragment';

$query = Query::fromUri($newUri);
$query->get('q');    // returns 'value'
$query->getAll('q'); // returns ['value', 'new.Value']
$query->params('q'); // returns 'new.Value'
~~~

System Requirements
-------

You need **PHP >= 8.1.0** but the latest stable version of PHP is recommended

If you want to handle:

- Data URI creation from a file content **requires** the `fileinfo` extension.
- IDN host you are **required** to install the `intl` extension;
- IPv4 host in octal or hexadecimal form, out of the box, you **need** at least one of the following extension:

    - install the `GMP` extension **or**
    - install the `BCMath` extension
    
   or you should be using
   
    - a `64-bits` PHP version

Trying to process such URI components without meeting those minimal requirements will trigger a `RuntimeException`.

Installation
--------

~~~
$ composer require league/uri-components:^7.0
~~~

Dependencies
-------

- [League Uri Interfaces](https://github.com/thephpleague/uri-interfaces)
- [League Uri](https://github.com/thephpleague/uri)
- [PSR-7](http://www.php-fig.org/psr/psr-7/)

What you will be able to do
--------

- Build and parse query with [QueryString](/components/7.0/query-parser-builder/)
- Partially modify URI with [URI Modifiers](/components/7.0/modifiers/)
- Create and Manipulate URI components objects with a [Common API](/components/7.0/api/)
