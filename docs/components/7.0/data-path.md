---
layout: default
title: The Data Uri Path component
---

# Data URI Path

The `DataPath` class represents a data path component. Apart from the [package common API](/components/7.0/)
and the [path common API](/components/7.0/path), the class exposes specific properties and methods to 
work with Data URI paths.

## Instantiation

~~~php
<?php
public static function DataPath::fromFileContents(Stringable|string $path): self
~~~

Since data URI represents files content you can also instantiate a new data URI object from a file path
using the `fromFileContents` named constructor. If the file or the submitted string is invalid,
not readable or accessible a `League\Uri\Contracts\UriException` exception will be thrown.

The class uses PHP's `finfo` class to detect the required mediatype as defined in `RFC2045`.

<p class="message-notice">submitted string is normalized to be <code>RFC3986</code> compliant.</p>

~~~php
<?php

use League\Uri\Components\DataPath;

DataPath::new()->value(); //returns 'text/plain;charset=us-ascii,'

echo DataPath::fromFileContents('path/to/my/png/image.png');
//displays 'image/png;charset=binary;base64,...'
//where '...' represent the base64 representation of the file
~~~

## Accessing the path properties

The DataPath class exposes the following specific methods:

- `getMediaType`: Returns the Data URI current mediatype;
- `getMimeType`: Returns the Data URI current mimetype;
- `getParameters`: Returns the parameters associated with the mediatype;
- `getData`: Returns the encoded data contained is the Data URI;
- `isBinaryData`: Tells whether the data URI represents some binary data

Each of these methods return a string. This string can be empty if the data where no supplied when constructing the URI.

~~~php
$path = DataPath::new('text/plain;charset=us-ascii,Hello%20World%21');
echo $path->getMediaType();  //returns 'text/plain;charset=us-ascii'
echo $path->getMimeType();   //returns 'text/plain'
echo $path->getParameters(); //returns 'charset=us-ascii'
echo $path->getData();       //returns 'Hello%20World%21'
$path->isBinaryData();       //returns false

$binaryPath = DataPath::fromFileContents('path/to/my/png/image.png');
$binaryPath->isBinaryData(); //returns true
~~~

## Modifying the path properties

### Update the Data URI parameters

Since we are dealing with a data and not just a URI, the only property that can be modified are its optional parameters.

To set new parameters you should use the `withParameters` method:

~~~php
$path = DataPath::new('text/plain;charset=us-ascii,Hello%20World%21');
$newPath = $path->withParameters('charset=utf-8');
echo $newPath; //returns 'text/plain;charset=utf-8,Hello%20World%21'
~~~

<p class="message-notice">Of note the data should be urlencoded if needed.</p>

### Transcode the data between its binary and ascii representation

Another manipulation is to transcode the data from ASCII to is base64 encoded (or binary) version. If no conversion is possible the former object is returned otherwise a new valid data uri object is created.

~~~php
$path = DataPath::new('text/plain;charset=us-ascii,Hello%20World%21');
$path->isBinaryData(); // return false;
$newPath = $path->toBinary();
echo $newPath; // display 'text/plain;charset=us-ascii;base64,SGVsbG8gV29ybGQh'
$newPath->isBinaryData(); // return true;
$newPath->toAscii()->toString() === $path->toString(); // return true;
~~~

## Saving the data path

Since the path can be interpreted as a file, it is possible to save it to a specified path using the dedicated `save` method. This method accepts two parameters:

- the file path;
- the open mode (à la PHP `fopen`);

By default, the open mode is set to `w`. If for any reason the file is not accessible a `RuntimeException` will be thrown.

The method returns the `SplFileObject` object used to save the data-uri data for further analysis/manipulation if you want.

~~~php
$path = DataPath::fromFileContents('path/to/my/file.png');
$file = $path->save('path/where/to/save/my/image.png');
//$file is a SplFileObject which point to the newly created file;
~~~
