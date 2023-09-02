---
layout: default
title: URL Search Params
---

URLSearchParams
=======

## Contracts

The `URLSearchParams` class implements utility methods to work with the query string of a URL
as defined by the [WHATWG group](https://url.spec.whatwg.org/#urlsearchparams).

This means that you can use this class anytime you need a compliant `URLSearchParams` object.
To improve its usage the class also exposes  the [package common API](/components/7.0/).

To get the full information on the class behaviour and feature you can go to the 
[MDN page](https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams). But since
we are in the PHP context the class differs in the following aspects:

| WHATWG group Specification       | PHP implementation                |
|----------------------------------|-----------------------------------|
| `URLSearchParams::entries`       | `URLSearchParams::getIterator`    |
| `URLSearchParams::size` property | `URLSearchParams::count`          |
| `URLSearchParams::forEach`       | `URLSearchParams::each`           |
| *N/A*                            | `URLSearchParams::isEmpty`        |
| *N/A*                            | `URLSearchParams::isNotEmpty`     |
| *N/A*                            | `URLSearchParams::fromParameters` |

<p class="message-notice">As per the specification the class is mutable.</p>
<p class="message-notice">As per the specification encoding is done follwoing the <code>application/x-www-form-urlencoded</code></p> 

The `URLSearchParams::fromParameters` named constructor allow instantiating the object
from PHP query parameters.

## Usage

~~~php
use League\Uri\Components\URLSearchParams;

$params = new URLSearchParams('foo=bar&bar=baz+bar&foo=baz');
$params->isNotEmpty(); //return true
$params->get('foo'); //returns 'bar'
$params->getAll('foo'); //returns ['bar', 'baz']
$params->has('foo'); //returns true
$params->has('foo', 'bar'); //returns true
$params->has('foo', 'toto'); //returns false (the second parameters is the value of the pair)
count($params); //returns 3
$params->delete('foo');
count($params); //returns 1 (because all the pairs which contains foo have been removed)
$params->set('aha', true);
$params->append('aha', null);
$params->get('foo'); //returns null
$params->get('bar'); //returns "baz bar"
$params->sort();

echo $params->toString(); //returns "aha=true&aha=null&bar=baz+bar"
~~~
