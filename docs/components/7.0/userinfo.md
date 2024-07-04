---
layout: default
title: The User information component
---

The UserInfo
=======

The `UserInfo` class represents a URI authority component. Apart from the [package common API](/components/7.0/) the class
exposes basic properties and method to manipulate its different component.

<p class="message-notice">If the modifications do not change the current object, it is returned as is, otherwise, a new modified object is returned.</p>
<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>

## Creating a new object

The `UserInfo` class comes with named constructors to ease instantiation. The following examples show
how to instantiate the class:

<p class="message-notice">submitted string is normalized to be <code>RFC3986</code> compliant.</p>

~~~php
<?php

use League\Uri\Components\UserInfo;
use League\Uri\UriString;

$authority = new UserInfo('user', 'pass');
$authority->toString(); //returns 'user:pass'

UserInfo::new('user:pass')->value(); //returns 'user:pass'
UserInfo::fromUri("http://www.example.com/path/to/the/sky")->getUser(); //return null;
UserInfo::new()->value(); //return null;
UserInfo::fromComponents(
	UriString::parse("http://user:pass@example.com:42/5.0/uri/api")
)->value(); //returns 'user:pass'
~~~

<p class="message-notice">submitted string is normalized to be <code>RFC3986</code> compliant.</p>
<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>

## Accessing User information content

To access the user login and password information you need to call the respective `UserInfo::getUser`
and `UserInfo::getPass` methods like shown below.

~~~php
use League\Uri\Components\UserInfo;

$info = new UserInfo('user', 'p@ss');
$info->getUser();    //returns 'user'
$info->getPass();    //returns 'p@ss'
$info->getUsername(); //returns 'user'
$info->getPassword(); //returns 'p%40ss'
$info->components(); //returns array {"user" => "user", "pass" => "p@ss"}
~~~

<p class="message-notice"><code>getUsername</code> and <code>getPassword</code> are added in version <code>7.5.0</code></p>

## Modifying the user information

<p class="message-notice">If the modifications do not change the current object, it is returned as is, otherwise, a new modified object is returned.</p>

~~~php
use League\Uri\Components\UserInfo;

$info = UserInfo::fromUri('https://login:pass@thephpleague.com/path/to/heaven');
echo $info;  //displays login:pass
echo $info->withUser('john')->withPass('doe'); //displays john:doe
~~~

<p class="message-warning">If the user part is `null`, trying to give the password any other value than the `null` value with throw an Exception.</p>

~~~php
new UserInfo(null, 'bar');  // throws a SyntaxError
UserInfo::fromAuthority('thephpleague:443')->withPass('foo'); // throws a SyntaxError
~~~

<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>
