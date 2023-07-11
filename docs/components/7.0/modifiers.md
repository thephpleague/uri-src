---
layout: default
title: URI partial modifiers
---

URI modifiers
=======

Sometimes you do not wish to change the full component of a URI, but you are interested
in updating part of one of its component. In order to do so in PHP the code can quickly
become a headache with a lot of edge case. For instance here's how you would update
the query string from a given URI object:

~~~php
<?php

use GuzzleHttp\Psr7\Uri;

$uriString = "http://www.example.com?fo.o=toto#~typo";
$queryToMerge = 'fo.o=bar&taz=';

$uri = new Uri($uriString);
parse_str($uri->getQuery(), $params);
parse_str($queryToMerge, $paramsToMerge);
$query = http_build_query(
    array_merge($params, $paramsToMerge),
    '',
    '&',
    PHP_QUERY_RFC3986
);

$newUri = $uri->withQuery($query);
echo $newUri; // display http://www.example.com?fo_o=bar&taz=#~typo
~~~

In contrast, using the provided `League\Uri\UriModifier::mergeQuery` modifier the code becomes

~~~php
<?php

use League\Uri\UriModifier;

$uriString = "http://www.example.com?fo.o=toto#~typo";
$queryToMerge = 'fo.o=bar&taz=';

echo UriModifier::mergeQuery($uriString, $queryToMerge);
echo $newUri; // display http://www.example.com?fo.o=bar&taz=#~typo
~~~

In addition to merging the query to the URI, `mergeQuery` has:

- enforced `RFC3986` encoding throughout the modifications;
- not mangle your data during merging;
- returned a valid URI object;

## Definition

Using the same logic the pacackage introduces a set of URI modifier as a method or
a function which provides a convenient mechanism for partially manipulating a URI.

The only **hard** requirement is on the type of the returned instance and accepted
parameters.

All modifiers first argument is the URI on which to operate on.

- if that URI is a PSR-7 implementing object, then the result will be of the same instance as the submitted class.
- Otherwise, a `League\Uri\Uri` instance will be returned.

Under the hood the `UriModifier` class uses the [URI components objects](/components/7.0/)
to apply changes to the submitted URI object.

## Query modifiers

The following modifiers update and normalize the URI query component.

<p class="message-notice">Because each modification is done after parsing and building, the 
resulting query string may update the component character encoding. These changes are expected because of 
the rules governing parsing and building query string.</p>

### UriModifier::sortQuery

Sorts the query according to its key values. The sorting rules are the same uses by WHATWG `URLSearchParams::sort` method.

~~~php
$uri = Http::new("http://example.com/?kingkong=toto&foo=bar%20baz&kingkong=ape");
$newUri = UriModifier::sortQuery($uri);

echo $uri->getQuery();    //display "kingkong=toto&foo=bar%20baz&kingkong=ape"
echo $newUri->getQuery(); //display "kingkong=toto&kingkong=ape&foo=bar%20baz"
~~~

### UriModifier::mergeQuery

Merges a submitted query string to the URI object to be modified. When merging two query strings with the same key value the submitted query string value takes precedence over the URI query string value.

~~~php
$uri = Http::new("http://example.com/test.php?kingkong=toto&foo=bar+baz#doc3");
$newUri = UriModifier::mergeQuery($uri, 'kingkong=godzilla&toto');

echo $uri->getQuery();    //display "kingkong=toto&foo=bar+baz"
echo $newUri->getQuery(); //display "kingkong=godzilla&foo=bar%20baz&toto"
~~~

### UriModifier::appendQuery

Appends a submitted query string to the URI object to be modified. When appending two query strings with the same key value the submitted query string value is added to the return query string without modifying the URI query string value.

~~~php
$uri = Http::new("http://example.com/test.php?kingkong=toto&foo=bar+baz#doc3");
$newUri = UriModifier::appendQuery($uri, 'kingkong=godzilla&toto');

