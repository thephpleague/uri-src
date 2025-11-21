---
layout: default
title: Polyfill for PHP Native URI extension
description: This package provides a polyfill for the new native PHP URI extension for PHP8.1+
redirect_from:
    - /polyfill/
---

# URI polyfill for PHP8.1+

[![Author](https://img.shields.io/badge/author-@nyamsprod-blue.svg?style=flat-square)](https://twitter.com/nyamsprod)
[![Latest Stable Version](https://img.shields.io/github/release/thephpleague/uri-polyfill.svg?style=flat-square)](https://packagist.org/packages/league/uri-polyfill)
[![Total Downloads](https://img.shields.io/packagist/dt/league/uri-polyfill.svg?style=flat-square)](https://packagist.org/packages/league/uri-polyfill)

This package provides a polyfill for the new native PHP URI
parsing features to be included in **PHP8.5**. The polyfill
works for PHP versions greater or equal to **PHP8.1**

## System Requirements

To use the package, you require:

- **PHP >= 8.1** but the latest stable version of PHP is recommended
- [league/uri-interfaces](https://github.com/thephpleague/uri-interfaces)
- [rowbot/url](https://github.com/TRowbotham/URL-Parser)


<p class="message-notice">If you are using  <code>PHP 8.1</code>, 
you <strong>SHOULD</strong> install <code>symfony/polyfill-php82</code> to use 
its <code>SensitiveParameter</code> polyfill</p>

## Install

Install the package using Composer.

```bash
composer require league/uri-polyfill:^7.6
```

## Documentation

**PHP 8.5** introduces the following classes:

- the `Uri\Rfc3986\Uri` class, an [RFC 3986](https://www.rfc-editor.org/rfc/rfc3986) compliant URI parser
- the `Uri\WhatWg\Url` class, an [WHATWG](https://url.spec.whatwg.org/) compliant URL parser

These classes are accompanied by Exception and Enum classes that provide robust URI parsing and validation according to two widely used URI specifications.

This package backports these features to earlier PHP versions, starting from **PHP 8.1**.

Full documentation is available in the [Add RFC 3986 and WHATWG compliant URI parsing support RFC](https://wiki.php.net/rfc/url_parsing_api),
as well as on the PHP documentation website.

Once installed, you will be able to access the new PHP Native URI extension on
older versions of PHP and do the following:

````php
$uri = new Uri\Rfc3986\Uri("HTTPS://ex%61mpLE.com:443/foo/../bar/./baz?#fragment");
$uri->toRawString(); // returns "HTTPS://ex%61mpLE.com:443/foo/../bar/./baz?#fragment"
$uri->toString();    // returns "https://example.com:443/bar/baz?#fragment"

$url = new Uri\WhatWg\Url("HTTPS://🐘.com:443/foo/../bar/./baz?#fragment");
echo $url->toAsciiString();   // returns "https://xn--go8h.com/bar/baz?#fragment"
echo $url->toUnicodeString(); // returns "https://🐘.com/bar/baz?#fragment"
````
