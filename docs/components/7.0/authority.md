---
layout: default
title: Authority URI part Object API
---

The Authority part
=======

The `Authority` class represents a URI authority component. Apart from the [package common API](/components/7.0/) the class
exposes basic properties and method to manipulate its different component.

<p class="message-notice">If the modifications do not change the current object, it is returned as is, otherwise, a new modified object is returned.</p>
<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>

## Instantiation

The `Authority` class comes with named constructors to ease instantiation. The following examples show
how to instantiate the class:

<p class="message-notice">submitted string is normalized to be <code>RFC3986</code> compliant.</p>

~~~php
<?php

use League\Uri\Components\Authority;
use League\Uri\UriString;

$authority = new Authority('eXamPle.cOm', 42, 'user:pass');
$authority->toString(); //returns 'user:pass@example.com:42'

Authority::new('user:pass@example.com:42')->value(); //returns 'user:pass@example.com:42'
Authority::fromUri("http://www.example.com/path/to/the/sky")->getPort(); //return null;
Authority::new()->value(); //return null;
Authority::fromComponents(
	UriString::parse("http://user:pass@example.com:42/5.0/uri/api")
)->value(); //returns 'user:pass@example.com:42'
~~~

<p class="message-notice">if no string is given a instance is returns using the empty string.</p>
<p class="message-warning">If you supply your own hash to <code>fromComponents</code>, you are responsible for providing well parsed components without their URI delimiters.</p>

Accessing Properties
-------

You can access the authority string, its individual parts and components using their respective getter methods. This lead to the following result for a simple HTTP URI:

~~~php
use League\Uri\Components\Authority;

$authority = Authority::new("foo:bar@www.example.com:81");
echo $authority->getUserInfo();  //displays "foo:bar"
echo $authority->getHost();      //displays "www.example.com"
echo $authority->getPort();      //displays 81 as an integer
echo $authority;
//displays "foo:bar@www.example.com:81"
echo json_encode($authority);
//displays "foo:bar@www.example.com:81"
$authority->components(); 
// returns array {
//   "user" => "foo",
//   "pass" => "bar",
//   "host" => "www.example.com",
//   "port" => 81,
// }
~~~

Modifying Properties
-------

To replace one of the URI components, you can use the modifying methods exposed by all URI object. If the modifications do not alter the current object, it is returned as is, otherwise, a new modified object is returned.
<p class="message-notice">Any modification method can trigger a <code>League\Uri\Contracts\UriException</code> exception if the resulting URI is not valid. Just like with the instantiation methods, validation is scheme dependant.</p>
Since All URI object are immutable you can chain each modifying methods to simplify URI creation and/or modification.

~~~php
echo Authority::new("thephpleague.com")
    ->withUserInfo("foo", "bar")
    ->withHost("www.example.com")
    ->withPort(81)
    ->toString(); //displays "//foo:bar@www.example.com:81"
~~~

Normalization
-------

Out of the box the package normalizes the URI part according to the non-destructive rules of RFC3986.

These non-destructive rules are:

- scheme and host components are lowercased;
- the host is converted to its ascii representation using punycode if needed

~~~php
echo Authority::new("www.ExAmPLE.com:80"); //displays www.example.com:80
~~~

<p class="message-info">Host conversion depends on the presence of the <code>idn_to_*</code> functions, if missing the code will trigger a <code>MissingFeature</code> exception</p>