echo $uri->getQuery();    //display "kingkong=toto&foo=bar+baz"
echo $newUri->getQuery(); //display "kingkong=toto&kingkong=godzilla&foo=bar%20baz&toto"
~~~

### UriModifier::removePairs

Removes query pairs from the current URI query string by providing the pairs key.

~~~php
$uri = Http::new("http://example.com/test.php?kingkong=toto&foo=bar+baz&bar=baz#doc3");
$newUri = UriModifier::removePairs($uri, 'foo', 'bar');

echo $uri->getQuery();    //display "kingkong=toto&foo=bar+baz&bar=baz"
echo $newUri->getQuery(); //display "kingkong=toto"
~~~

### UriModifier::removeParams

Removes query params from the current URI query string by providing the param name. The removal preserves mangled key params.

~~~php
$uri = Http::new("http://example.com/test.php?kingkong=toto&fo.o=bar&fo_o=bar");
$newUri = UriModifier::removeParams($uri, 'fo.o');

echo $uri->getQuery();    //display "kingkong=toto&fo.o=bar&fo_o=bar"
echo $newUri->getQuery(); //display "kingkong=toto&fo_o=bar"
~~~

## Host modifiers

The following modifiers update and normalize the URI host component according to RFC3986 or RFC3987.

### UriModifier::hostToAscii

Converts the host into its ascii representation according to RFC3986:

~~~php
<?php

use GuzzleHttp\Psr7\Uri;
use League\Uri\UriModifier;

$uri = new Uri("http://스타벅스코리아.com/to/the/sky/");
$newUri = UriModifier::hostToAscii($uri);

echo get_class($newUri); //display \GuzzleHttp\Psr7\Uri
echo $newUri; //display "http://xn--oy2b35ckwhba574atvuzkc.com/to/the/sky/"
~~~

<p class="message-warning">This method will have no effect on <strong>League URI objects</strong> as this conversion is done by default.</p>

### UriModifier::hostToUnicode

Converts the host into its idn representation according to RFC3986:

~~~php
<?php

use GuzzleHttp\Psr7\Uri;
use League\Uri\Modifiers\HostToUnicode;

$uriString = "http://xn--oy2b35ckwhba574atvuzkc.com/to/the/./sky/";
$uri = new Uri($uriString);
$newUri = UriModifier::hostToUnicode($uri);

echo get_class($newUri); //display \GuzzleHttp\Psr7\Uri
echo $newUri; //display "http://스타벅스코리아.com/to/the/sky/"
~~~

<p class="message-warning">This method will have no effect on
<strong>League URI objects</strong> because the object always transcode the host component
into its RFC3986/ascii representation.</p>

### UriModifier::removeZoneIdentifier

Removes the host zone identifier if present

~~~php
<?php

use Zend\Diactoros\Uri;
use League\Uri\Modifiers\RemoveZoneIdentifier;

$uri = new Uri('http://[fe80::1234%25eth0-1]/path/to/the/sky.php');
$newUri = UriModifier::removeZoneIdentifier($uri);
echo get_class($newUri); //display \Zend\Diactoros\Uri

echo $newUri; //display 'http://[fe80::1234]/path/to/the/sky.php'
~~~

### UriModifier::addRootLabel

Adds the root label if not present

~~~php
$newUri = UriModifier::addRootLabel('http://example.com:83');

echo $newUri; //display 'http://example.com.:83'
~~~

### UriModifier::removeRootLabel

Removes the root label if present

~~~php
$newUri = UriModifier::removeRootLabel('http://example.com.#yes');

echo $newUri; //display 'http://example.com#yes'
~~~

### UriModifier::appendLabel

Appends a host to the current URI host.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
$newUri = UriModifier::appendLabel($uri, 'fr');

echo $newUri; //display "http://www.example.com.fr/path/to/the/sky/"
~~~

### UriModifier::prependLabel

Prepends a host to the current URI path.

~~~php
echo UriModifier::prependLabel("http://www.example.com/path/to/the/sky/", 'shop'); //display "http://shop.www.example.com/path/to/the/sky/and/above"
~~~

