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
echo $normalizer->normalizeHost('0')->toString(); // returns 0.0.0.0
```

Usage
--------

The class can convert the host if it is contains in:

- a URI using `IPv4Normalizer::normalizeUri`;
- an Authority `IPv4Normalizer::normalizeAuthority`;
- a Host `IPv4Normalizer::normalizeHost`;

```php
<?php

use League\Uri\Contracts\AuthorityInterface;
use League\Uri\Contracts\HostInterface;
use League\Uri\Contracts\UriInterface;
use League\Uri\IPv4Normalizer;
use League\Uri\IPv4Calculators\IPv4Calculator;
use \Psr\Http\Message\UriInterface as Psr7UriInterface;

public function IPv4Normalizer::normalizeUri(Stringable|string $uri): UriInterface|Psr7UriInterface ;
public function IPv4Normalizer::normalizeAuthority(Stringable|string $authority): AuthorityInterface;
public function IPv4Normalizer::normalizeHost(Stringable|string $host): HostInterface;
```

The methods only parameters are string or stringable objects that contain or represent a host component.

Behind the scene a `League\Uri\IPv4Calculators\IPv4Calculator` implementation is responsible for making
all the calculation needed to perform the conversion between IPv4 string representation.

The package comes bundled with three implementations:

- `League\Uri\IPv4Calculators\GMPCalculator` which relies on GMP extension;
- `League\Uri\IPv4Calculators\BCMathCalculator` which relies on BCMath extension;
- `League\Uri\IPv4Calculators\NativeCalculator` which relies on PHP build against a x.64 architecture;

The `IPv4Normalizer::fromEnvironment()` will try to load on of these implementation depending on your 
application environment. If it can not, a `League\Uri\Exceptions\Ipv4CalculatorMissing` exception
will be thrown.

The methods always return an instance of the same type as the submitted one with the host changed if the normalization is applicable or unchanged otherwise.

```php
<?php

use League\Uri\Components\Authority;
use League\Uri\IPv4Calculators\NativeCalculator;
use League\Uri\IPv4Normalizer;

$authority = Authority::new('hello:world@0300.0250.0000.0001:442');
$normalizer = new IPv4Normalizer(new NativeCalculator());
$normalizedAuthority = $normalizer->normalizeAuthority($authority);

echo $authority->getHost(); // returns '0300.0250.0000.0001'
echo $normalizedAuthority->getHost(); // returns '192.168.0.1'
```
