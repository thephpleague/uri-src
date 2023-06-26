---
layout: default
title: The Scheme component
---

# The Scheme component

The `Scheme` class eases scheme creation and manipulation. This URI component object only exposes the [package common API](/components/7.0/api/).

## Creating a new object

~~~php
<?php
public static Scheme::new(Stringable|string|null $content = null): self
public static Scheme::fromUri(Stringable|string $uri): self
~~~

## Properties and methods

This URI component object only exposes the [package common API](/components/7.0/api/).

## Usage

~~~php
<?php

use League\Uri\Components\Scheme;

$scheme = Scheme::new('FtP');
echo $scheme->value();           //display 'ftp'
echo $scheme->toString();        //display 'ftp'
echo $scheme;                    //display 'ftp'
echo $scheme->getUriComponent(); //display 'ftp:'

$new_scheme = Scheme::new();
echo $new_scheme->value();           //display null
echo $new_scheme->toString();        //display ''
echo $new_scheme;                    //display ''
echo $new_scheme->getUriComponent(); //display ''

$alt_scheme = Scheme::new('email:toto@example.com');
echo $alt_scheme->value();           //display 'email'
echo $alt_scheme->toString();        //display 'email'
echo $alt_scheme;                    //display 'email'
echo $alt_scheme->getUriComponent(); //display 'email'
~~~

<p class="message-notice">The object can not be modified, you are required to instantiate a new object.</p>
<p class="message-notice">The delimiter <code>:</code> is not part of the component value and <strong>must not</strong> be added.</p>
<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>
