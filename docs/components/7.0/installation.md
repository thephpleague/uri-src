---
layout: default
title: Installation
---

System Requirements
-------

You need **PHP >= 8.1.0** but the latest stable version of PHP is recommended

If you want to handle:

- Data URI creation from a file content **requires** the `fileinfo` extension.
- IDN host you are **required** to install the `intl` extension;
- IPv4 host in octal or hexadecimal form, out of the box, you **need** at least one of the following extension:

    - install the `GMP` extension **or**
    - install the `BCMath` extension

  or you should be using

    - a `64-bits` PHP version

Trying to process such URI components without meeting those minimal requirements will trigger a `RuntimeException`.

Installation
--------

~~~
$ composer require league/uri-components:^7.0
~~~

Dependencies
-------

- [League Uri Interfaces](https://github.com/thephpleague/uri-interfaces)
- [League Uri](https://github.com/thephpleague/uri)
- [PSR-7](http://www.php-fig.org/psr/psr-7/)
