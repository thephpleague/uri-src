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

In contrast, using the provided `League\Uri\Modifier::mergeQuery` modifier the code becomes

~~~php
<?php

use League\Uri\Modifier;

$uriString = "http://www.example.com?fo.o=toto#~typo";
$queryToMerge = 'fo.o=bar&taz=';

echo Modifier::from($uriString)->mergeQuery($queryToMerge);
// display http://www.example.com?fo.o=bar&taz=#~typo
~~~

In addition to merging the query to the URI, `mergeQuery` has:

- enforced `RFC3986` encoding throughout the modifications;
- not mangle your data during merging;
- returned a valid URI object;

Because the `Modifier` is immutable and the modifying methods return a new instance of the class,
out of the box, piping multiple methods to improve your code is supported.

~~~php
<?php

use League\Uri\Modifier;
use GuzzleHttp\Psr7\Uri as GuzzleUri;

$uri = Modifier::from(new GuzzleUri('http://bébé.be'))
    ->appendSegment('toto')
    ->addRootLabel()
    ->prependLabel('shop')
    ->hostToUnicode()
    ->appendQuery('foo=toto&foo=tata')
    ->getUri();
echo $uri::class;   // returns GuzzleHttp\Psr7\Uri
echo $uri, PHP_EOL; // returns http://shop.bébé.be./toto?foo=toto&foo=tata
~~~

<p class="message-warning">While the class does manipulate URI it does not implement any URI related interface.</p>
<p class="message-notice">If a PSR-7 or a League <code>UriInterface</code> implementing instance is given
then the return value will also be a PSR-7 <code>UriInterface</code> implementing instance.</p>
<p class="message-notice"><code>getIdnUriString</code> was added in version <code>7.5.0</code>.</p>

The `Modifier::getUri` method returns either a `PSR-7` or a League URI `UriInterface`, conversely,
the `Modifier::getUriString` method returns the RFC3986 string representation for the URI and
the `Modifier::getIdnUriString` method returns the RFC3986 string representation for the URI
with a Internationalized Domain Name (IDNA) if applicable. Last but not least, the class
implements the `Stringable` and the `JsonSerializable` interface to improve developer experience.

Under the hood the `Modifier` class intensively uses the [URI components objects](/components/7.0/)
to apply changes to the submitted URI object.

## Query modifiers

The following modifiers update and normalize the URI query component.

<p class="message-notice">Because each modification is done after parsing and building, the 
resulting query string may update the component character encoding. These changes are expected because of 
the rules governing parsing and building query string.</p>

### Modifier::encodeQuery

<p class="message-notice">since version <code>7.1.0</code></p>

Change the encoding of the query string. You can either specify one of PHP's constant between `PHP_QUERY_RFC1738` and
`PHP_QUERY_RFC3986`

~~~php
use League\Uri\Modifier;

echo Modifier::from("https://example.com/?kingkong=toto&foo=bar%20baz&kingkong=ape")
    ->encodeQuery(PHP_QUERY_RFC1738)
    ->getUri()
    ->getQuery(); 
//display "kingkong=toto&kingkong=ape&foo=bar+baz"
~~~

or for more specific conversions you can provide a `League\Uri\KeyValuePair\Converter` class.

~~~php
use League\Uri\KeyValuePair\Converter as KeyValuePairConverter;
use League\Uri\Modifier;
use Nyholm\Psr7\Uri;

$converter = KeyValuePairConverter::new(';')
    ->withEncodingMap([
        '%3F' => '?',
        '%2F' => '/',
        '%40' => '@',
        '%3A' => ':',
    ]);
    
Modifier::from(new Uri('https://example.com?foo[2]=bar#fragment'))
    ->appendQuery('url=https://example.com?foo[2]=bar#fragment')
    ->encodeQuery($converter)
    ->getUri()
    ->getQuery();
//display "foo%5B2%5D=bar;url=https://example.com?foo%5B2%5D%3Dbar%23fragment"
~~~

### Modifier::sortQuery

Sorts the query according to its key values. The sorting rules are the same uses by WHATWG `URLSearchParams::sort` method.

~~~php
use League\Uri\Modifier;

echo Modifier::from("http://example.com/?kingkong=toto&foo=bar%20baz&kingkong=ape")
    ->sortQuery($uri)
    ->getUri()
    ->getQuery(); 
//display "kingkong=toto&kingkong=ape&foo=bar%20baz"
~~~

