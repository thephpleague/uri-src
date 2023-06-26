---
layout: default
title: The User information component
---

The UserInfo
=======

The `League\Uri\Components\UserInfo` class eases user information creation and manipulation.
This URI component object exposes the [package common API](/components/7.0/api/),
but also provide specific methods to work with the URI user information part.

## Creating a new object

~~~php
public UserInfo::__construct(Stringable|string|null $user, Stringable|string|null $pass = null): void
public static UserInfo::new(Stringable|string|null $value = null): self
public static UserInfo::fromUri(Stringable|string|null $uri): self
public static UserInfo::fromAuthority(Stringable|string|null $authority): self
~~~

<p class="message-notice">submitted string is normalized to be <code>RFC3986</code> compliant.</p>

<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>

## Accessing User information content

~~~php
public UserInfo::getUser(): ?string
public UserInfo::getPass(): ?string
~~~

To access the user login and password information you need to call the respective `UserInfo::getUser` and `UserInfo::getPass` methods like shown below.

~~~php
$info = new UserInfo('foo', 'bar');
$info->getUser(); //return 'foo'
$info->getPass(); //return 'bar'
~~~

## Modifying the user information

~~~php
public UserInfo::withUser(Stringable|string|null $user): self
public UserInfo::withPass(Stringable|string|null $pass): self
~~~

<p class="message-notice">If the modifications do not change the current object, it is returned as is, otherwise, a new modified object is returned.</p>

~~~php
$info = UserInfo::fromUri('https://login:pass@thephpleague.com/path/to/heaven');
$new_info = $info->withUser('john')->withPass('doe');
echo $new_info; //displays john:doe
echo $info;     //displays login:pass
~~~

<p class="message-warning">If the user part is `null`, trying to give the password any other value than the `null` value with throw an Exception.</p>

~~~php
new UserInfo(null, 'bar');  // throws a SyntaxError
UserInfo::fromAuthority('thephpleague:443')->withPassword('foo'); // throws a SyntaxError
~~~

<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>
