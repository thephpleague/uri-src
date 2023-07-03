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

The following classes are defined (order alphabetically):

- [Http](/uri/7.0/psr7/) : represents a PSR-7 `UriInterface` URI compliant object
- [URI](/uri/7.0/rfc3986/) : represents a generic RFC3986 compliant URI object

The following helper classes are added (order alphabetically) to complement the offer:

- the [BaseUri](/uri/7.0/base-uri) : a context aware wrapper around URI;
- the [UriString](/uri/7.0/parser-builder) : parses or builds URI into or from its components;
- the [UriTemplate](/uri/7.0/uri-template) : expands URI templates;

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
