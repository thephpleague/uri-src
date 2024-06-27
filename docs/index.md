---
layout: index
title: The URI toolkit For PHP Developers
---

The URI toolkit For PHP Developers
---

Suite of packages which provide intuitive features to parse, validate, format and manipulate URIs and 
their components. Built to enable working with any kind of [RFC3986](https://tools.ietf.org/html/rfc3986)
compliant URI and follow closely the latest [WHATWG URL Living Standard](https://url.spec.whatwg.org/)
specification. It provides an enhanced replacement for PHP's `parse_url`, `http_build_query`, PECL's
`http_build_url` functions, as well as [PSR-7](https://www.php-fig.org/psr/psr-7/)
and [PSR-17](https://www.php-fig.org/psr/psr-17/) adapters.

```php
use League\Uri\Components\Query;
use League\Uri\Modifier;
use League\Uri\Uri;

$uri = Uri::new('https://example.com?q=value#fragment');
$uri->getScheme(); // returns 'http'
$uri->getHost();   // returns 'example.com'

$newUri = Modifier::from($uri)->appendQuery('q=new.Value');
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
- Encode/decode URI components

### [URI](/uri/7.0/)

The URI manipulation package

- URI object with complete validation
- Resolves and Relativizes URIs
- Expands URI Templates
- PSR-7 and PSR-17 URI adapters

### [URI-COMPONENTS](/uri-components/7.0/)

The URI components package

- Provides URI components objects
- URLSearchParams for PHP
- Partial modifiers for URI.

**Once a new major version is released, the previous stable release remains supported
for six more months with patches and security fixes.**