### Modifier::mergeQuery

Merges a submitted query string to the URI object to be modified. When merging two query strings with the same key value the submitted query string value takes precedence over the URI query string value.

~~~php
$uri = Http::new("http://example.com/test.php?kingkong=toto&foo=bar+baz#doc3");
echo Modifier::from($uri)
    ->mergeQuery('kingkong=godzilla&toto')
    ->getUri()
    ->getQuery();
//display "kingkong=godzilla&foo=bar%20baz&toto"
~~~

### Modifier::appendQuery

Appends a submitted query string to the URI object to be modified. When appending two query strings with the same key value the submitted query string value is added to the return query string without modifying the URI query string value.

~~~php
Http::new("http://example.com/test.php?kingkong=toto&foo=bar+baz#doc3")
echo Modifier::from($uri)
    ->appendQuery('kingkong=godzilla&toto')
    ->getUri()
    ->getQuery();
//display "kingkong=toto&kingkong=godzilla&foo=bar%20baz&toto"
~~~

### Modifier::appendQueryPairs

Appends a query pairs to the URI object to be modified. When appending two query strings with
the same key value the submitted query string value is added to the return query
string without modifying the URI query string value.

~~~php
Http::new("http://example.com/test.php?kingkong=toto&foo=bar+baz#doc3")
echo Modifier::from($uri)
    ->appendQueryPairs([['kingkong', 'godzilla'], ['toto', null]])
    ->getUri()
    ->getQuery();
//display "kingkong=toto&kingkong=godzilla&foo=bar%20baz&toto"
~~~

### Modifier::appendQueryParameters

Appends a query PHP parameters to the URI object to be modified. When appending two query strings
with the same key value the submitted query string value is added to the return query
string without modifying the URI query string value.

~~~php
Http::new("http://example.com/test.php?kingkong=toto&foo=bar+baz#doc3")
echo Modifier::from($uri)
    ->appendQueryParameters(['kingkong' => 'godzilla', 'toto' => ''])
    ->getUri()
    ->getQuery();
//display "kingkong=toto&kingkong=godzilla&foo=bar%20baz&toto="
~~~

### Modifier::removeQueryPairs

Removes query pairs from the current URI query string by providing the pairs key.

~~~php
$uri = "http://example.com/test.php?kingkong=toto&foo=bar+baz&bar=baz#doc3";
$modifier = Modifier::from($uri);
$newUri = $modifier->removeQueryPairs('foo', 'bar')->getUri();

echo $modifier->getUri()->getQuery(); //display "kingkong=toto&foo=bar+baz&bar=baz"
echo $newUri->getUri()->getQuery();   //display "kingkong=toto"
~~~

### Modifier::removeQueryParameters

<p class="message-notice">since version <code>7.2.0</code></p>

Removes query params from the current URI query string by providing the param name. The removal preserves mangled key params.

~~~php
$uri = "http://example.com/test.php?kingkong=toto&fo.o=bar&fo_o=bar";
$modifier = Modifier::from($uri);
$newUri = $modifier->removeQueryParameters('fo.o');

echo $modifier->getUri()->getQuery(); //display "kingkong=toto&fo.o=bar&fo_o=bar
echo $newUri->getUri()->getQuery();   //display "kingkong=toto&fo_o=bar"
~~~


### Modifier::removeQueryParameterIndices

<p class="message-notice">since version <code>7.2.0</code></p>

Removes query params numeric indices from the current URI query string. The removal preserves mangled key params.

~~~php
$uri = "http://example.com/test.php?kingkong[1]=toto&fkingkong[2]=toto";
$modifier = Modifier::from($uri);
$newUri = $modifier->removeQueryParameterIndices();

echo $modifier->getUri()->getQuery(); //display "kingkong%5B1%5D=toto&fkingkong%5B2%5D=toto"
echo $newUri->getUri()->getQuery();   //display "kingkong%5B%5D=toto&fkingkong%5B%5D=toto"
~~~

### Modifier::mergeQueryParameters

<p class="message-notice">since version <code>7.2.0</code></p>

Merge PHP query parameters with the current URI query string by providing the parameters. The addition preserves mangled key params.

~~~php
$uri = "http://example.com/test.php?kingkong=toto&fo.o=bar&fo_o=bar";
$newUri = Modifier::from($uri)->:mergeQueryParameters(['toto' => 'baz']);

