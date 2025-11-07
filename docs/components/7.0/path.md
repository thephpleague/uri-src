---
layout: default
title: Path Component
---

Path Component
=======

The `Path` class represents a generic path component. Apart from the [package common API](/components/7.0/) the class
exposes basic properties and method to manipulate any type of path.

## Leading and Trailing Slash

Most of the time, regardless of the type of Path, you will want to get information around the nature of your path. Is it
absolute or relative? Does it expose a trailing slash or not ? To answer those questions and to manipulate its statuses
you can at any given time get or update the path status.

~~~php
<?php

use League\Uri\Components\Path;

$absolutePath = Path::new("/path/to/the/sky/");
$absolutePath->isAbsolute();       //return true
$absolutePath->hasTrailingSlash(); //return true
echo $absolutePath-;               //displays '/path/to/the/sky/'

$relativePath = $absolutePath->withoutTrailingSlash();
$relativePath->isAbsolute();       //return true;
$relativePath->hasTrailingSlash(); //return false
echo $relativePath;                //displays '/path/to/the/sky'

$noSlash = $relativePath->withLeadingSlash();
$noSlash->isAbsolute();       //return false;
$noSlash->hasTrailingSlash(); //return false
echo $noSlash;                //displays 'path/to/the/sky'
~~~

## Path Modifications

<p class="message-notice">If the modifications do not change the current object, it is returned as is, otherwise, a new modified object is returned.</p>
<p class="message-warning">When a modification fails a <code>League\Uri\Contracts\UriException</code> exception is thrown.</p>

Out of the box, the `Path` object operates a number of non-destructive normalizations. For instance, the path is correctly URI encoded against the RFC3986 rules.

### Removing Dot Segments

To remove dot segments as per [RFC3986](https://tools.ietf.org/html/rfc3986#section-6) you need to explicitly call the `Path::withoutDotSegments` method as the result can be destructive. The method takes no argument and returns a new `Path` object which represents the current object without dot segments.

~~~php
<?php

use League\Uri\Components\Path;

$path = Path::new('path/to/./the/../the/sky%7bfoo%7d');
$newPath = $path->withoutDotSegments();
echo $path;     //displays 'path/to/./the/../the/sky%7bfoo%7d'
echo $newPath;  //displays 'path/to/the/sky%7Bfoo%7D'
~~~

## Specialized Path Objects

What makes a URI specific apart from the scheme is how the path is parse and manipulated. This simple path class
although functional will not ease parsing a Data URI path or an HTTP Uri path. That's why the library comes bundles
with two specialized Path objects that decorates the current object and adds more specific methods in accordance
to the path usage:

- the [HierarchicalPath](/components/7.0/hierarchical-path/) object to work with a Hierarchical paths component
- the [DataPath](/components/7.0/data-path/) object to work with the Data URIs path
