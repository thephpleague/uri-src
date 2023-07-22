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
[![Build](https://github.com/thephpleague/uri/workflows/build/badge.svg)](https://github.com/thephpleague/uri/actions?query=workflow%3A%22build%22)
[![Total Downloads](https://img.shields.io/packagist/dt/league/uri.svg?style=flat-square)](https://packagist.org/packages/league/uri)

This package contains features and capabilities to ease manipulating URIs.

It provides an enhanced replacement for PHP's `parse_url` and PECL's `http_build_url`
functions via its [UriString](/uri/7.0/parser-builder) class.

It allows simple URI manipulation via the context aware wrapper [BaseUri](/uri/7.0/base-uri).
But also expose a complete API around URI creation using the [UriTemplate](/uri/7.0/uri-template)
and the [URI](/uri/7.0/rfc3986/) class which represents a generic RFC3986 compliant URI
object.

For interoperability, we also provide [PSR-7 and PSR-17](/uri/7.0/psr-compliance/)
compliant implementation around URI access and creation. 

System Requirements
-------

You need **PHP >= 8.1** but the latest stable version of PHP is recommended.

In order to handle IDN host you are required to also install the `intl` extension otherwise an exception will be thrown when attempting to validate such host.

In order to create Data URI from a filepath you are required to also install the `fileinfo` extension otherwise an exception will be thrown.

Installation
--------

~~~
$ composer require league/uri:^7.0
~~~

Dependencies
-------

- [League Uri Interfaces](https://github.com/thephpleague/uri-interfaces)
- [PSR-7](http://www.php-fig.org/psr/psr-7/)
