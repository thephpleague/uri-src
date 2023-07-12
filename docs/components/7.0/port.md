---
layout: default
title: The Port component
---

# The Port component

The `Port` class represents the URI Port component. Apart from the [package common API](/components/7.0/),
the class exposes an additional `toInt` method which returns the component value as an integer or `null`
if the component is not defined.

~~~php
<?php

use League\Uri\Components\Port;

$port = Port::new(443);
$port->value();                //returns '443'
$port->toInt();                //returns 443
echo $port;                    //displays '443'
echo $port->toString();        //displays '443'
echo $port->getUriComponent(); //displays ':443'

$nullPort = Port::new();
$nullPort->value();                //returns null
$nullPort->toInt();                //returns null
echo $nullPort;                    //displays ''
echo $nullPort->toString();        //displays ''
echo $nullPort->getUriComponent(); //displays ''
~~~

<p class="message-notice">The object can not be modified, you are required to instantiate a new object.</p>
<p class="message-notice">The delimiter <code>:</code> is not part of the component value and <strong>must not</strong> be added.</p>
<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>
