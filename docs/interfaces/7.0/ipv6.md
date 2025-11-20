---
layout: default
title: IPv6 Converter
description: Helpers to handle and manipulate IPv6 host in the context of URIs.
---

IPv6 Converter
=======

The `League\Uri\IPv6\Converter` is a IPv6 Converter.

```php
<?php

use League\Uri\IPv6\Converter;

echo Converter::expand('[::1]');
// returns '[0000:0000:0000:0000:0000:0000:0000:0001]'
echo Converter::compress('[1050:0000:0000:0000:0005:0000:300c:326b]');
// returns [1050::5:0:300c:326b]
```

The `Converter::compress` static method converts an expanded IPv6 host into its compressed form.  
The method-only parameter should represent a host value. The `Converter::expand` method
does the opposite.

If you submit a host which is not an IPv6 one then, the submitted host value will be returned
as is. Conversely, trying to expand an IPv6 host which is already expanded or trying to compress
an already compressed IPv6 host will return the same value so nothing will be gain performing
such obvious operation.

```php
echo Converter::compress('[::1]'); 
// returns '[::1]'

echo Converter::expand('[1050:0000:0000:0000:0005:0000:300c:326b]');
// returns [1050:0000:0000:0000:0005:0000:300c:326b]
```

To complement the host related methods, the class also provide stricter IPv6 compress and expand
methods using the  `Converter::compressIp` and  `Converter::expandId` methods. Those methods will
throw if the submitted value is not a valid IPv6 representation.

```php
echo Converter::compressIp('192.28.3.1');
//will throw a ValueError 

echo Converter::expandIp('1050::5:0:300c:326b');
// returns 1050:0000:0000:0000:0005:0000:300c:326b
```
