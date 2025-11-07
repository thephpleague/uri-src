---
layout: default
title: Upgrading from 2.x to 7.x
---

# Upgrading from 2.x to 7.x

`league\uri-components 7.0` is a new major version that comes with backward compatibility breaks.

This guide will help you migrate from a 2.x version to 7.0. It will only explain backward 
compatibility breaks, it will not present the new features 
([read the documentation for that](/uri-components/7.0/)).

## Installation

If you are using composer then you should update the `require` section of your `composer.json` file.

~~~
composer require league/uri-components:^7.0
~~~

This will edit (or create) your `composer.json` file.

## PHP version requirement

`league\uri-components 7.0` requires a PHP version greater or equal than 8.1.0 (was previously 7.2.0).

Dependencies
-------

The new version requires `League\Uri v7.0` to work. Previously only the `league/uri-interfaces`
package was required.

- [League Uri Interfaces](https://github.com/thephpleague/uri-interfaces)
- [League Uri](https://github.com/thephpleague/uri)
- [PSR-7](http://www.php-fig.org/psr/psr-7/)

Deprecated classes
--------

The `League\Uri\UriModifer` class is deprecated in favor of the `League\Uri\Modifier` class:

Before:

~~~php
<?php

use League\Uri\Uri;
use League\Uri\UriModifier;

$uri = Uri::createFromString('http://example.com?q=value#fragment');
$newUri = UriModifier::appendQuery($uri, 'q=new.Value');
echo $newUri::class; // return League\Uri\Uri
echo $newUri; // 'http://example.com?q=value&q=new.Value#fragment'
~~~

After:

~~~php
<?php

use League\Uri\Uri;
use League\Uri\Modifier;

$newUri = Modifier::from('http://example.com?q=value#fragment')->appendQuery('q=new.Value');
echo $newUri::class; // return League\Uri\Modifier
echo $newUri;                 // 'http://example.com?q=value&q=new.Value#fragment'
echo $newUri->getUriString(); // 'http://example.com?q=value&q=new.Value#fragment'
$newUri->getUri()::class // return League\Uri\Uri
~~~

the `League\Uri\IPv4Normalizer` class is deprecated, you need to use the `League\Uri\Ipv4\Converter` class instead

Before:

~~~php
<?php

use League\Uri\IPv4Normalizer;
use League\Uri\Components\Host;

$host = new Host('0');
$normalizer = new IPv4Normalizer();
$normalizedHost = $normalizer->normalizeHost($host);
echo $host;           // returns 0 
echo $normalizedHost; // returns 0.0.0.0 ($normalizeHost is a Host object)
~~~

After:

~~~php
<?php

use League\Uri\IPv4\Converter;
use League\Uri\Components\Host;

$host = new Host('0');
$normalizedHost = Converter::fromEnvironment()($host);
echo Host::new($normalizedHost); // returns 0.0.0.0 
~~~

Deprecated methods
--------

The following methods are marked as deprecated. They are still present to allow an easier upgrade path
to version `7.0`, but it is recommended not to use them for new projects.

| Deprecated methods                             | New stable methods                |
|------------------------------------------------|-----------------------------------|
| `Authority::createFromString`                  | `Authority::new`                  |
| `Authority::createFromUri`                     | `Authority::new`                  |
| `Authority::createFromNull`                    | `Authority::new`                  |
| `Authority::createFromServer`                  | `Authority::fromServer`           |
| `Authority::createFromComponents`              | `Authority::fromComponents`       |
| `DataPath::createFromString`                   | `DataPath::new`                   |
| `DataPath::createFromUri`                      | `DataPath::fromUri`               |
| `DataPath::createFromFilePath`                 | `DataPath::fromFileContents`      |
| `Domain::createFromHost`                       | `Domain::new`                     |
| `Domain::createFromString`                     | `Domain::new`                     |
| `Domain::createFromLabels`                     | `Domain::fromLabels`              |
| `Domain::createFromUri`                        | `Domain::fromUri`                 |
| `Domain::createFromAuthority`                  | `Domain::fromAuthority`           |
| `Fragment::createFromString`                   | `Fragment::new`                   |
| `Fragment::createFromUri`                      | `Fragment::fromUri`               |
| `Scheme::createFromString`                     | `Scheme::new`                     |
| `Scheme::createFromUri`                        | `Scheme::fromUri`                 |
| `Path::createFromString`                       | `Path::new`                       |
| `Path::createFromUri`                          | `Path::fromUri`                   |
| `Port::fromInt`                                | `Port::new`                       |
| `Port::createFromUri`                          | `Port::fromUri`                   |
| `Port::createFromAuthority`                    | `Port::fromAuthority`             |
| `Host::createFromString`                       | `Host::new`                       |
| `Host::createFromUri`                          | `Host::new`                       |
| `Host::createFromNull`                         | `Host::new`                       |
| `Host::createFromIp`                           | `Host::fromIp`                    |
| `HierarchicalPath::createFromString`           | `HierarchicalPath::new`           |
| `HierarchicalPath::createFromUri`              | `HierarchicalPath::new`           |
| `HierarchicalPath::createFromPath`             | `HierarchicalPath::new`           |
| `HierarchicalPath::createRelativeFromSegments` | `HierarchicalPath::fromRelative`  |
| `HierarchicalPath::createAbsoluteFromSegments` | `HierarchicalPath::fromAbsolute`  |
| `Query::createFromParams`                      | `Query::fromParameters`           |
| `Query::createFromPairs`                       | `Query::fromPairs`                |
| `Query::createFromUri`                         | `Query::fromUri()`                |
| `Query::createFromRFC3986`                     | `Query::fromRFC3986`              |
| `Query::createFromRFC1738`                     | `Query::fromRFC1738`              |
| `Query::params`                                | `Query::parameter`                |
| `Query::params`                                | `Query::parameters`               |
| `Query::withoutParams`                         | `Query::withoutParameters`        |
| `Query::toRFC3986`                             | `Query::value`                    |


For the `Domain`, the `createFromLabels` named constructor is being replaced by `fromLabels`.
The signature is also updated from `iterable` to `string` as variadic to allow easier validation of input.

````diff
- Domain::createFromLabels(['who', 'are', 'you'])->value(); //returns 'you.are.who'
+ Domain::fromLabels('who', 'are', 'you')->value(); //returns 'you.are.who'
````

For the `HierarchicalPath`, the `createAbsoluteFromSegments` and `createRelativeFromSegments` named constructors
are being replaced by `fromRelative` and `fromAbsolute`. The signature is also updated
from `iterable` to `string` as variadic to allow easier validation of input.

````diff
- HierarchicalPath::createAbsoluteFromSegments(['who', 'are', 'you'])->value(); //returns '/who/are/you'
+ HierarchicalPath::fromAbsolute('who', 'are', 'you')->value(); //returns '/who/are/you'
````

````diff
- HierarchicalPath::createRelativeFromSegments(['who', 'are', 'you'])->value(); //returns 'who/are/you'
+ HierarchicalPath::fromRelative('who', 'are', 'you')->value(); //returns 'who/are/you'
````

For `Query::createFromRFC1738` and `Query::createFromRFC3986` are replaced by `Query::fromRFC1738` and `Query::fromRFC3986`
with a change in signature. If the query string is not explicitly set it is considered to be the `null` value before
it was falling back on the empty string.

````diff
- Query::createFromRFC1738()->value(); //returns ''
+ Query::fromRFC1738()->value();       //returns null
+ Query::fromRFC1738('')->value();     //returns ''
````

All remaining named constructors which starts with `createFrom*` are replaced by the same method starting with `from*`.

````diff
- Port::createFromUri(Uri::createFromString('https://example.com:82'))->value(); //returns '82'
+ Port::fromUri('https://example.com:82')->value();  //returns '82'
````

Removed
----------

###  UserInfo modifier method removed

The `UserInfo::withUserInfo` modifier method is removed and can be replaced by combining the two
new modifier methods introduced `UserInfo::withUser` and/or `UserInfo::withUPass`.

````diff
- (new UserInfo('user', 'pass'))->withUserInfo('user', 'newPass')->value(); // returns 'user:newPass'
+ (new UserInfo('user', 'pass'))->withPass('newPass')->value(); // returns 'user:newPass'
````

###  `withContent` and `getContent` methods

The two methods were already deprecated in version 2. And they are now removed in version 7.
Of note, it means that the `Scheme` and `Fragment` objects no longer contain methods to change
their value once instantiated.

````diff
- Fragmnt::createFromString('header1')->withContent('header2')->getUriComponent(); // returns '#header2'
+ Fragment::new('header1')->getUriComponent(); // returns '#header1'
+ Fragment::new('header2')->getUriComponent(); // returns '#header2'
````

To modify the component content, you are now required to create a new instance.

### Other Notable Changes

- Support for `__set_state` with no replacement;
- Support for `float` type as possible argument for components;
- `Domain` value can be `null` previously it would trigger an exception.
