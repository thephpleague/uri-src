---
layout: default
title: The Domain component
---

The Domain Host
=======

The `Domain` class represents a domain name host component. Apart from the [package common API](/components/7.0/)
and the [host common API](/components/7.0/path), the class exposes specific properties and methods to
work with Domain name labels and logic.

<p class="message-notice">If the modifications do not change the current object, it is returned as is, otherwise, a new modified object is returned.</p>
<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>

## Partial or fully qualified domain name

A host is absolute or a fully qualified domain name (FQDN) if it contains a <strong>root label</strong>, its string
representation ends with a `.`, otherwise it is known as being a relative or a partially qualified domain name (PQDN).
The class allows use to get the domain name status and update it using the following feature

~~~php
$host = Domain::new('www.11.be');
$host->isAbsolute(); // return false

$fqdn = $host->withRootLabel(); //display 'www.11.be.'
$fqdn->isAbsolute(); // return true

$pqdn = $fqdn->withoutRootLabel(); //display 'www.11.be'
$fqdn->isAbsolute(); // return false
~~~

## Manipulating the domain name as an ordered list of labels

A domain is an ordered list of labels delimited by the host separator `.`. So it is possible to create a `Domain`
object using a collection of labels with the `Domain::fromLabels` method.
The method expects variadic of string or stringable objects representing the domain labels. 

<p class="message-warning">The labels must be ordered, this means that the top-level domain is the first entry</p>.
<p class="message-warning">Since an IP is not a domain name, the class will throw an
<code>League\Uri\Exceptions\SyntaxError</code> if you try to create a fully qualified domain name with a valid IP
address.</p>

~~~php
echo Domain::fromLabels('com', 'example', 'shop');     //display 'shop.example.com'
echo Domain::fromLabels('', 'com', 'example', 'shop'); //display 'shop.example.com.
Domain::fromLabels('0.1', '127.0'); //throws League\Uri\Exceptions\SyntaxError
~~~

The class implements PHP’s `Countable` and `IteratorAggregate` interfaces which means that you can count the number
of labels and use the foreach construct to iterate overs them.

~~~php
$host = Domain::fromLabels('com', 'example', 'shop'); //display 'shop.example.com'
count($host); //return 3
foreach ($host as $offset => $label) {
    //do something meaningful here
}
[...Domain::new('uri.thephpleague.com')];  //return ['com', 'thephpleague', 'uri'];
~~~

### Accessing the host labels and keys

Since we are manipulating the domain name as an ordered list we can use known methods to access the labels and their keys
as with normal lists.

~~~php
<?php

use League\Uri\Components\Domain;

$path = .Domain::new('www.bbc.co.uk');
$path->keys();          //return [0, 1, 2, 3];
$path->keys('www');     //return [3];
$path->keys('gweta');   //return [];
$path->get(0);          //return 'uk'
$path->get(23);         //return null
$path->get(-1);         //return 'www'
$path->get(-23);        //return null
~~~

<p class="message-notice"><code>keys</code> always decode the submitted value to process the label data.</p>
<p class="message-notice"><code>get</code> always returns the decoded representation.</p>
<p class="message-notice">If the offset does not exist <code>get</code> will return <code>null</code>.</p>
<p class="message-info"><code>get</code> supports negative offsets</p>

### Appending and Prepending labels

You can append or prepend labels to the current instance using the `Domain::append` and/or the `Domain::prepend` methods.
Both method accepts a single argument which represents the data to be appended or prepended.

~~~php
echo Domain::new('toto')->append('example.com'); //return toto.example.com
echo Domain::new('example.com')->prepend('toto'); //return toto.example.com
~~~

#### Replacing and removing labels

Replacing or removing labels is done on the basis of the label offsets by using the `Domain::replaceLabel` and/or
the `Domain::withoutLabels` methods.

~~~php
echo Domain::new('foo.example.com')->replaceLabel(2, 'bar.baz'); //return bar.baz.example.com
echo Domain::new('toto.example.com')->withoutLabels(0, 2); //return example
~~~

<p class="message-info"><code>replaceLabel</code> and <code>withoutLabels</code> support negative offsets</p>
<p class="message-warning">if the specified offsets do not exist, no modification is performed and the current object is returned.</p>
