---
layout: default
title: Uri Objects
redirect_from:
    - /uri/
---

Overview
=======

[![Author](https://img.shields.io/badge/author-@nyamsprod-blue.svg?style=flat-square)](https://twitter.com/nyamsprod)
[![Source Code](https://img.shields.io/badge/source-league/uri-blue.svg?style=flat-square)](https://github.com/thephpleague/uri)
[![Latest Stable Version](https://img.shields.io/github/release/thephpleague/uri.svg?style=flat-square)](https://packagist.org/packages/league/uri)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)<br>
[![Total Downloads](https://img.shields.io/packagist/dt/league/uri.svg?style=flat-square)](https://packagist.org/packages/league/uri)

`league\uri` offers a clear and consistent API for building, parsing, and modifying URIs.
It includes classes for [URI](/uri/7.0/rfc3986/), [URN](/uri/7.0/urn/) and [UriTemplate](/uri/7.0/uri-template) handling:

- The **Uri class** is fully RFC 3986–compliant with RFC 3987 IDN support and scheme validation.
- The **Urn class** implements RFC 8141 for managing Uniform Resource Names.
- The **UriTemplate class** implements RFC 6570 for expanding and resolving URI templates.

For interoperability, the package also provides [PSR-7 and PSR-17](/uri/7.0/psr-compliance/) compliant implementations
around URI access and creation. 

System Requirements
-------

You need **PHP >= 8.1** but the latest stable version of PHP is recommended.

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

To convert a URI into an HTML anchor tag you need to have the `ext-dom` extension
installed in your system.

Installation
--------

~~~
$ composer require league/uri:^7.0
~~~

Dependencies
-------

- [League Uri Interfaces](https://github.com/thephpleague/uri-interfaces)
- [PSR-7](http://www.php-fig.org/psr/psr-7/)
