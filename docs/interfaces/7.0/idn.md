---
layout: default
title: IDN - Domain Internationalization
description: Helpers to safely encode and/or decode internationalized domain names.
---

IDN Conversion
===========

In order to safely translate a domain name into it's unicode representation and vice versa,
we need a tool to correctly report the conversion results. To do so the package provides an
enhanced OOP wrapper around PHP's `idn_to_ascii` and `idn_to_utf8` functions using the
`League\Uri\Idna\Converter` class.

With vanilla PHP you have to do the following:

```php
<?php

$flags = IDNA_NONTRANSITIONAL_TO_ASCII |
    IDNA_CHECK_BIDI |
    IDNA_USE_STD3_RULES |
    IDNA_CHECK_CONTEXTJ;
    
$res = idn_to_utf8('www.xn--85x722f.xn--55qx5d.cn', $flags, INTL_IDNA_VARIANT_UTS46, $result);

$res;               // returns 'www.食狮.公司.cn'
$result['result'];  // returns 'www.食狮.公司.cn'
$result['errors'];  // returns 0
$result['isTransitionalDifferent'];  // returns false
```

which means remembering:

- the flags value,
- the parameters position, 
- the return value can be the converted domain or `false` in case of error
- that the result is filled by reference so if not provided you won't know the reason for failure or success.
- the `errors` keys represents a bitset of the error constants `IDNA_ERROR_*`
- IDNA options constants and IDNA error constants can be mistakenly used as they are both `int` with similar values

In contrast, when performing a conversion with the `League\Uri\Idna\Converter` a `League\Uri\Idna\Result`
instance is returned with information regarding the outcome of the conversion.

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

<p class="message-warning">In case of error the return value of <code>Result::domain</code> <code>may</code>
not be the same as the submitted value and may highlight the host part that triggered the error as per
<a href="https://www.unicode.org/reports/tr46/#Processing">the specifications</a>.</p>

The `League\Uri\Idna\Error` enum provides the official name of the error as well as its description via
the `Error::description` method.

Both static methods `Converter::toAscii` and `Converter::toUnicode` expect a host string
and some IDN related options. 

<p class="message-info">since version <code>7.2.0</code> The <code>Converter</code> methods also accept <code>Stringable</code>
object as domain input.</p>

You can provide PHP's own constants or if you want a more readable API you can use the
`League\Uri\Idna\Option` immutable object.

```php
<?php

use League\Uri\Idna\Converter;
use League\Uri\Idna\Option;

$flags = IDNA_NONTRANSITIONAL_TO_ASCII |
    IDNA_CHECK_BIDI |
    IDNA_USE_STD3_RULES |
    IDNA_CHECK_CONTEXTJ;
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
method for error you can use the following related methods:

- `Converter::toAsciiOrFail` instead of `Converter::toAscii`;
- `Converter::toUnicodeOrFail` instead of `Converter::toUnicode`; 

Both methods will directly return the converted domain string or throw a `League\Uri\Exceptions\ConversionFailed` exception
on error. You can still access:

- the result by calling the `ConversionFailed::getResult` method. 
- the original submitted host string using the `ConversionFailed::getHost` method.

The exception message will contain a concatenation of all the error descriptions available for the conversion.

```php
<?php

use League\Uri\Idna\Converter;
use League\Uri\Exceptions\ConversionFailed;

try {
    $domain = Converter::toAsciiOrFail('％００.com');
} catch (ConversionFailed $exception) {
    $result = $exception->getResult(); // returns the `League\Uri\Idna\Result` object
    echo $exception->getHost();        // display "％００.com"
    echo $result->domain();            // display "�00.com"
    echo $exception->getMessage(); 
    //displays "Host `％００.com` is invalid: a label or domain name contains disallowed characters."
}
````

<p class="message-notice">since version <code>7.2.0</code></p>

It is possible to determine if a given domain is or can be internationalized using the new `Converter::isIDN`
method. The method will return `true` if the submitted domain is valid and is/can be converted to its unicode form.

```php
<?php

use League\Uri\Idna\Converter;

Converter::isIdn('www.example.con'); // return false - the unicode form is identical
Converter::isIdn('％００.com'); // return false - The host is invalid
Converter::isIdn('bébé.be');   // return true
Converter::isIdn('www.xn--85x722f.xn--55qx5d.cn');  // return true - a IDN host in its ascii form
````
