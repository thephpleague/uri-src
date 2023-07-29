---
layout: default
title: IDN - Domain Internationalization
---

In order to safely translate a domain name into it's unicode representation, we need a tool
to correctly reports the convertion results. To do so the package provides a OOP wrapper
around PHP's `idn_to_ascii` and `idn_to_unicode` functions using the class `League\Uri\Idna\Idna`

when performing a conversion a `League\Uri\Idna\IdnaInfo` class is returned with information
regarding the conversion.

```php
<?php

use League\Uri\Idna\Idna;
use League\Uri\Idna\IdnaError;
use League\Uri\Idna\IdnaInfo;
use League\Uri\Idna\IdnaOption;

/** @var IdnaInfo $info */
$info = Idna::toUnicode('www.xn--85x722f.xn--55qx5d.cn', IdnaOption::forIDNA2008Unicode());
$info->result();    // returns 'www.食狮.公司.cn'
$info->errors();    // returns IdnaERROR::NONE->value
$info->errorList(); // []

$info = Idna::toAscii('www.食狮.公司.cn', IdnaOption::forIDNA2008Ascii());
$info->result();    // returns 'www.xn--85x722f.xn--55qx5d.cn'
$info->errors();    // returns IdnaERROR::NONE->value
$info->errorList(); // []
```

In case of error no exception is thrown but the `errors` and the `errorList` methods get
populated.

```php
<?php

use League\Uri\Idna\Idna;
use League\Uri\Idna\IdnaOption;

$info = Idna::toAscii('aa'.str_repeat('A', 64).'.％００.com', IdnaOption::forIDNA2008Ascii());
$info->errors();    // IdnaError::LABEL_TOO_LONG->value | IdnaError::DISALLOWED->value
                    // representing a bitset of the error constants Idna::ERROR_*
$info->errorList(); // returns 
//  array {
//    IdnaError::LABEL_TOO_LONG,
//    IdnaError::DISALLOWED,
//  }
```
