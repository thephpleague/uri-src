---
layout: default
title: The Hierarchical Path component
---

# The Hierarchical Path component

The `HierarchicalPath` class represents an ordered list of segmented path component. Apart from the [package common API](/components/7.0/)
and the [path common API](/components/7.0/path), the class exposes specific properties and methods to
work with segments-type URI path components.

<p class="message-notice">If the modifications do not change the current object, it is returned as is, otherwise, a new modified object is returned.</p>
<p class="message-warning">When a modification fails a <code>League\Uri\Contracts\UriException</code> exception is thrown.</p>

## Manipulating the path as a filesystem path

The `HierarchicalPath` allows you to access and manipulate the path as if it was a filesystem path.

~~~php
<?php

use League\Uri\Components\HierarchicalPath;

$path = HierarchicalPath::new('/path/to/the/sky.txt;foo=bar');
$path->getExtension(); //return 'txt'
$path->getBasename();  //return 'sky.txt'
$path->getDirname();   //return '/path/to/the'

echo $path->withDirname('/foo')->withExtension('csv'); // display /foo/sky.csv;foo=bar
echo $path->withBasename('paradise.html'); // display /path/to/the/paradise.html
~~~

<p class="message-info"><code>getBasename</code> and <code>withBasename</code> do not alter or return the path parameters (the part after and including the <code>;</code>).</p>
<p class="message-warning"><code>withExtension</code> will throw an <code>League\Uri\Components\Exception</code> exception if the extension contains the path delimiter.</p>

## Manipulating the path as an ordered list of segments

A hierarchical path can be represented as an ordered list of segments.

<p class="message-info">A path ending with a slash will have an empty string as the last member of its array representation.</p>

### Instantiation using a collection of path segments.

A path is a ordered list of segment delimited by the path delimiter `/`. So it is possible to 
create a `HierarchicalPath` object using a collection of segments with two specific named constructors
`HierarchicalPath::fromRelative` and `HierarchicalPath::fromAbsolute`, the methods expect variadic
of string or stringable objects representing the path segments.

~~~php
<?php

use League\Uri\Components\HierarchicalPath;

echo HierarchicalPath::fromRelative('shop', 'example', 'com');     //display 'shop/example/com'
echo HierarchicalPath::fromRelative('shop', 'example', 'com', ''); //display 'shop/example/com/'
echo HierarchicalPath::fromAbsolute('shop', 'example', 'com');     //display '/shop/example/com'
echo HierarchicalPath::fromAbsolute('shop', 'example', 'com', ''); //display '/shop/example/com/'
~~~

The class implements PHP's `Countable` and `IteratorAggregate` interfaces. This means that you can count the number of segments and use the `foreach` construct to iterate overs them.

~~~php
<?php

use League\Uri\Components\HierarchicalPath;

$path = HierarchicalPath::fromUri('https://thephpleague.com/path/to/the/sky');
count($path); //return 4
foreach ($path as $offset => $segment) {
    //do something meaningful here
}

[...HierarchicalPath::new('/path/to/the/sky')];  //return ['path', 'to', 'the', 'sky'];
[...HierarchicalPath::new('/path/to/the/sky/')]; //return ['path', 'to', 'the', 'sky', ''];
[...HierarchicalPath::new('path/to/the/sky/')];  //return ['path', 'to', 'the', 'sky', ''];
~~~

### Accessing the path segments and keys

Since we are manipulating the path as an ordered list we can use known methods to acces the path segments and keys 
as with normal lists.

~~~php
<?php

use League\Uri\Components\HierarchicalPath;

$path = HierarchicalPath::new('/path/to/the/sky');
$path->keys();          //return [0, 1, 2, 3];
$path->keys('sky');     //return [3];
$path->keys('gweta');   //return [];
$path->get(0);          //return 'path'
$path->get(23);         //return null
$path->get(-1);         //return 'sky'
$path->get(-23);        //return null
$path->get(-23, 'now'); //return 'now'
~~~

<p class="message-notice"><code>keys</code> always decode the submitted value to process the segment data.</p>
<p class="message-notice"><code>get</code> always returns the decoded representation.</p>
<p class="message-notice">If the offset does not exist <code>get</code> will return <code>null</code>.</p>
<p class="message-info"><code>get</code> supports negative offsets</p>

### Append and prepend segments

To append segments to the current object you need to use the `HierarchicalPath::append` method. This method accept a single argument which represents the data to be appended:

~~~php
<?php

use League\Uri\Components\HierarchicalPath;

HierarchicalPath::new()->append('path')->append('to/the/sky')->value();   //return path/to/the/sky
HierarchicalPath::new()->prepend('sky')->prepend('path/to/the')->value(); //return path/to/the/sky
~~~

### Replace and Remove segments

To replace of remove segment you must specify the offset on which to act upon.

To replace a segment use the `HierarchicalPath::withSegment` and provide the offset of the segment to remove and
the value used to replace it. Similarly, to remove segments from the current object and returns a new instance
without, use the `HierarchicalPath::withoutSegments` method which expects a variadic argument which
list the offset to remove.

~~~php
<?php

use League\Uri\Components\HierarchicalPath;

HierarchicalPath::new('/foo/example/com')->withSegment(0, 'bar/baz')->value(); //return /bar/baz/example/com
HierarchicalPath::fromAbsolute('path','to', 'the', 'sky')->withoutSegment(0, 1)->value(); //return '/the/sky'
~~~

<p class="message-info">Just like the <code>HierarchicalPath::get</code> this method supports negative offset.</p>
<p class="message-notice">if the specified offset does not exist, no modification is performed and the current object is returned.</p>

### Removing empty segments

Sometimes your path may contain multiple adjacent delimiters. Since removing them may result in a semantically
different URI, this normalization can not be applied by default. To remove adjacent delimiters you can call
the `HierarchicalPath::withoutEmptySegments` method which convert you path as described below:

~~~php
<?php

use League\Uri\Components\HierarchicalPath;

$path = HierarchicalPath::new("path////to/the/sky//");
$newPath = $path->withoutEmptySegments();
echo $path;    //displays 'path////to/the/sky//'
echo $newPath; //displays 'path/to/the/sky/'
~~~

