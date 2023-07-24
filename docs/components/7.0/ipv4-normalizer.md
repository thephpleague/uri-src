---
layout: default
title: IPv4 Normalizer
---

IPv4 Normalizer
=======

The `League\Uri\IPv4Normalizer` is a PHP IPv4 Host Normalizer. It normalizes the
host representation and convert it to an IPv4 decimal representation when possible.
This is done using the [WHATWG rules](https://url.spec.whatwg.org/#concept-ipv4-parser)
to parse and format IPv4 multiple string representations.

```php
<?php

use League\Uri\IPv4Normalizer;

$normalizer = IPv4Normalizer::fromEnvironment();
echo $normalizer->normalize('0'); // returns 0.0.0.0
```

Usage
--------

The class convert the host if the conversion is applicable using the `IPv4Normalizer::normalize` method or
the `IPv4Normalizer::normalizeHost` methods. The methods differs in their return type the former
returns `null` if the conversion fails or the IP string representation if it succeeds, while
the latter always returns a `Host` object.

Behind the scene a `League\Uri\IPv4Calculators\IPv4Calculator` implementation is responsible for making
all the calculations needed to perform the conversion between IPv4 string representation.

The package comes bundled with three implementations:

- `League\Uri\IPv4Calculators\GMPCalculator` which relies on GMP extension;
- `League\Uri\IPv4Calculators\BCMathCalculator` which relies on BCMath extension;
- `League\Uri\IPv4Calculators\NativeCalculator` which relies on PHP build against a x.64 architecture;

The `IPv4Normalizer::fromEnvironment()` will try to load one of these implementations depending on your 
application environment. If it can not, a `League\Uri\Exceptions\Ipv4CalculatorMissing` exception
will be thrown.

```php
<?php

use League\Uri\IPv4Calculators\NativeCalculator;
use League\Uri\IPv4Normalizer;

$host = Host::new('0300.0250.0000.0001');
$normalizer = new IPv4Normalizer(new NativeCalculator());
$ipHost = $normalizer->normalizeHost($host);
$ipHost::class; // returns \League\Uri\Components\Host

echo $host->value();   // returns '0300.0250.0000.0001'
echo $ipHost->value(); // returns '192.168.0.1'

echo $normalizer->normalize('0300.0250.0000.0001'); // returns '192.168.0.1'
```
