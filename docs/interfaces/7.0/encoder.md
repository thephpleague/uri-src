---
layout: default
title: URI component encoder and decoder
---

URI component encoder and decoder
=======

In order to safely encode and/or decode a URI component, we need a tool to correctly perform the conversion.
To do so the package provides an enhanced OOP wrapper around PHP's `rawurlencode` and `rawurldecode` functions
using the `League\Uri\Encoder` helper class.

The class provides encoding mechanism for the following URI components:

```php
<?php

use League\Uri\Encoder;

$component = '/thi:s/is/a?simple=path';
$query = 'simple#=path&ké?=23';

echo Encoder::encodeUser($component);        // returns "%2Fthi%3As%2Fis%2Fa%3Fsimple=path"
echo Encoder::encodePassword($component);    // returns "%2Fthi:s%2Fis%2Fa%3Fsimple=path"
echo Encoder::encodePath($component);        // returns "/thi:s/is/a%3Fsimple=path"
echo Encoder::encodeQueryOrFragment($query); // returns "simple%23=path&k%C3%A9?=23"
````

The class also provides a more specific encodage used for query string key/value pair.

```php
<?php
$queryKey = 'ké#';
$queryValue = '&foobar';

echo Encoder::encodeQueryKeyValue($queryKey);   // returns "k%C3%A9%23"
echo Encoder::encodeQueryKeyValue($queryValue); // returns "%26foobar"
````

Each static encoding method is component aware and will prevent encoding component special characters that must or 
must not be encoded in the context of a full URI.

To complete the encoding methods, the class also exposes static decoding methods to safely decode URI components:

```php
<?php

use League\Uri\Encoder;

$component = '%2Fthi%3As%2Fis%2Fa%3Fsimple%23=%20path';

echo Encoder::decodeAll($component);                  // returns "/thi:s/is/a?simple#= path"
echo Encoder::decodeNecessary($component);            // returns "%2Fthi:s%2Fis%2Fa?simple#= path"
echo Encoder::decodeUnreservedCharacters($component); // returns "/thi:s/is/a?simple#=%20path"
echo Encoder::decodePath($component);                 // returns "%2Fthi:s%2Fis%2Fa%3Fsimple%23=%20path"
echo Encoder::decodeQuery($component);                // returns "/thi:s/is/a?simple%23=%20path"
echo Encoder::decodeFragment($component);             // returns "/thi:s/is/a?simple#=%20path"
````
Each static decoding method is component aware and will prevent decoding component specific characters in the context
of a full URI.

<p class="message-info">The <code>scheme</code> and <code>port</code> do not requires a specific class to be correctly
encoded. While host can be urlencoded and urldecoded, modern <code>host</code> encoding/decoding mechanism
relies on a much more strict and documented process like the one use for instance wit the
<code>League\Uri\Idna\Converter</code> class.</p>

<p class="message-warning">The <code>Encoder::decodeAll</code> and <code>decodeEncoder::Partial</code> may produce 
component representation that are not valid (containing white spaces or representing the <code>null</code> value)
in the context of a full URI creation.</p>
