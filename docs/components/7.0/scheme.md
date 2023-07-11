---
layout: default
title: The Scheme component
---

# The Scheme component

The `Scheme` class represents the URI scheme component and only exposes the [package common API](/components/7.0/api/).

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

$alt_scheme = Scheme::fromUri('email:toto@example.com');
echo $alt_scheme->value();           //display 'email'
echo $alt_scheme->toString();        //display 'email'
echo $alt_scheme;                    //display 'email'
echo $alt_scheme->getUriComponent(); //display 'email'
~~~

<p class="message-notice">The object can not be modified, you are required to instantiate a new object.</p>
<p class="message-notice">The delimiter <code>:</code> is not part of the component value and <strong>must not</strong> be added.</p>
<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>
