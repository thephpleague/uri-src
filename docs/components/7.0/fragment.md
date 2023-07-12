---
layout: default
title: The Fragment component
---

# The Fragment component

The `Fragment` class represents the URI fragment component. Apart from the [package common API](/components/7.0/),
the class exposes an additional `decoded` method to return the component value safely decoded.

~~~php
<?php

use League\Uri\Components\Fragment;

$fragment = Fragment::new('%E2%82%AC');
echo $fragment->value();           //display '%E2%82%AC'
echo $fragment->decoded();         //display '€'
echo $fragment->toString();        //display '%E2%82%AC'
echo $fragment->getUriComponent(); //display '#%E2%82%AC'
echo $fragment;                    //display '%E2%82%AC'

$newFragment = Fragment::new();
echo $newFragment->value();           //display null
echo $newFragment->decoded();         //display ''
echo $newFragment->toString();        //display ''
echo $newFragment->getUriComponent(); //display ''
echo $newFragment;                    //display ''

$altFragment = Fragment::fromUri('https://thephpleague.com#');
echo $altFragment->value();           //display ''
echo $altFragment->decoded();         //display ''
echo $altFragment->toString();        //display ''
echo $altFragment->getUriComponent(); //display '#'
echo $altFragment;                    //display ''
~~~

<p class="message-notice">The object can not be modified, you are required to instantiate a new object.</p>
<p class="message-notice">The delimiter <code>:</code> is not part of the component value and <strong>must not</strong> be added.</p>
<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>
