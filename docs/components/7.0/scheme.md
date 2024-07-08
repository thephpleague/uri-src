---
layout: default
title: The Scheme component
---

# The Scheme component

The `Scheme` class represents the URI scheme component and exposes the [package common API](/components/7.0/).

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

Starting with version `7.5.0` the scheme object give a bit more information around the selected scheme. It will let you know:

if you are using an HTTP protocol via its `Scheme::isHttp` method:

~~~php
Scheme::new('FtP')->isHttp(); // return false
Scheme::new('HttPs')->isHttp(); // return true
~~~

if you are using a websocket scheme via its `Scheme::isWebsocket` method:

~~~php
Scheme::new('ws')->isWebsocket(); // return true
Scheme::new('HttPs')->isWebsocket(); // return false
~~~

if you are using a SSL scheme via its `Scheme::isSsl` method:

~~~php
Scheme::new('wss')->isSsl(); // return true
Scheme::new('Http')->isWebsocket(); // return false
~~~

if you are using a special scheme via its `Scheme::isSpecial` method:

~~~php
Scheme::new('ldap')->isSpecial(); // return false
Scheme::new('file')->isSpecial(); // return true
~~~

the default port used by a special scheme via the `Scheme::defaultPort` method:

~~~php
Scheme::new('https')->defaultPort(); // Port::new(443);
Scheme::new('file')->defaultPort(); // Port::new(null);
~~~

If the scheme is not special the method will return a `Port` object equivalent to the `null`
port value.
