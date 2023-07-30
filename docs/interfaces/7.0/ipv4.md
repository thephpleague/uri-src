---
layout: default
title: IPv4 Converter
---

IPv4 Converter
=======

The `League\Uri\IPv4Converter` is a IPv4 Host Converter.

```php
<?php

use League\Uri\IPv4\IPv4Converter;
use League\Uri\IPv4\NativeCalculator;

$host = '300.0250.0000.0001:442';
$normalizer = new IPv4Converter(new NativeCalculator());
$normalizedHost = $normalizer($authority);

echo $normalizedHost; // returns '192.168.0.1'
```

Usage
--------

The `IPv4Normalizer::normalize` method tries to normalize a host based on
the normalization algorithm used by the <a href="https://url.spec.whatwg.org/#concept-ipv4-parser">WHATWG rules</a>
to parse and format IPv4 multiple string representations into a valid IPv4 decimal
representation. The method only parameter should represent a host component value.

To work as intended the class requires a `League\Uri\IPv4\IPv4Calculator` implementing class 
responsible for making all the calculation needed to perform the conversion between
IPv4 string representations.

The package comes bundled with three implementations:

- `League\Uri\IPv4\GMPCalculator` which relies on GMP extension;
- `League\Uri\IPv4\BCMathCalculator` which relies on BCMath extension;
- `League\Uri\IPv4\NativeCalculator` which relies on PHP build against the x.64 architecture;

For ease of usage the class exposes a `IPv4Normalizer::fromEnvironment` named constructor which 
will pick the correct implementation based on the available extensions. If no calculator
is provided a `League\Uri\Exceptions\Ipv4CalculatorMissing` exception will be thrown.

If no normalization is possible `null` is returned.

```php
<?php

use League\Uri\IPV4\IPv4Converter;

$normalizer = IPv4Converter::fromEnvironment();
$normalizer('0');       // returns 0.0.0.0
$normalizer('toto.be'); // returns null
```
