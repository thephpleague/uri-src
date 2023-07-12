---
layout: default
title: Path Component
---

Path Component
=======

The `Path` class represents a generic path component. Apart from the [package common API](/components/7.0/) the class
exposes basic properties and method to manipulate any type of path.

## Path leading and trailing slash statuses

Most of the time, regardless of the type of Path, you will want to get information around the nature of your path. Is it
absolute or relative ? Does it expose a trailing slash or not ? To answer those question you can at any given time
test your path status with two methods `Path::isAbsolute` and `Path::hasTrailingSlash`

~~~php
<?php

use League\Uri\Components\Path;

Path::new('bar/baz')->isAbsolute(); //return false;
Path::new('/bar/baz')->isAbsolute(); //return true;
Path::new()->isAbsolute(); //return false;
Path::new('/path/to/the/sky.txt')->hasTrailingSlash(); //return false
Path::new('/path/')->hasTrailingSlash();               //return true
~~~

## Path modifications

<p class="message-notice">If the modifications do not change the current object, it is returned as is, otherwise, a new modified object is returned.</p>
<p class="message-warning">When a modification fails a <code>League\Uri\Contracts\UriException</code> exception is thrown.</p>

Out of the box, the `Path` object operates a number of non-destructive normalizations. For instance, the path is correctly URI encoded against the RFC3986 rules.

### Removing dot segments

To remove dot segment as per [RFC3986](https://tools.ietf.org/html/rfc3986#section-6) you need to explicitly call the `Path::withoutDotSegments` method as the result can be destructive. The method takes no argument and returns a new `Path` object which represents the current object without dot segments.

~~~php
<?php

use League\Uri\Components\Path;

$path = Path::new('path/to/./the/../the/sky%7bfoo%7d');
$newPath = $path->withoutDotSegments();
echo $path;     //displays 'path/to/./the/../the/sky%7bfoo%7d'
echo $newPath;  //displays 'path/to/the/sky%7Bfoo%7D'
~~~

### Removing empty segments

Sometimes your path may contain multiple adjacent delimiters. Since removing them may result in a semantically different URI, this normalization can not be applied by default. To remove adjacent delimiters you can call the `Path::withoutEmptySegments` method which convert you path as described below:

~~~php
<?php

use League\Uri\Components\Path;

$path = Path::new("path////to/the/sky//");
$newPath = $path->withoutEmptySegments();
echo $path;    //displays 'path////to/the/sky//'
echo $newPath; //displays 'path/to/the/sky/'
~~~

### Manipulating the trailing slash

Depending on your context you may want to add or remove the path trailing slash. In order to do so the `Path` object uses two methods which accept no argument.

`Path::withoutTrailingSlash` will remove the ending slash of your path only if a slash is present.

~~~php
<?php

use League\Uri\Components\Path;

$path = Path::new("path/to/the/sky/");
$newPath = $path->withoutTrailingSlash();

echo $path;     //displays 'path/to/the/sky/'
echo $newPath;  //displays 'path/to/the/sky'
~~~

Conversely, `Path::withTrailingSlash` will append a slash at the end of your path only if no slash is already present.

~~~php
<?php

use League\Uri\Components\Path;

$path = Path::new("/path/to/the/sky");
$newPath = $path->withTrailingSlash();

echo $path;    //displays '/path/to/the/sky'
echo $newPath; //displays '/path/to/the/sky/'
~~~

### Manipulating the leading slash

Conversely, to convert the path type the `Path` object uses two methods which accept no argument.

`Path::withoutLeadingSlash` will convert an absolute path into a relative one by removing the path leading slash if present.

~~~php
<?php

use League\Uri\Components\Path;

$path = Path::new("path/to/the/sky/");
$newPath = $path->withoutTrailingSlash();

echo $path;    //displays 'path/to/the/sky/'
echo $newPath; //displays 'path/to/the/sky'
~~~

`Path::withLeadingSlash` will convert a relative path into a absolute one by prepending the path with a slash if none is present.

~~~php
<?php

use League\Uri\Components\Path;

$path = Path::new("/path/to/the/sky");
$newPath = $path->withTrailingSlash();

echo $path; //displays '/path/to/the/sky'
echo $newPath;  //displays '/path/to/the/sky/'
~~~

## Specialized Path Object

What makes a URI specific apart from the scheme is how the path is parse and manipulated. This simple path class
although functional will not ease parsing a Data URI path or an HTTP Uri path. That's why the library comes bundles
with two specialized Path objects that decorates the current object and adds more specific methods in accordance
to the path usage:

- the [HierarchicalPath](/components/7.0/hierarchical-path/) object to work with Hierarchical paths component
- the [DataPath](/components/7.0/data-path/) object to work with the Data URIs path