echo $newUri->getUri()->getQuery(); //display "kingkong=tot&fo.o=bar&fo_o=bar&toto=baz"
~~~

### Modifier::mergeQueryPairs

<p class="message-notice">since version <code>7.2.0</code></p>

Merge query paurs with the current URI query string by providing the pairs.

~~~php
$uri = "http://example.com/test.php?kingkong=toto&fo.o=bar&fo_o=bar";
$newUri = Modifier::from($uri)->:mergeQueryPairs([['fo.o', 'champion']]);

echo $newUri->getUri()->getQuery(); //display "kingkong=toto&fo.o=champion&fo_o=bar"
~~~

## Host modifiers

The following modifiers update and normalize the URI host component according to RFC3986 or RFC3987.

### Modifier::hostToAscii

Converts the host into its ascii representation according to RFC3986:

~~~php
<?php

use GuzzleHttp\Psr7\Uri;
use League\Uri\Modifier;

$uri = new Uri("http://스타벅스코리아.com/to/the/sky/");
$newUri = Modifier::from($uri)->hostToAscii()->getUri();

echo get_class($newUri); //display \GuzzleHttp\Psr7\Uri
echo $newUri; //display "http://xn--oy2b35ckwhba574atvuzkc.com/to/the/sky/"
~~~

<p class="message-warning">This method will have no effect on <strong>League URI objects</strong> as this conversion is done by default.</p>

### Modifier::hostToUnicode

Converts the host into its idn representation according to RFC3986:

~~~php
<?php

use GuzzleHttp\Psr7\Uri;
use League\Uri\Modifiers\HostToUnicode;

$uriString = "http://xn--oy2b35ckwhba574atvuzkc.com/to/the/./sky/";
$uri = new Uri($uriString);
$newUri = Modifier::from($uri)->hostToUnicode();

echo get_class($newUri); //display \GuzzleHttp\Psr7\Uri
echo $newUri; //display "http://스타벅스코리아.com/to/the/sky/"
~~~

<p class="message-warning">This method will have no effect on
<strong>League URI objects</strong> because the object always transcode the host component
into its RFC3986/ascii representation.</p>

### Modifier::hostToDecimal

Normalizes the URI host content to a IPv4 dot-decimal notation if possible
otherwise returns the uri instance unchanged. See the [IPv4 Converter documentation](/components/7.0/ipv4/)
page for more information.

~~~php
<?php

use League\Uri\Modifier;

$uri = 'http://0300.0250.0000.0001/path/to/the/sky.php';
echo Modifier::from($uri)->hostToDecimal()->getUri();
//display 'http://192.168.0.1/path/to/the/sky.php'
~~~

### Modifier::hostToOctal

Normalizes the URI host content to a IPv4 dot-octal notation if possible
otherwise returns the uri instance unchanged. See the [IPv4 Converter documentation](/components/7.0/ipv4/)
page for more information.

~~~php
<?php

use League\Uri\Modifier;

$uri = 'http://192.168.0.1/path/to/the/sky.php';
echo Modifier::from($uri)->hostToOctal()->getUri();
//display 'http://0300.0250.0000.0001/path/to/the/sky.php'
~~~

### Modifier::hostToHexadecimal

Normalizes the URI host content to a IPv4 hexadecimal notation if possible
otherwise returns the uri instance unchanged. See the [IPv4 Converter documentation](/components/7.0/ipv4/)
page for more information.

~~~php
<?php

use League\Uri\Modifier;

$uri = 'http://192.168.257/path/to/the/sky.php';
echo Modifier::from($uri)->hostToOctal()->getUri();
//display 'http://0xc0a811/path/to/the/sky.php'
~~~

### Modifier::removeZoneIdentifier

Removes the host zone identifier if present

~~~php
<?php

use Zend\Diactoros\Uri;
use League\Uri\Modifier;

$uri = new Uri('http://[fe80::1234%25eth0-1]/path/to/the/sky.php');
$newUri = Modifier::from($uri)->removeZoneIdentifier()->getUri();
echo get_class($newUri); //display \Zend\Diactoros\Uri

echo $newUri; //display 'http://[fe80::1234]/path/to/the/sky.php'
~~~

### Modifier::addRootLabel

Adds the root label if not present

~~~php
use League\Uri\Modifier;

echo Modifier::from('http://example.com:83')->addRootLabel(); //display 'http://example.com.:83'
~~~

