---
layout: default
title: IDN - Domain Internationalization
---

IDN Conversion
===========

In order to safely translate a domain name into it's unicode representation and vice versa,
we need a tool to correctly report the convertion results. To do so the package provides an
enhanced OOP wrapper around PHP's `idn_to_ascii` and `idn_to_unicode` functions using the
`League\Uri\Idna\Converter` class.

When performing a conversion a `League\Uri\Idna\Result` class is returned with information
regarding the outcome of the conversion.

With vanilla PHP you would to the following:

```php
<?php

$flags = IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_CHECK_BIDI | IDNA_USE_STD3_RULES | IDNA_CHECK_CONTEXTJ;
$res = idn_to_utf8('www.xn--85x722f.xn--55qx5d.cn', $flags, INTL_IDNA_VARIANT_UTS46, $result);

$res;               // returns 'www.食狮.公司.cn'
$result['result'];  // returns 'www.食狮.公司.cn'
$result['errors'];  // returns 0
$result['isTransitionalDifferent'];  // returns false
```

In contrast, when using the `Converter` class the code becomes:

```php
<?php

use League\Uri\Idna\Converter;

/** @var League\Uri\Idna\Result $result */
$result = Converter::toUnicode('www.xn--85x722f.xn--55qx5d.cn');
$result->domain();                  // returns 'www.食狮.公司.cn'
$result->isTransitionalDifferent(); // return false
$result->hasErrors();               // returns false
 
$result = Converter::toAscii('www.食狮.公司.cn');
$result->domain();                  // returns 'www.xn--85x722f.xn--55qx5d.cn'
$result->isTransitionalDifferent(); // return false
$result->hasErrors();               // returns false
```

In case of errors the `Result::hasErrors` method returns `true` and you can inspect the found errors
using the `Result::errors` method which returns a list of `Error` enum.

```php
<?php

use League\Uri\Idna\Converter;
use League\Uri\Idna\Error;

$result = Converter::toAscii('aa'.str_repeat('A', 64).'.％００.com');
$result->hasErrors(); //return true
$result->hasError(Error::LABEL_TOO_LONG); // returns true
foreach ($result->errors() as $error) {
    echo $error->name, ': ', $error->description(), PHP_EOL;
}
//displays
//LABEL_TOO_LONG: a domain name label is longer than 63 bytes
//DISALLOWED: a label or domain name contains disallowed characters
```

The `Error` enum provides the official name of the error as well as its description via
the `Error::description` method.

Both static methods `Converter::toAscii` and `Converter::toUnicode` expect a host string
and some IDN related options. You can provide PHP's own constants or if you want a more
readable API you can use the `League\Uri\Idna\Option` immutable object or use a
combination of both APIs.

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

echo idn_to_ascii('bébé.be', $option);
echo idn_to_ascii('bébé.be', $option1->toBytes());
echo idn_to_ascii('bébé.be', $option2->toBytes());
echo idn_to_ascii('bébé.be', $option3->toBytes());

echo Converter::toAscii('bébé.be')->domain();
echo Converter::toAscii('bébé.be', $option)->domain();
echo Converter::toAscii('bébé.be', $option1)->domain();
echo Converter::toAscii('bébé.be', $option2)->domain();
echo Converter::toAscii('bébé.be', $option3)->domain();

//all the above calls will produce the same result 'xn--bb-bjab.be'
 ```

If you provide a `Option` instance, the `Option::toBytes` method will be called inside the conversion
method when appropriate.

In contrary to PHP functions, if no option is provided both methods will use the correct basic options to validate
domain names:

- for `Converter::toAscii` the default is `Option::forIDNA2008Ascii()`;
- for `Converter::toUnicode` the default is `Option::forIDNA2008Unicode()`;

Last but not least if you prefer methods that throw exceptions instead of having to check the `Result::hasErrors`
method for error you can use the following sibling methods:

- `Converter::toAsciiOrFail` which throws a `League\Uri\Idna\ConversionFailed` exception on error
- `Converter::toUnicodeOrFail` which throws a `League\Uri\Idna\ConversionFailed` exception on error

You can still access the result by calling the `ConversionFailed::result` method.

```php
<?php

use League\Uri\Idna\Converter;
use League\Uri\Idna\ConversionFailed;
use League\Uri\Idna\Error;

try {
    $result = Converter::toAsciiOrFail('％００.com');
} catch (ConversionFailed $exception) {
    $result = $exception->result(); // the `League\Uri\Idna\Result` object
    echo $exception->getMessage(); 
    //displays "The host `％００.com` could not be converted: a label or domain name contains disallowed characters."
}
````
