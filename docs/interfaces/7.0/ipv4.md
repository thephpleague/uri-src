---
layout: default
title: IPv4 Converter
---

IPv4 Converter
=======

The `League\Uri\IPv4\Converter` is a IPv4 Host Converter.

```php
<?php

use League\Uri\IPv4\Converter;
use League\Uri\IPv4\NativeCalculator;

$host = '0300.0250.0000.0001';
$converter = new Converter(new NativeCalculator());
$convertedHost = $converter->toDecimal($host);

echo $convertedHost; // returns '192.168.0.1'
```

Usage
--------

The `Converter::toDecimal` method tries to convert a host into a valid IPv4 decimal dot-notation
representation based on the algorithm used by the [WHATWG rules](https://url.spec.whatwg.org/#concept-ipv4-parser).
The method only parameter should represent a host value.

To work as intended the class requires a `League\Uri\IPv4\Calculator` implementing class 
responsible for making all the calculations needed to perform the conversion between
IPv4 representations.

The package comes bundled with three implementations:

- `League\Uri\IPv4\GMPCalculator` which relies on GMP extension;
- `League\Uri\IPv4\BCMathCalculator` which relies on BCMath extension;
- `League\Uri\IPv4\NativeCalculator` which relies on PHP build against the x.64 architecture;

For ease of usage the class exposes a `fromEnvironment` named constructor which 
will pick the correct implementation based on the available extensions. 

If no calculator is provided a `League\Uri\Exceptions\MissingFeature` exception will be thrown. likewise,
if no convertion is possible `null` is returned.

```php
<?php

use League\Uri\IPV4\Converter;

$converter = Converter::fromEnvironment();
$converter->toDecimal('0');       // returns 0.0.0.0
$converter->toDecimal('toto.be'); // returns null
```

The same functionality is provided to convert the IPv4 to Octal and Hexadecimal representation.

- `Converter::toOctal` tries to convert the IPv4 to its octal dot notation or returns `null`
- `Converter::toHexadecimal` tries to convert the IPv4 to its hexadecimal dot notation or returns `null`

```php
<?php

use League\Uri\IPV4\Converter;

$converter = Converter::fromEnvironment();
$converter->toDecimal('0xc0a821');         // returns "192.168.2.1"
$converter->toOctal('0xc0a821');           // returns "0300.0250.0002.0001"
$converter->toHexadecimal('192.168.2.1.'); // returns "0xc0a821"
```