### Modifier::removeRootLabel

Removes the root label if present

~~~php
use League\Uri\Modifier;

echo  Modifier::from('http://example.com.#yes')->removeRootLabel();  //display 'http://example.com#yes'
~~~

### Modifier::appendLabel

Appends a host to the current URI host.

~~~php
use League\Uri\Modifier;

echo Modifier::from("http://www.example.com/path/to/the/sky/")->appendLabel('fr'); 
//display "http://www.example.com.fr/path/to/the/sky/"
~~~

### Modifier::prependLabel

Prepends a host to the current URI path.

~~~php
use League\Uri\Modifier;

echo Modifier::from("http://www.example.com/path/to/the/sky/")->prependLabel('shop'); //display "http://shop.www.example.com/path/to/the/sky/and/above"
~~~

### Modifier::replaceLabel

Replaces a label from the current URI host with a host.

<p class="message-notice">Hosts are hierarchical components whose labels are indexed from right to left.</p>

~~~php
use League\Uri\Modifier;

$uri = "http://www.example.com/path/to/the/sky/";
echo Modifier::from($uri)->replaceLabel(2, 'admin.shop');
//display"http://admin.shop.example.com/path/to/the/sky"
~~~

<p class="message-info">This modifier supports negative offset</p>

The previous example can be rewritten using negative offset:

~~~php
use League\Uri\Modifier;

$uri = "http://www.example.com/path/to/the/sky/";
echo Modifier::from($uri)->replaceLabel(-1, 'admin.shop');
//display"http://admin.shop.example.com/path/to/the/sky"
~~~

### Modifier::removeLabels

Removes selected labels from the current URI host. Labels are indicated string variadic labels offsets.

<p class="message-notice">Hosts are hierarchical components whose labels are indexed from right to left.</p>

~~~php

$uri = "http://www.localhost.com/path/to/the/sky/";
echo Modifier::from($uri)->removeLabels(2, 0);
//display "http://localhost/path/the/sky/"
~~~

<p class="message-info">This modifier supports negative offset</p>

The previous example can be rewritten using negative offset:

~~~php
$uri = "http://www.example.com/path/to/the/sky/";
Modifier::from($uri)->removeLabels(-1, -3)->getUriString();
//return "http://localhost/path/the/sky/"
~~~

### Modifier::sliceLabels

Slice the host from the current URI host. Negative offset are also supported.

<p class="message-notice">Hosts are hierarchical components whose labels are indexed from right to left.</p>

~~~php
$uri = "http://www.localhost.com/path/to/the/sky/";
echo Modifier::from($uri)->sliceLabels(1, 1)->getUriString();
//display "http://localhost/path/the/sky/"
~~~

<p class="message-info">This modifier supports negative offset</p>


## Path modifiers

<p class="message-notice">Because each modification is done after parsing and building, 
the resulting path may update the component character encoding. These changes are 
expected because of the rules governing parsing and building path string.</p>

### Modifier::removeDotSegments

Removes dot segments according to RFC3986:

~~~php
$uri = "http://www.example.com/path/../to/the/./sky/";
echo Modifier::from($uri)->removeDotSegments();
//display "http://www.example.com/to/the/sky/"
~~~

### Modifier::removeEmptySegments

Removes adjacent separators with empty segment.

~~~php
$uri = "http://www.example.com/path//to/the//sky/";
echo Modifier::from($uri)->removeEmptySegments();
//display "http://www.example.com/path/to/the/sky/"
~~~

### Modifier::removeTrailingSlash

Removes the path trailing slash if present

~~~php
$uri = Uri::new("http://www.example.com/path/?foo=bar");
echo Modifier::from($uri)->removeTrailingSlash();
//display "http://www.example.com/path?foo=bar"
~~~

### Modifier::addTrailingSlash

Adds the path trailing slash if not present

~~~php
$uri = "http://www.example.com/sky#top";
echo Modifier::from($uri)->addTrailingSlash();
//display "http://www.example.com/sky/#top"
~~~

### Modifier::removeLeadingSlash

Removes the path leading slash if present.

~~~php
$uri = "/path/to/the/sky/";
echo Modifier::from($uri)->removeLeadingSlash();
//display "path/to/the/sky"
~~~

### Modifier::addLeadingSlash

Adds the path leading slash if not present.

