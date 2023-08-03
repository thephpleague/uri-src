---
layout: default
title: IDN - Domain Internationalization
---

IDN Conversion
===========

In order to safely translate a domain name into it's unicode representation, we need a tool
to correctly reports the convertion results. To do so the package provides a OOP wrapper
around PHP's `idn_to_ascii` and `idn_to_unicode` functions using the class `League\Uri\Idna\Idna`

when performing a conversion a `League\Uri\Idna\Result` class is returned with information
regarding the conversion.

With vanilla PHP you would to the following:

```php
<?php

$flags = IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_CHECK_BIDI | IDNA_USE_STD3_RULES | IDNA_CHECK_CONTEXTJ;
$res = idn_to_utf8('www.xn--85x722f.xn--55qx5d.cn', $flags, INTL_IDNA_VARIANT_UTS46, $result);

$res;             // returns 'www.食狮.公司.cn'
$result['result'];  // returns 'www.食狮.公司.cn'
$result['errors'];  // returns 0
$result['isTransitionalDifferent'];  // returns false
```

In contrast, when using the `Idna` class the code becomes:

```php
<?php

use League\Uri\Idna\Converter;

/** @var League\Uri\Idna\Result $result */
$result = Converter::toUnicode('www.xn--85x722f.xn--55qx5d.cn');
$result->domain();                  // returns 'www.食狮.公司.cn'
$result->isTransitionalDifferent(); // return false
$result->hasErrors();               // returns false
 
$result = Converter::toAscii('www.食狮.公司.cn';
$result->domain();         // returns 'www.xn--85x722f.xn--55qx5d.cn'
$result->isTransitionalDifferent(); // return false
$result->hasErrors();      // returns false
```

In case of error the `IdnaInfo::hasErrors` method returns `true` and you can inspect the reasons
using the `errors` method which returns a list of `IdnaError` enum objects.

```php
<?php

use League\Uri\Idna\Converter;
use League\Uri\Idna\Error;

$result = Converter::toAscii('aa'.str_repeat('A', 64).'.％００.com');
$result->hasErrors(); //return true
$result->hasError(Error::LABEL_TOO_LONG); // returns true
$result->errors(); // returns 
//  array {
//    Error::LABEL_TOO_LONG,
//    Error::DISALLOWED,
//  }

$error = $info->errors()[0];
$error->value;         // returns the value of IDNA_ERROR_LABEL_TOO_LONG; the enum C value (MAY change and should not be relied upon)
$error->name;          // returns 'LABEL_TOO_LONG'
$error->description(); // returns 'a domain name label is longer than 63 bytes'
```

The enum `Error` provide the official name of the error as well as its description via
the `Error::description` method.

Both static methods `Converter::toAscii` and `Converter::toUnicode` expect a host string and some IDN related options.
You can provide PHP's own constants or if you want a more readable API you can use 
the `League\Uri\Idna\Option` immutable object.

```php
<?php

use League\Uri\Idna\Converter;
use League\Uri\Idna\Option;

$option = IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_CHECK_BIDI | IDNA_USE_STD3_RULES | IDNA_CHECK_CONTEXTJ;

//can be rewritten as

$option1 = Option::new(IDNA_NONTRANSITIONAL_TO_ASCII)
    ->add(IDNA_CHECK_BIDI)
    ->add(IDNA_USE_STD3_RULES)
    ->add(IDNA_CHECK_CONTEXTJ);

//can be rewritten as

$option2 = Option::new()
    ->nonTransitionalToAscii()
    ->checkBidi()
    ->useSTD3Rules()
    ->checkContextJ();
            
//can be rewritten as

$option3 = Option::forIDNA2008Ascii();

echo Converter::toAscii('bébé.be')->domain();
echo Converter::toAscii('bébé.be', $option)->domain();
echo Converter::toAscii('bébé.be', $option1)->domain();
echo Converter::toAscii('bébé.be', $option2)->domain();
echo Converter::toAscii('bébé.be', $option3)->domain();
echo idn_to_ascii('bébé.be', $option);
echo idn_to_ascii('bébé.be', $option1->toBytes());
echo idn_to_ascii('bébé.be', $option2->toBytes());
echo idn_to_ascii('bébé.be', $option3->toBytes());

//all the above calls will produce the same result 'xn--bb-bjab.be'
 ```

If you provide a `Option` instance, the `Option::toBytes` method will be called inside the conversion
method when appropriate.

In contrary to PHP functions, if no option is provided both methods will use the correct basic options to validate
domain names:

- for `Converter::toAscii` the default will be `Option::forIDNA2008Ascii()`;
- for `Converter::toUnicode` the default will be `Option::forIDNA2008Unicode()`;
