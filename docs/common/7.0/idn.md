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
use League\Uri\Idna\IdnaInfo;

/** @var IdnaInfo $info */
$info = Idna::toUnicode('www.xn--85x722f.xn--55qx5d.cn', Idna::IDNA2008_UNICODE);
$info->result(); // returns 'www.食狮.公司.cn'
$info->errors();    // returns Idna::ERROR_NONE
$info->errorList(); // []

$info = Idna::toAscii('www.食狮.公司.cn', Idna::IDNA2008_ASCII);
$info->result();                // returns 'www.xn--85x722f.xn--55qx5d.cn'
$info->errors();                // returns Idna::ERROR_NONE
$info->error(Idna::ERROR_NONE); // returns "No error has occurred"
$info->errorList();             // []
```

In case of error no exception is thrown but the `errors` and the `errorList` methods get
populated.

```php
<?php

use League\Uri\Idna\Idna;

$info = Idna::toAscii('aa'.str_repeat('A', 64).'.％００.com', Idna::IDNA2008_ASCII);
$info->errors();    // Idna::ERROR_LABEL_TOO_LONG | Idna::ERROR_DISALLOWED representing a bitset of the error constants Idna::ERROR_*
$info->errorList(); // returns 
//  array {
//    Idna::ERROR_LABEL_TOO_LONG => "a domain name label is longer than 63 bytes"
//    Idna::ERROR_DISALLOWED => "a label or domain name contains disallowed characters"
//  }
$info->error(Idna::ERROR_LABEL_TOO_LONG); // returns "a domain name label is longer than 63 bytes"
$info->error(Idna::ERROR_DISALLOWED); // returns "a label or domain name contains disallowed characters"
```
