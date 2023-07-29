---
layout: default
title: IDN - Domain Internationalization
---

IDN Conversion
===========

In order to safely translate a domain name into it's unicode representation, we need a tool
to correctly reports the convertion results. To do so the package provides a OOP wrapper
around PHP's `idn_to_ascii` and `idn_to_unicode` functions using the class `League\Uri\Idna\Idna`

when performing a conversion a `League\Uri\Idna\IdnaInfo` class is returned with information
regarding the conversion.

With vanilla PHP you would to the following:

```php
<?php

$flags = IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_CHECK_BIDI | IDNA_USE_STD3_RULES | IDNA_ERROR_CONTEXTJ;
$res = idn_to_utf8('www.xn--85x722f.xn--55qx5d.cn', $flags, INTL_IDNA_VARIANT_UTS46, $info);

$res;             // returns 'www.食狮.公司.cn'
$info['result'];  // returns 'www.食狮.公司.cn'
$info['errors'];  // returns 0
$info['isTransitionalDifferent'];  // returns false
```

In contrast, when using the `Idna` class the code becomes:

```php
<?php

use League\Uri\Idna\Idna;
use League\Uri\Idna\IdnaError;
use League\Uri\Idna\IdnaInfo;
use League\Uri\Idna\IdnaOption;

/** @var IdnaInfo $info */
$info = Idna::toUnicode('www.xn--85x722f.xn--55qx5d.cn', IdnaOption::forIDNA2008Unicode());
$info->result();                  // returns 'www.食狮.公司.cn'
$info->isTransitionalDifferent(); // return false
$info->hasErrors();               // returns false
 
$info = Idna::toAscii('www.食狮.公司.cn', IdnaOption::forIDNA2008Ascii());
$info->result();         // returns 'www.xn--85x722f.xn--55qx5d.cn'
$info->isTransitionalDifferent(); // return false
$info->hasErrors();      // returns false
```

In case of error the `IdnaInfo::hasErrors` method returns `true` and you can inspect the reasons
using the `errors` method which returns a list of `IdnaError` enum objects.

```php
<?php

use League\Uri\Idna\Idna;
use League\Uri\Idna\IdnaOption;

$info = Idna::toAscii('aa'.str_repeat('A', 64).'.％００.com', IdnaOption::forIDNA2008Ascii());
$info->hasErrors(); //return true
$info->errors(); // returns 
//  array {
//    IdnaError::LABEL_TOO_LONG,
//    IdnaError::DISALLOWED,
//  }
```

The enum `IdnaError` provide the official name of the error as well as its description via
the `IdnaError::description` method.

Both static methods `Idna::toAscii` and `Idna::toUnicode` expect a host string and some IDN related options.
You can provide PHP's own constants or if you want a more readable API you can use 
the `League\Uri\Idna\IdnaOption` immutable object.

```php
<?php

use League\Uri\Idna\Idna;
use League\Uri\Idna\IdnaOption;

$option = IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_CHECK_BIDI | IDNA_USE_STD3_RULES | IDNA_ERROR_CONTEXTJ;

// returns the same as 

$altOption1 = IdnaOption::new()
            ->nonTransitionalToAscii()
            ->checkBidi()
            ->useSTD3Rules()
            ->checkContextJ()
            ->toBytes();
            
 // and the same as 
 
 $altOption2 = IdnaOption::forIDNA2008Ascii()->toBytes();
 ```

If you provide a `IdnaOption` instance, the `toBytes` method will be called inside the conversion
method when appropriate.