### UriModifier::replaceLabel

Replaces a label from the current URI host with a host.

<p class="message-notice">Hosts are hierarchical components whose labels are indexed from right to left.</p>

~~~php
$newUri = UriModifier::replaceLabel("http://www.example.com/path/to/the/sky/", 2, 'admin.shop');

echo $newUri; //display"http://admin.shop.example.com/path/to/the/sky"
~~~

<p class="message-info">This modifier supports negative offset</p>

The previous example can be rewritten using negative offset:

~~~php
$newUri = UriModifier::replaceLabel("http://www.example.com/path/to/the/sky/", -1, 'admin.shop');

echo $newUri; //display"http://admin.shop.example.com/path/to/the/sky"
~~~

### UriModifier::removeLabels

Removes selected labels from the current URI host. Labels are indicated using an array containing the labels offsets.

<p class="message-notice">Hosts are hierarchical components whose labels are indexed from right to left.</p>

~~~php
$newUri = UriModifier::removeLabels("http://www.localhost.com/path/to/the/sky/", 2, 0);

echo $newUri; //display "http://localhost/path/the/sky/"
~~~

<p class="message-info">This modifier supports negative offset</p>

The previous example can be rewritten using negative offset:

~~~php
$newUri = UriModifier::removeLabels("http://www.example.com/path/to/the/sky/", -1, -3);

echo $newUri->toString(); //display "http://localhost/path/the/sky/"
~~~

## Path modifiers

<p class="message-notice">Because each modification is done after parsing and building, 
the resulting path may update the component character encoding. These changes are 
expected because of the rules governing parsing and building path string.</p>

### UriModifier::removeDotSegments

Removes dot segments according to RFC3986:

~~~php
$newUri = UriModifier::removeDotSegments("http://www.example.com/path/../to/the/./sky/");

echo $newUri; //display "http://www.example.com/to/the/sky/"
~~~

### UriModifier::removeEmptySegments

Removes adjacent separators with empty segment.

~~~php
$newUri = UriModifier::removeEmptySegments("http://www.example.com/path//to/the//sky/");

echo $newUri; //display "http://www.example.com/path/to/the/sky/"
~~~

### UriModifier::removeTrailingSlash

Removes the path trailing slash if present

~~~php
$newUri = UriModifier::removeTrailingSlash("http://www.example.com/path/?foo=bar");

echo $newUri; //display "http://www.example.com/path?foo=bar"
~~~

### UriModifier::addTrailingSlash

Adds the path trailing slash if not present

~~~php
$newUri = UriModifier::addTrailingSlash("http://www.example.com/sky#top");

echo $newUri; //display "http://www.example.com/sky/#top"
~~~

### UriModifier::removeLeadingSlash

Removes the path leading slash if present.

~~~php
$newUri = UriModifier::removeLeadingSlash("/path/to/the/sky/");

echo $newUri; //display "path/to/the/sky"
~~~

### UriModifier::addLeadingSlash

Adds the path leading slash if not present.

~~~php
$newUri = UriModifier::addLeadingSlash("path/to/the/sky/");

echo $newUri; //display "/path/to/the/sky"
~~~

### UriModifier::replaceDirname

Adds, updates and or removes the path dirname from the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky");
$newUri = UriModifier::replaceDirname($uri, '/road/to');

echo $uri->getPath();    //display "/path/to/the/sky"
echo $newUri->getPath(); //display "/road/to/sky"
~~~

### UriModifier::replaceBasename

Adds, updates and or removes the path basename from the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky");
$newUri = UriModifier::replaceBasename($uri, "paradise.xml");

echo $uri->getPath();    //display "/path/to/the/sky"
echo $newUri->getPath(); //display "/path/to/the/paradise.xml"
~~~

### UriModifier::replaceExtension

Adds, updates and or removes the path extension from the current URI path.

