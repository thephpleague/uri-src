---
layout: index
title: The URI toolkit For PHP Developers
---

The URI toolkit For PHP Developers
---

A complete PHP toolkit for working with URIs. Parse, validate, format, and manipulate 
any [RFC 3986](https://tools.ietf.org/html/rfc3986) or
r [RFC 8141](https://tools.ietf.org/html/rfc8141)–compliant identifier in a consistent,
standards-based way. Aligned with the [WHATWG URL Living Standard](https://url.spec.whatwg.org/). Includes polyfills, 
[PSR-7](https://www.php-fig.org/psr/psr-7/) and [PSR-17](https://www.php-fig.org/psr/psr-17/) adapters, and modern replacements for PHP’s legacy URL functions.

```php
use League\Uri\Components\Query;
use League\Uri\Modifier;
use League\Uri\Uri;

$uri = Uri::new('https://example.com?q=value#fragment');
$uri->getScheme(); // returns 'http'
$uri->getHost();   // returns 'example.com'

$newUri = Modifier::wrap($uri)->appendQuery('q=new.Value');
echo $newUri; // 'https://example.com?q=value&q=new.Value#fragment'

$query = Query::fromUri($newUri);
$query->get('q');    // returns 'value'
$query->getAll('q'); // returns ['value', 'new.Value']
$query->parameter('q'); // returns 'new.Value'
```

Choose the package that suits your needs
====

### [URI-INTERFACES](/interfaces/7.0/)

The URI utility package

- URI parser and builder
- Query parser and builder
- IDNA, IPv4 and IPv6 converter
- Encoder/decoder for URI components

### [URI](/uri/7.0/)

The URI manipulation package

- Full validation of common URI schemes
- URN support following **RFC 8141**
- URI Template expansion **RFC 6570**
- **PSR-7** and **PSR-17** URI adapters
- Resolves, Normalizes and Relativizes URIs

### [URI-COMPONENTS](/uri-components/7.0/)

The URI components package

- Provides URI components objects
- **URLSearchParams** for PHP
- **FragmentDirectives** for PHP
- Universal URI Modifier

### [URI-POLYFILL](/uri-polyfill/7.0/)

The URI Polyfill package

- **Uri\Rfc3986\Uri** for PHP8.1+
- **Uri\WhatWg\Url** for PHP8.1+

**Once a new major version is released, the previous stable release remains supported
for six more months with patches and security fixes.**
