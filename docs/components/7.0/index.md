---
layout: default
title: URI components
redirect_from:
    - /components/
---

Uri Components
=======

Introduction
-------

[![Latest Version](https://img.shields.io/github/release/thephpleague/uri-components.svg?style=flat-square)](https://github.com/thephpleague/uri-components/releases)

While working with URI, you may stumble on some tasks, such as parsing its query string or updating its host,
that are not covered by the [URI package](/uri/7.0/).
Thankfully, the URI component package allows you to easily parse, create, manipulate URI components as well as partially
update URIs. By using the package, your application can safely perform tasks around your URIs and provide a better 
user experience to your developers.

~~~php
use League\Uri\Components\Query;
use League\Uri\Modifier;

$newUri = Modifier::from('http://example.com?q=value#fragment')
    ->appendQuery('q=new.Value');
echo $newUri; // 'http://example.com?q=value&q=new.Value#fragment';

$query = Query::fromUri($newUri);
$query->get('q');       // returns 'value'
$query->getAll('q');    // returns ['value', 'new.Value']
$query->parameter('q'); // returns 'new.Value'
~~~

The package provides easy to use classes [to partially modify a URI](/components/7.0/modifiers/)
and at the same time a complete set of class and tools [to specifically interact](/components/7.0/api/)
with each component of a RFC3986 URI.

System Requirements
-------

You need **PHP >= 8.1.0** but the latest stable version of PHP is recommended

Handling of an IDN host requires the presence of the `intl`
extension or a polyfill for the `intl` IDN functions like the
`symfony/polyfill-intl-idn` otherwise an exception will be thrown
when attempting to validate or interact with such a host.

IPv4 conversion requires at least one of the following:

- the `GMP` extension,
- the `BCMatch` extension or
- a `64-bits` PHP version

otherwise an exception will be thrown when attempting to convert a host
as an IPv4 address.

In order to create Data URI from the content of a file you are required to also
install the `fileinfo` extension otherwise an exception will be thrown.

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