~~~php
$uri = Http::new("http://www.example.com/export.html");
$newUri = UriModifier::replaceExtension($uri, 'csv');

echo $uri->getPath();    //display "/export.html"
echo $newUri->getPath(); //display "/export.csv"
~~~

### UriModifier::addBasePath

Adds the basepath to the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky");
$newUri = UriModifier::addBasePath($uri, '/the/real');

echo $uri->getPath();    //display "/path/to/the/sky"
echo $newUri->getPath(); //display "/the/real/path/to/the/sky"
~~~

### UriModifier::removeBasePath

Removes the basepath from the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky");
$newUri = UriModifier::removeBasePath($uri, "/path/to/the");

echo $uri->getPath();    //display "/path/to/the/sky"
echo $newUri->getPath(); //display "/sky"
~~~

### UriModifier::appendSegment

Appends a path to the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
$newUri = UriModifier::appendSegment($uri, "and/above");

echo $uri->getPath();    //display "/path/to/the/sky"
echo $newUri->getPath(); //display "/path/to/the/sky/and/above"
~~~

### UriModifier::prependSegment

Prepends a path to the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
$newUri = UriModifier::prependSegment($uri, "and/above");

echo $uri->getPath();    //display "/path/to/the/sky"
echo $newUri->getPath(); //display "/and/above/path/to/the/sky/"
~~~

### UriModifier::replaceSegment

Replaces a segment from the current URI path with a new path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
$newUri = UriModifier::replaceSegment($uri, 3, "sea");

echo $uri->getPath();    //display "/path/to/the/sky/"
echo $newUri->getPath(); //display "/path/to/the/sea/"
~~~

<p class="message-info">This modifier supports negative offset</p>

The previous example can be rewritten using negative offset:

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
$newUri = UriModifier::replaceSegment($uri, -1, "sea");

echo $uri->getPath();    //display "/path/to/the/sky/"
echo $newUri->getPath(); //display "/path/to/the/sea/"
~~~

### UriModifier::removeSegments

Removes selected segments from the current URI path by providing the segments offset.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
$newUri = UriModifier::removeSegments($uri, 1, 3);

echo $uri->getPath();    //display "/path/to/the/sky/"
echo $newUri->getPath(); //display "/path/the/"
~~~

<p class="message-info">This modifier supports negative offset</p>

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
$newUri = UriModifier::removeSegments($uri, -1, -2]);

echo $uri->getPath();    //display "/path/to/the/sky/"
echo $newUri->getPath(); //display "/path/the/"
~~~

### UriModifier::replaceDataUriParameters

Update Data URI parameters

~~~php
$uri = DataUri::new("data:text/plain;charset=US-ASCII,Hello%20World!");
$newUri = UriModifier::replaceDataUriParameters($uri, "charset=utf-8");

echo $uri->getPath();    //display "text/plain;charset=US-ASCII,Hello%20World!"
echo $newUri->getPath(); //display "text/plain;charset=utf-8,Hello%20World!"
~~~

### UriModifier::dataPathToBinary

Converts a data URI path from text to its base64 encoded version

~~~php
$uri = DataUri::new("data:text/plain;charset=US-ASCII,Hello%20World!");
$newUri = UriModifier::dataPathToBinary($uri);

echo $uri->getPath();    //display "text/plain;charset=US-ASCII,Hello%20World!"
echo $newUri->getPath(); //display "text/plain;charset=US-ASCII;base64,SGVsbG8gV29ybGQh"

~~~

### UriModifier::dataPathToAscii

Converts a data URI path from text to its base64 encoded version

~~~php
$uri = DataUri::new("data:text/plain;charset=US-ASCII;base64,SGVsbG8gV29ybGQh");
$newUri = UriModifier::dataPathToAscii($uri);

echo $uri->getPath();    //display "text/plain;charset=US-ASCII;base64,SGVsbG8gV29ybGQh"
echo $newUri->getPath(); //display "text/plain;charset=US-ASCII,Hello%20World!"
~~~
