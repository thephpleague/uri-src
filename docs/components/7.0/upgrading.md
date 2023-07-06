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

Deprecated methods
--------

- All named constructors `createFromString` and `createFromNull` are replaced by the new named constructor `new`
- The `Domain::createFromHost` is also replaced by the `Domain::new` method

Depending on the component the method will accept either 

- a string and a stringable object;
- `null` (except for the `Path` related classes);
- an integer (for the `Port` class);
- or no argument at all (for the `Query` class);

````diff
<?php

use League\Uri\Components\Host;

- Host::createFromString('bébé.be')->value(); //returns 'xn--bb-bjab.be'
+ Host::new('bébé.be')->value(); //returns 'xn--bb-bjab.be'

- Host::createFromNull()->value(); //returns null
+ Host::new()->value(); //returns null
````

For the `HierarchicalPath`, the `createAbsoluteFromSegments` and `createRelativeFromSegments` named constructors
are being replaced by `fromRalative` and `fromAbsolute`. The signature is also updated
from `iterable` to `string` as variadic to allow easier validation of input.

````diff
<?php

use League\Uri\Components\HierarchicalPath;

- HierarchicalPath::createAbsoluteFromSegments(['who', 'are', 'you'])->value(); //returns '/who/are/you'
+ HierarchicalPath::fromAbsolute('who', 'are', 'you')->value(); //returns '/who/are/you'

- HierarchicalPath::createRelativeFromSegments(['who', 'are', 'you'])->value(); //returns 'who/are/you'
+ HierarchicalPath::fromRelative('who', 'are', 'you')->value(); //returns 'who/are/you'
````

For `Query::createFromRFC1738` and `Query::createFromRFC3986` are replaced by `Query::fromRFC1738` and `Query::fromRFC3986`
with a change in signature. The query string needs to be explicitly set otherwise an exception
will be thrown.

````diff
<?php

use League\Uri\Components\Query;

- Query::createFromRFC1738()->value(); //returns ''
+ Query::fromRFC1738('')->value();     //returns ''
+ Query::new()->value();               //returns null
+ Query::fromRFC1738();                //will throw
````

All remaining named constructors which starts with `createFrom*` are replaced by the same method starting with `from*`.

````diff
<?php

use League\Uri\Components\Port;
use League\Uri\Uri;

- Port::createFromUri(Uri::createFromString('https://example.com:82'))->value(); //returns '82'
+ Port::fromUri('https://example.com:82')->value();  //returns '82'
````

Removed
----------

###  UserInfo modifier method removed

The `UserInfo::withUserInfo` modifier method is removed and can be replaced by combining the two
new modifier methods introduced `UserInfo::withUser` and/or `UserInfo::withUPass`.

````diff
<?php

use League\Uri\Components\UserInfo;

- (new UserInfo('user', 'pass'))->withUserInfo('user', 'newPass')->value(); // returns 'user:newPass'
+ (new UserInfo('user', 'pass'))->withPass('newPass')->value(); // returns 'user:newPass'
````

###  `withContent` and `getContent` methods

The two methods were already deprecated in version 2. And they are now removed in version 7.
Of note, it means that the `Scheme` and `Fragment` objects no longer contain methods to change
their value once instantiated.

````diff
<?php

use League\Uri\Components\Fragment;

- Fragmnt::createFromString('header1')->withContent('header2')->getUriComponent(); // returns '#header2'
+ Fragment::new('header1')->getUriComponent(); // returns '#header1'
+ Fragment::new('header2')->getUriComponent(); // returns '#header2'
````

To modify such component you are now required to create a new instance.

### other notable changes

- `withContent` and `getContent` methods, already deprecated in version 2;
- Support for `__set_state` with no replacement;
- Support for `float` type as possible argument for components;
- Support for `int` type on `UriModifier` methods argument MUST be converted to string;
- `Domain` value can be `null` previously it would trigger an exception.
