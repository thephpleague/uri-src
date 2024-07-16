---
layout: default
title: URI common interfaces and tools
redirect_from:
- /interfaces/
---

Uri Common tools
=======

This package contains:

- interface to represent [URI and components objects](/interfaces/7.0/contracts/)
- parsers to parse and build [URI](/interfaces/7.0/uri-parser-builder/) and [Query](/interfaces/7.0/query-parser-builder/) strings that provide enhanced replacement for PHP's `parse_url` and PECL's `http_build_url` functions via its [UriString](/uri/7.0/parser-builder) class.
- tools to help in processing URIs ([IPv4 Converter](/interfaces/7.0/ipv4/), [IPv6 Converter](/interfaces/7.0/ipv6/)  and [IDN converter](/interfaces/7.0/idn/)) and their components in various ways.

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

Install
--------

```
$ composer require league/uri-interfaces:^7.0
```