~~~php
echo Modifier::from("path/to/the/sky/")->addLeadingSlash();
//display "/path/to/the/sky"
~~~

### Modifier::replaceDirname

Adds, updates and or removes the path dirname from the current URI path.

~~~php
echo Modifier::from("http://www.example.com/path/to/the/sky")
    ->replaceDirname('/road/to')
    ->getUri()
    ->getPath(); //display "/road/to/sky"
~~~

### Modifier::replaceBasename

Adds, updates and or removes the path basename from the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky");
echo Modifier::from($uri)
    ->replaceBasename("paradise.xml")
    ->getUri()
    ->getPath();
     //display "/path/to/the/paradise.xml"
~~~

### Modifier::replaceExtension

Adds, updates and or removes the path extension from the current URI path.

~~~php
$uri = Http::new("http://www.example.com/export.html");
echo Modifier::from($uri)->replaceExtension('csv')->getUri()->getPath();
//display "/export.csv"
~~~

### Modifier::addBasePath

Adds the basepath to the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky");
echo Modifier::from($uri)
    ->addBasePath('/the/real')
    ->getUri()
    ->getPath();
//display "/the/real/path/to/the/sky"
~~~

### Modifier::removeBasePath

Removes the basepath from the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky");
echo Modifier::from($uri)
    ->removeBasePath("/path/to/the")
    ->getUri()
    ->getPath();
//display "/sky"
~~~

### Modifier::appendSegment

Appends a path to the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
echo Modifier::from($uri)
    ->appendSegment("and/above")
    ->getUri()
    ->getPath();
 //display "/path/to/the/sky/and/above"
~~~

### Modifier::prependSegment

Prepends a path to the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
echo Modifier::from($uri)
    ->prependSegment("and/above")
    ->getUri()
    ->getPath();
 //display "/and/above/path/to/the/sky/"
~~~

### Modifier::replaceSegment

Replaces a segment from the current URI path with a new path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
echo Modifier::from($uri)
    ->replaceSegment(3, "sea")
    ->getUri()
    ->getPath();
 //display "/path/to/the/sea/"
~~~

<p class="message-info">This modifier supports negative offset</p>

The previous example can be rewritten using negative offset:

~~~php
echo Modifier::from("http://www.example.com/path/to/the/sky/")
    ->replaceSegment(-1, "sea")
    ->getPath();
//display "/path/to/the/sea/"
~~~

### Modifier::removeSegments

Removes selected segments from the current URI path by providing the segments offset.

~~~php
echo Modifier::from("http://www.example.com/path/to/the/sky/")
    ->removeSegments(1, 3)
    ->getUri()
    ->getPath();
//display "/path/the/"
~~~

<p class="message-info">This modifier supports negative offset</p>

~~~php
echo Modifier::from("http://www.example.com/path/to/the/sky/")
    ->removeSegments(-1, -2])
    ->getUri()
    ->getPath();
//display "/path/the/"
~~~

### Modifier::sliceSegments

Slice the path from the current URI path. Negative offset are also supported.

~~~php
$uri = "http://www.localhost.com/path/to/the/sky/";
echo Modifier::from($uri)->sliceSegments(2, 2)->getUriString();
//display "http://www.localhost.com/the/sky/"
~~~

### Modifier::replaceDataUriParameters

Update Data URI parameters

~~~php
$uri = Uri::new("data:text/plain;charset=US-ASCII,Hello%20World!");
echo Modifier::from($uri)
    ->replaceDataUriParameters("charset=utf-8")
    ->getUri()
    ->getPath();
//display "text/plain;charset=utf-8,Hello%20World!"
~~~

### Modifier::dataPathToBinary

Converts a data URI path from text to its base64 encoded version

~~~php
$uri = Uri::new("data:text/plain;charset=US-ASCII,Hello%20World!");
echo Modifier::from($uri)
    ->dataPathToBinary()
    ->getUri()
    ->getPath();
//display "text/plain;charset=US-ASCII;base64,SGVsbG8gV29ybGQh"
~~~

### Modifier::dataPathToAscii

Converts a data URI path from text to its base64 encoded version

~~~php
$uri = Uri::new("data:text/plain;charset=US-ASCII;base64,SGVsbG8gV29ybGQh");
echo Modifier::from($uri)
    ->dataPathToAscii()
    ->getUri()
    ->getPath();
//display "text/plain;charset=US-ASCII,Hello%20World!"
~~~
