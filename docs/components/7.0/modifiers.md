---
layout: default
title: URI partial modifiers
description: The Modifier enables modifying URI objects in a compliant and standard way.
---

Universal URI Modifier
=======

## Universal Modifier

### Why does it exist?

Most of the time, you only want to change or modify a specific portion of a URI.
In PHP, performing such partial updates can quickly become complex and error-prone.
For example, the following illustrates how to update the query string of an
existing URI object:

~~~php
$uriString = "http://www.example.com?fo.o=toto#~typo";
$queryToMerge = 'fo.o=bar&taz=';
$uri = new GuzzleHttp\Psr7\Uri($uriString);

parse_str($uri->getQuery(), $params);
$query = http_build_query(
    data: array_merge($params, $paramsToMerge),
    encoding_type: PHP_QUERY_RFC3986,
);
$newUri = $uri->withQuery($query);

echo $newUri::class; // display GuzzleHttp\Psr7\Uri
echo $newUri; // display http://www.example.com?fo_o=bar&taz=#~typo
~~~

In contrast, using the provided `League\Uri\Modifier::mergeQuery` modifier the code becomes

~~~php
$uriString = "http://www.example.com?fo.o=toto#~typo";
$queryToMerge = 'fo.o=bar&taz=';
$uri = new GuzzleHttp\Psr7\Uri($uriString);

$newUri = League\Uri\Modifier::wrap($uri)
    ->mergeQuery($queryToMerge)
    ->unwrap();

echo $newUri::class; // display GuzzleHttp\Psr7\Uri
echo $newUri; // display http://www.example.com?fo.o=bar&taz=#~typo
~~~

In addition to merging the query, `mergeQuery` has:

- enforced standard compliant encoding throughout the modifications;
- not mangle your data during merging;
- returned a valid URI object;

### Supported URI Objects

The `Modifier::wrap` method is the entry point from which the submitted URI will be processed.
The method accepts:

- `League\Uri\Uri` objects
- `PSR-7` UriInterface implementing objects
- PHP native URI objects
- any `Stringable` object
- a `string`

<p class="message-notice"><code>Modifier::from</code> is deprecated since version <code>7.6.0</code></p>
<p class="message-warning">While the class does manipulate URI, it does not implement any URI related getter methods.</p>

The `Modifier::unwrap` returns a URI object, but its type depends on the value passed to `Modifier::wrap`.

By default, it returns a League URI `Uri` instance unless you used
a supported URI object with the `Modifier::wrap` method, in which case,
an instance of that same URI class is returned.

```php
use GuzzleHttp\Psr7\Utils;
use League\Uri\Modifier;
use Uri\Rfc3986\Uri;

Modifier::wrap('https://example,com')->unwrap(); // returns a League\Uri\Uri object
Modifier::wrap(Uri::parse('https://example,com'))->unwrap(); //returns a Uri\Rfc3986\Uri object
Modifier::wrap(Utils::uriFor('https://example,com'))->unwrap(); // returns a GuzzleHttp\Psr7\Uri object
```

This ensures that throughout all modifications after using `unwrap` the developer gets a URI object
with the same instance as the one that was provided to the `wrap` method.

### String Representations

If you are not interested in the returned URI but only on its underlying string representation, you can instead use
the following methods:

- `Modifier::toString` : returns the URI object underlying string representation. 
- `Modifier::toDisplayString` : returns an RFC3987 like string representation (this **MAY** not be a valid URL as all the components are decoded)
- `Modifier::toMarkdownAnchor` : returns a Markdown representation of the URI, you can optionally set the anchor text.
- `Modifier::toHtmlAnchor` : returns an HTML anchor tag `<a>` with its `href` pre-filled; you can optionally set the anchor text and attributes.

The result of `Modifier::toString` is the representation used by the `Stringable` and the `JsonSerializable` interface to improve interoperability.

```php
use League\Uri\Modifier;
use Uri\Whatwg\Url;

$uri = Modifier::wrap(new Url('https://bébé.be?foo[]=bar'))->prependLabel('shop');
$uri->unwrap()::class;        // returns 'GuzzleHttp\Psr7\Uri'
$uri->unwrap()->__toString(); // returns 'https://shop.bébé.be?foo%5B%5D=bar'
$uri->toString();             // returns 'https://shop.bébé.be?foo%5B%5D=bar'
$uri->toDisplayString();      // returns 'https://shop.bébé.be?foo[]=bar'
$uri->toMarkdownAnchor('My Shop'); // returns [My Shop](https://shop.bébé.be?foo%5B%5D=bar)
$uri->toHtmlAnchor('My Shop', ['class' => ['text-center', 'text-6xl']]);
// returns <a href="https://shop.b%C3%A9b%C3%A9.be?foo%5B%5D=bar" class="text-center text-6xl">My Shop</a>
```

<p class="message-info">The <code>toDisplayString()</code>, <code>toMarkdownAnchor()</code> and <code>toHtmlAnchor()</code> methods are available since version <code>7.6.0</code>.</p>
<p class="message-notice">The <code>getUri()</code>, <code>getUriString()</code> and <code>getIdnUriString()</code> methods are deprecated since version <code>7.6.0</code>.</p>
<p class="message-notice">The static method <code>from()</code> is deprecated since version <code>7.6.0</code> and replaced by the static method <code>wrap()</code>.</p>

### Chaining Modifications

The `Modifier` class is immutable, and each modification method returns
a new `Modifier` instance. This means you can seamlessly chain multiple
calls together, resulting in cleaner and more expressive code.

~~~php
<?php

use League\Uri\Components\FragmentDirectives\TextDirective;
use League\Uri\Modifier;
use Uri\WhatWg\Url;

$uri = Modifier::wrap(new Url('https://www.mypoems.net/the-book-of-mwana-kupona/'))
    ->prependSegment('epic')
    ->removeLabels(-1)
    ->replaceLabel(1, "africanpoems")
    ->appendFragmentDirectives(new TextDirective(start: "Negema wangu binti", end: "neno lema kukwambia."))
    ->unwrap();

echo $uri::class, PHP_EOL; // displays Uri\WhatWg\Url
echo $uri->toAsciiString(), PHP_EOL;
// displays "https://africanpoems.net/epic/the-book-of-mwana-kupona/#:~:text=Negema%20wangu%20binti,neno%20lema%20kukwambia."
~~~

### Context Aware Changes

The `Modifier` class will perform some sanity checks and/or formatting before
handing over the changed value to the underlying object to perform the action.
Unless the input data is invalid, the exception thrown will be the ones from the
underlying URI object.

Here's an example:

```php
use Uri\Rfc3986\Uri;

echo new Uri('http://example.com/foo/bar')
    ->withHost('bébé.be') 
    ->toString(), PHP_EOL;
// this will throw because
// the host contains unsupported characters
// according to RFC3986
```

In contrast, when using the `Modifier` the result is different:

```php
use League\Uri\Modifier;
use Uri\Rfc3986\Uri;

echo Modifier::wrap(new Uri('http://example.com/foo/bar'))
    ->withHost('bébé.be')
    ->unwrap()
    ->toString(), PHP_EOL;
// the `unwrap()` method returns a valid Uri\Rfc3986\Uri instance 
// its `toString()` method returns http://xn--bb-bjab.be/foo/bar
// the modifier has converted the host into its ascii representation
// to avoid the exception to be thrown.
```

### URI Resolution

<p class="message-notice">Available since version <code>7.6.0</code></p>

The `Modifier` can normalize or resolve URI. Resolving and Normalizing URI is 
supported by `League\Uri\Uri`, and PHP's native URI object **but** not by PSR-7
`UriInterface` and the public API diverge. Using the `Modifier` class you get
a single method for all the URI objects and add the missing support for any
PSR-7 implementing class.

```php
use Uri\Whatwg\Url;

echo new Url('http://example.com/foo/bar')
    ->resolve('./../bar')
    ->toAsciiString(), PHP_EOL;
// returns http://example.com/bar
// there is no equivalent in PSR-7
```
using the `Modifier` you can do the following:

```php
use League\Uri\Modifier;
use GuzzleHttp\Psr7\Utils;

echo Modifier::wrap(Utils::uriFor('http://example.com/foo/bar'))
    ->resolve('./../bar')
    ->unwrap()
    ->__toString(), PHP_EOL;
// `uri()` returns a Guzzle PSR-7 URI object
// returns "http://example.com/bar"
```

### URI Normalization

<p class="message-notice">Available since version <code>7.6.0</code></p>

RFC3986 and WHATWG URL specification both allow normalizing URIs. But both
specifications do it in a different way and capacity. Normalization is
mandatory when using a WHATWG URL but optional with RFC3986. It is
not covered in PSR-7. Which again may leave the developer in a challenging
situation. To ease developer experience, the `Modifier` class exposes a
new `normalize` method which guarantee normalization regardless of the
underlying URI object.

```php
use League\Uri\Url;
use Uri\Rfc3986\Uri as Rfc3986Uri;
use Uri\Whatwg\Url as WhatwgUrl;

$uriString = 'HttP://ExamPle.com/./../foo/bar';
echo (new WhatwgUrl($uriString))->toAsciiString(), PHP_EOL;
echo (new Rfc3986Uri($uriString))->toString(), PHP_EOL;
echo Uri::new($uriString)->normalize()->toString(), PHP_EOL;
// returns 'http://example.com/foo/bar
// PSR-7 UriInterface does not have a method for that
```

Again using the `Modifier` class you will get a unified and predicable returned URI

```php
use League\Uri\Modifier;
use GuzzleHttp\Psr7\Utils;

$uriString = 'HttP://ExamPle.com/./../foo/bar';
echo Modifier::wrap(Utils::uriFor($uriString))
    ->normalize()
    ->unwrap() 
    ->__toString();
// returns 'http://example.com/foo/bar
```

## Available Modifiers

Under the hood the `Modifier` class intensively uses the [URI components objects](/components/7.0/)
to apply the following changes to the submitted URI.

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div>
<p class="font-bold text-blue-700">Query modifiers</p>
<ul>
  <li><a href="#modifierwithquery">withQuery</a></li>
  <li><a href="#modifierencodequery">encodeQuery</a></li>
  <li><a href="#modifiersortquery">sortQuery</a></li>
  <li><a href="#modifiermergequery">mergeQuery</a></li>
  <li><a href="#modifierappendquery">appendQuery</a></li>
  <li><a href="#modifierappendqueryparameters">appendQueryParameters</a></li>
  <li><a href="#modifierremovequeryparameters">removeQueryParameters</a></li>
  <li><a href="#modifierremovequeryparameterindices">removeQueryParameterIndices</a></li>
  <li><a href="#modifiermergequeryparameters">mergeQueryParameters</a></li>
  <li><a href="#modifierappendquerypairs">appendQueryPairs</a></li>
  <li><a href="#modifierremovequerypairs">removeQueryPairs</a></li>
  <li><a href="#modifiermergequerypairs">mergeQueryPairs</a></li>
  <li><a href="#modifiermreplacequerypair">replaceQueryPair</a></li>
  <li><a href="#modifiermreplacequeryparameter">replaceQueryParameter</a></li>
</ul>
</div>
<div>
<p class="font-bold text-blue-700">Host modifiers</p>
<ul>
  <li><a href="#modifierwithhost">withHost</a></li>
  <li><a href="#modifiernormalizehost">normalizeHost</a></li>
  <li><a href="#modifierhosttoascii">hostToAscii</a></li>
  <li><a href="#modifierhosttounicode">hostToUnicode</a></li>
  <li><a href="#modifiernormalizeip">normalizeIp</a></li>
  <li><a href="#modifierhosttodecimal">hostToDecimal</a></li>
  <li><a href="#modifierhosttooctal">hostToOctal</a></li>
  <li><a href="#modifierhosttohexadecimal">hostToHexadecimal</a></li>
  <li><a href="#modifierhosttoipv6compressed">hostToIpv6Compressed</a></li>
  <li><a href="#modifierhosttoipv6expanded">hostToIpv6Expanded</a></li>
  <li><a href="#modifierremovezoneidentifier">removeZoneIdentifier</a></li>
  <li><a href="#modifieraddrootlabel">addRootLabel</a></li>
  <li><a href="#modifierremoverootlabel">removeRootLabel</a></li>
  <li><a href="#modifierprependlabel">prependLabel</a></li>
  <li><a href="#modifierreplacelabel">replaceLabel</a></li>
  <li><a href="#modifierremovelabels">removeLabels</a></li>
  <li><a href="#modifierslicelabels">sliceLabels</a></li>
</ul>
</div>
<div>
<p class="font-bold text-blue-700">Path modifiers</p>
<ul>
  <li><a href="#modifierwithpath">withPath</a></li>
  <li><a href="#modifierremovedotsegments">removeDotSegments</a></li>
  <li><a href="#modifierremoveemptysegments">removeEmptySegments</a></li>
  <li><a href="#modifierremovetrailingslash">removeTrailingSlash</a></li>
  <li><a href="#modifieraddtrailingslash">addTrailingSlash</a></li>
  <li><a href="#modifierremoveleadingslash">removeLeadingSlash</a></li>
  <li><a href="#modifieraddleadingslash">addLeadingSlash</a></li>
  <li><a href="#modifierreplacedirname">replaceDirname</a></li>
  <li><a href="#modifierreplacebasename">replaceBasename</a></li>
  <li><a href="#modifierreplaceextension">replaceExtension</a></li>
  <li><a href="#modifieraddbasepath">addBasePath</a></li>
  <li><a href="#modifierremovebasepath">removeBasePath</a></li>
  <li><a href="#modifierappendsegment">appendSegment</a></li>
  <li><a href="#modifierprependsegment">prependSegment</a></li>
  <li><a href="#modifierreplacesegment">replaceSegment</a></li>
  <li><a href="#modifierremovesegments">removeSegments</a></li>
  <li><a href="#modifierslicesegments">sliceSegments</a></li>
  <li><a href="#modifierreplacedatauriparameters">replaceDataUriParameters</a></li>
  <li><a href="#modifierdatapathtobinary">dataPathToBinary</a></li>
  <li><a href="#modifierdatapathtoascii">dataPathToAscii</a></li>
</ul>
</div>
<div>
<p class="font-bold text-blue-700">Fragment modifiers</p>
<ul>
  <li><a href="#modifierwithfragment">withFragment</a></li>
  <li><a href="#modifierappendfragmentdirectives">appendFragmentDirectives</a></li>
  <li><a href="#modifierprependfragmentdirectives">prependFragmentDirectives</a></li>
  <li><a href="#modifierremovefragmentdirectives">removeFragmentDirectives</a></li>
  <li><a href="#modifierreplacefragmentdirective">replaceFragmentDirective</a></li>
  <li><a href="#modifierfilterfragmentdirectives">filterFragmentDirectives</a></li>
  <li><a href="#modifierslicefragmentdirectives">sliceFragmentDirectives</a></li>
  <li><a href="#modifierstripfragmentdirectives">stripFragmentDirectives</a></li>
  <li><a href="#modifierretainfragmentdirectives">retainFragmentDirectives</a></li>
</ul>
</div>
<div>
<p class="font-bold text-blue-700">Component modifiers</p>
<ul>
    <li><a href="#modifierwithscheme">withScheme</a></li>
    <li><a href="#modifierwithuserinfo">withUserInfo</a></li>
    <li><a href="#modifierwithport">withPort</a></li>
</ul>
</div>
</div>

## Query Modifiers

Following modifiers update and normalize the URI query component.

<p class="message-notice">Because each modification is done after parsing and building, the 
resulting query string may update the component character encoding. These changes are expected because of 
the rules governing parsing and building query string.</p>

### Modifier::withQuery

<p class="message-notice">since version <code>7.6.0</code></p>

Change the full query component.

~~~php
use League\Uri\Modifier;

echo Modifier::wrap("https://example.com/?kingkong=toto&foo=bar%20baz&kingkong=ape")
    ->withQuery('foo=bar')
    ->unwrap()
    ->getQuery(); 
//display "foo=bar"
~~~

### Modifier::encodeQuery

<p class="message-notice">since version <code>7.1.0</code></p>

Change the encoding of the query string. You can either specify one of PHP's constant between `PHP_QUERY_RFC1738` and
`PHP_QUERY_RFC3986`

~~~php
use League\Uri\Modifier;

echo Modifier::wrap("https://example.com/?kingkong=toto&foo=bar%20baz&kingkong=ape")
    ->encodeQuery(PHP_QUERY_RFC1738)
    ->unwrap()
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
    
Modifier::wrap(new Uri('https://example.com?foo[2]=bar#fragment'))
    ->appendQuery('url=https://example.com?foo[2]=bar#fragment')
    ->encodeQuery($converter)
    ->unwrap()
    ->getQuery();
//display "foo%5B2%5D=bar;url=https://example.com?foo%5B2%5D%3Dbar%23fragment"
~~~

### Modifier::sortQuery

Sorts the query according to its key values. The sorting rules are the same uses by WHATWG `URLSearchParams::sort` method.

~~~php
use League\Uri\Modifier;

echo Modifier::wrap("http://example.com/?kingkong=toto&foo=bar%20baz&kingkong=ape")
    ->sortQuery()
    ->unwrap()
    ->getQuery(); 
//display "kingkong=toto&kingkong=ape&foo=bar%20baz"
~~~

### Modifier::mergeQuery

Merges a submitted query string to the URI object to be modified. When merging two query strings with the same key value the submitted query string value takes precedence over the URI query string value.

~~~php
$uri = Http::new("http://example.com/test.php?kingkong=toto&foo=bar+baz#doc3");
echo Modifier::wrap($uri)
    ->mergeQuery('kingkong=godzilla&toto')
    ->unwrap()
    ->getQuery();
//display "kingkong=godzilla&foo=bar%20baz&toto"
~~~

### Modifier::appendQuery

Appends a submitted query string to the URI object to be modified. When appending two query strings with the same key value the submitted query string value is added to the return query string without modifying the URI query string value.

~~~php
$uri = Http::new("http://example.com/test.php?kingkong=toto&foo=bar+baz#doc3")
echo Modifier::wrap($uri)
    ->appendQuery('kingkong=godzilla&toto')
    ->unwrap()
    ->getQuery();
//display "kingkong=toto&kingkong=godzilla&foo=bar%20baz&toto"
~~~

### Modifier::appendQueryPairs

Appends a query pairs to the URI object to be modified. When appending two query strings with
the same key value the submitted query string value is added to the return query
string without modifying the URI query string value.

~~~php
$uri = Http::new("http://example.com/test.php?kingkong=toto&foo=bar+baz#doc3")
echo Modifier::wrap($uri)
    ->appendQueryPairs([['kingkong', 'godzilla'], ['toto', null]])
    ->unwrap()
    ->getQuery();
//display "kingkong=toto&kingkong=godzilla&foo=bar%20baz&toto"
~~~

### Modifier::appendQueryParameters

Appends a query PHP parameters to the URI object to be modified. When appending two query strings
with the same key value the submitted query string value is added to the return query
string without modifying the URI query string value.

~~~php
$uri = Http::new("http://example.com/test.php?kingkong=toto&foo=bar+baz#doc3")
echo Modifier::wrap($uri)
    ->appendQueryParameters(['kingkong' => 'godzilla', 'toto' => ''])
    ->unwrap()
    ->getQuery();
//display "kingkong=toto&kingkong=godzilla&foo=bar%20baz&toto="
~~~

### Modifier::removeQueryPairs

Removes query pairs from the current URI query string by providing the pairs key.

~~~php
$uri = "http://example.com/test.php?kingkong=toto&foo=bar+baz&bar=baz#doc3";
$modifier = Modifier::wrap($uri);
$newUri = $modifier->removeQueryPairs('foo', 'bar');

echo $modifier->unwrap()->getQuery(); //display "kingkong=toto&foo=bar+baz&bar=baz"
echo $newUri->unwrap()->getQuery();   //display "kingkong=toto"
~~~

### Modifier::removeQueryParameters

<p class="message-notice">since version <code>7.2.0</code></p>

Removes query params from the current URI query string by providing the param name. The removal preserves mangled key params.

~~~php
$uri = "http://example.com/test.php?kingkong=toto&fo.o=bar&fo_o=bar";
$modifier = Modifier::wrap($uri);
$newUri = $modifier->removeQueryParameters('fo.o');

echo $modifier->unwrap()->getQuery(); //display "kingkong=toto&fo.o=bar&fo_o=bar
echo $newUri->unwrap()->getQuery();   //display "kingkong=toto&fo_o=bar"
~~~


### Modifier::removeQueryParameterIndices

<p class="message-notice">since version <code>7.2.0</code></p>

Removes query params numeric indices from the current URI query string. The removal preserves mangled key params.

~~~php
$uri = "http://example.com/test.php?kingkong[1]=toto&fkingkong[2]=toto";
$modifier = Modifier::wrap($uri);
$newUri = $modifier->removeQueryParameterIndices();

echo $modifier->unwrap()->getQuery(); //display "kingkong%5B1%5D=toto&fkingkong%5B2%5D=toto"
echo $newUri->unwrap()->getQuery();   //display "kingkong%5B%5D=toto&fkingkong%5B%5D=toto"
~~~

### Modifier::mergeQueryParameters

<p class="message-notice">since version <code>7.2.0</code></p>

Merge PHP query parameters with the current URI query string by providing the parameters. The addition preserves mangled key params.

~~~php
$uri = "http://example.com/test.php?kingkong=toto&fo.o=bar&fo_o=bar";
$newUri = Modifier::wrap($uri)->mergeQueryParameters(['toto' => 'baz']);

echo $newUri->unwrap()->getQuery(); //display "kingkong=tot&fo.o=bar&fo_o=bar&toto=baz"
~~~

### Modifier::mergeQueryPairs

<p class="message-notice">since version <code>7.2.0</code></p>

Merge query paurs with the current URI query string by providing the pairs.

~~~php
$uri = "http://example.com/test.php?kingkong=toto&fo.o=bar&fo_o=bar";
$newUri = Modifier::wrap($uri)->mergeQueryPairs([['fo.o', 'champion']]);

echo $newUri->unwrap()->getQuery(); //display "kingkong=toto&fo.o=champion&fo_o=bar"
~~~

### Modifier::replaceQueryPair

<p class="message-notice">since version <code>7.6.0</code></p>

Replace a single query pair from the current URI by providing its offset.

~~~php
$uri = "http://example.com/test.php?kingkong=toto&fo.o=bar&fo_o=bar";
$newUri = Modifier::wrap($uri)->replaceQueryPair(1, 'name', 'john');

echo $newUri->unwrap()->getQuery(); //display "kingkong=toto&name=john&fo_o=bar"
~~~

<p class="message-info">Negative offsets are supported</p>
<p class="message-warning">If the offset does not exist, a <code>ValueError</code> will be thrown</p>

### Modifier::replaceQueryParameter

<p class="message-notice">since version <code>7.6.0</code></p>

Replace a single query parameter from the current URI by providing its name.

~~~php
$uri = "http://example.com/test.php?kingkong=toto&fo.o=bar&fo_o=bar";
$newUri = Modifier::wrap($uri)->replaceQueryParameter('fo.o', ['name' => 'john']);

echo $newUri->unwrap()->getQuery(); //display "kingkong=toto&fo.o%5Bname%5D=john&fo_o=bar"
~~~

<p class="message-warning">If the name does not exist, a <code>ValueError</code> will be thrown</p>

## Host modifiers

The following methods update and normalize the URI host component according to the underlying URI object.

### Modifier::withHost

<p class="message-notice">since version <code>7.6.0</code></p>

Change the full query component.

~~~php
use League\Uri\Modifier;

echo Modifier::wrap("https://example.com/?kingkong=toto&foo=bar%20baz&kingkong=ape")
    ->withHost('hello.be')
    ->unwrap()
    ->getHost(); 
//display "hello.be"
~~~

### Modifier::hostToAscii

Converts the host into its ascii representation according to RFC3986:

~~~php
<?php

use GuzzleHttp\Psr7\Uri;
use League\Uri\Modifier;

$uri = new Uri("http://스타벅스코리아.com/to/the/sky/");
$newUri = Modifier::wrap($uri)->hostToAscii()->unwrap();

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
$newUri = Modifier::wrap($uri)->hostToUnicode();

echo get_class($newUri); //display \GuzzleHttp\Psr7\Uri
echo $newUri; //display "http://스타벅스코리아.com/to/the/sky/"
~~~

<p class="message-warning">This method will have no effect on
<strong>League URI objects</strong> because the object always transcode the host component
into its RFC3986/ascii representation.</p>

### Modifier::hostToDecimal

Normalizes the URI host content to an IPv4 dot-decimal notation if possible
otherwise returns the uri instance unchanged. See the [IPv4 Converter documentation](/components/7.0/ipv4/)
page for more information.

~~~php
<?php

use League\Uri\Modifier;

$uri = 'http://0300.0250.0000.0001/path/to/the/sky.php';
echo Modifier::wrap($uri)->hostToDecimal()->unwrap();
//display 'http://192.168.0.1/path/to/the/sky.php'
~~~

### Modifier::hostToOctal

Normalizes the URI host content to an IPv4 dot-octal notation if possible
otherwise returns the uri instance unchanged. See the [IPv4 Converter documentation](/components/7.0/ipv4/)
page for more information.

~~~php
<?php

use League\Uri\Modifier;

$uri = 'http://192.168.0.1/path/to/the/sky.php';
echo Modifier::wrap($uri)->hostToOctal()->unwrap();
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
echo Modifier::wrap($uri)->hostToOctal()->unwrap();
//display 'http://0xc0a811/path/to/the/sky.php'
~~~

### Modifier::hostToIpv6Compressed

Normalizes the URI host content to a compressed IPv6 notation if possible.
See the [IPv6 Converter documentation](/components/7.0/ipv6/) page for more information.

~~~php
<?php

use League\Uri\Modifier;

$uri = 'http://[1050:0000:0000:0000:0005:0000:300c:326b]/path/to/the/sky.php';
echo Modifier::wrap($uri)->hostToIpv6Compressed()->toString();
//display 'http://[1050::5:0:300c:326b]/path/to/the/sky.php'
~~~

### Modifier::hostToIpv6Expanded

Normalizes the URI host content to a expanded IPv6 notation if possible.
See the [IPv6 Converter documentation](/components/7.0/ipv6/) page for more information.

~~~php
<?php

use League\Uri\Modifier;

$uri = 'http://[::1]/path/to/the/sky.php';
echo Modifier::wrap($uri)->hostToIpv6Expanded()->toString();
//display 'http://[0000:0000:0000:0000:0000:0000:0000:0001]/path/to/the/sky.php'
~~~

### Modifier::removeZoneIdentifier

Removes the host zone identifier if present

~~~php
<?php

use Zend\Diactoros\Uri;
use League\Uri\Modifier;

$uri = new Uri('http://[fe80::1234%25eth0-1]/path/to/the/sky.php');
$newUri = Modifier::wrap($uri)->removeZoneIdentifier()->unwrap();
echo $newUri::class; //display \Zend\Diactoros\Uri

echo $newUri; //display 'http://[fe80::1234]/path/to/the/sky.php'
~~~

### Modifier::normalizeIp

Format the IP host:

- it will compress the IP representation if the host is an IPv6 address
- it will convert the host to its IPv4 decimal format if possible

<p class="message-notice">Available since version <code>7.6.0</code></p>

~~~php
$uri = "https://0:0@0:0";
echo Modifier::wrap($uri)->normalizeIp()->toString();
//display "https://0:0@0.0.0.0:0"
~~~

### Modifier::normalizeHost

If the host is an IP address or a registrable domain that can be assimilated to
an IPv4 address it will use the `Modifier::normalizeIp` method. Otherwise, it
will try to convert the host into its ASCII format.

<p class="message-notice">Available since version <code>7.6.0</code></p>

~~~php
$uri = "https://0:0@0:0";
echo Modifier::wrap($uri)->normalizeHost()->toString();
//display "https://0:0@0.0.0.0:0"
~~~

This is the algorithm used by the WHATWG URL specification.

### Modifier::addRootLabel

Adds the root label if not present

~~~php
use League\Uri\Modifier;

echo Modifier::wrap('http://example.com:83')->addRootLabel(); //display 'http://example.com.:83'
~~~

### Modifier::removeRootLabel

Removes the root label if present

~~~php
use League\Uri\Modifier;

echo Modifier::wrap('http://example.com.#yes')->removeRootLabel();  //display 'http://example.com#yes'
~~~

### Modifier::appendLabel

Appends a host to the current URI host.

~~~php
use League\Uri\Modifier;

echo Modifier::wrap("http://www.example.com/path/to/the/sky/")->appendLabel('fr'); 
//display "http://www.example.com.fr/path/to/the/sky/"
~~~

### Modifier::prependLabel

Prepends a host to the current URI path.

~~~php
use League\Uri\Modifier;

echo Modifier::wrap("http://www.example.com/path/to/the/sky/")->prependLabel('shop');
//display "http://shop.www.example.com/path/to/the/sky/and/above"
~~~

### Modifier::replaceLabel

Replaces a label from the current URI host with a host.

<p class="message-notice">Hosts are hierarchical components whose labels are indexed from right to left.</p>

~~~php
use League\Uri\Modifier;

$uri = "http://www.example.com/path/to/the/sky/";
echo Modifier::wrap($uri)->replaceLabel(2, 'admin.shop');
//display "http://admin.shop.example.com/path/to/the/sky"
~~~

<p class="message-info">This modifier supports negative offset</p>

The previous example can be rewritten using negative offset:

~~~php
use League\Uri\Modifier;

$uri = "http://www.example.com/path/to/the/sky/";
echo Modifier::wrap($uri)->replaceLabel(-1, 'admin.shop');
//display "http://admin.shop.example.com/path/to/the/sky"
~~~

### Modifier::removeLabels

Removes selected labels from the current URI host. Labels are indicated string variadic labels offsets.

<p class="message-notice">Hosts are hierarchical components whose labels are indexed from right to left.</p>

~~~php

$uri = "http://www.localhost.com/path/to/the/sky/";
echo Modifier::wrap($uri)->removeLabels(2, 0);
//display "http://localhost/path/the/sky/"
~~~

<p class="message-info">This modifier supports negative offset</p>

The previous example can be rewritten using negative offset:

~~~php
$uri = "http://www.example.com/path/to/the/sky/";
Modifier::wrap($uri)->removeLabels(-1, -3)->toString();
//return "http://localhost/path/the/sky/"
~~~

### Modifier::sliceLabels

Slice the host from the current URI host. Negative offset are also supported.

<p class="message-notice">Hosts are hierarchical components whose labels are indexed from right to left.</p>

~~~php
$uri = "http://www.localhost.com/path/to/the/sky/";
echo Modifier::wrap($uri)->sliceLabels(1, 1)->toString();
//display "http://localhost/path/the/sky/"
~~~

<p class="message-info">This modifier supports negative offset</p>

## Path modifiers

<p class="message-notice">Because each modification is done after parsing and building, 
the resulting path may update the component character encoding. These changes are 
expected because of the rules governing parsing and building path string.</p>

### Modifier::withPath

<p class="message-notice">since version <code>7.6.0</code></p>

Change the full path component.

~~~php
use League\Uri\Modifier;

echo Modifier::wrap("https://example.com/?kingkong=toto&foo=bar%20baz&kingkong=ape")
    ->withPath('/path/to')
    ->unwrap()
    ->getPath(); 
//display "/path/to"
~~~

### Modifier::removeDotSegments

Removes dot segments according to RFC3986:

~~~php
$uri = "http://www.example.com/path/../to/the/./sky/";
echo Modifier::wrap($uri)->removeDotSegments();
//display "http://www.example.com/to/the/sky/"
~~~

### Modifier::removeEmptySegments

Removes adjacent separators with empty segment.

~~~php
$uri = "http://www.example.com/path//to/the//sky/";
echo Modifier::wrap($uri)->removeEmptySegments();
//display "http://www.example.com/path/to/the/sky/"
~~~

### Modifier::removeTrailingSlash

Removes the path trailing slash if present

~~~php
$uri = Uri::new("http://www.example.com/path/?foo=bar");
echo Modifier::wrap($uri)->removeTrailingSlash();
//display "http://www.example.com/path?foo=bar"
~~~

### Modifier::addTrailingSlash

Adds the path trailing slash if not present

~~~php
$uri = "http://www.example.com/sky#top";
echo Modifier::wrap($uri)->addTrailingSlash();
//display "http://www.example.com/sky/#top"
~~~

### Modifier::removeLeadingSlash

Remove the path leading slash if present.

~~~php
$uri = "/path/to/the/sky/";
echo Modifier::wrap($uri)->removeLeadingSlash();
//display "path/to/the/sky"
~~~

### Modifier::addLeadingSlash

Add the path leading slash if not present.

~~~php
echo Modifier::wrap("path/to/the/sky/")->addLeadingSlash();
//display "/path/to/the/sky"
~~~

### Modifier::replaceDirname

Adds, updates and/or removes the path dirname from the current URI path.

~~~php
echo Modifier::wrap("http://www.example.com/path/to/the/sky")
    ->replaceDirname('/road/to')
    ->unwrap()
    ->getPath(); //display "/road/to/sky"
~~~

### Modifier::replaceBasename

Adds, updates and or removes the path basename from the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky");
echo Modifier::wrap($uri)
    ->replaceBasename("paradise.xml")
    ->unwrap()
    ->getPath();
     //display "/path/to/the/paradise.xml"
~~~

### Modifier::replaceExtension

Adds, updates and or removes the path extension from the current URI path.

~~~php
$uri = Http::new("http://www.example.com/export.html");
echo Modifier::wrap($uri)->replaceExtension('csv')->unwrap()->getPath();
//display "/export.csv"
~~~

### Modifier::addBasePath

Adds the basepath to the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky");
echo Modifier::wrap($uri)
    ->addBasePath('/the/real')
    ->unwrap()
    ->getPath();
//display "/the/real/path/to/the/sky"
~~~

### Modifier::removeBasePath

Removes the basepath from the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky");
echo Modifier::wrap($uri)
    ->removeBasePath("/path/to/the")
    ->unwrap()
    ->getPath();
//display "/sky"
~~~

### Modifier::appendSegment

Appends a path to the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
echo Modifier::wrap($uri)
    ->appendSegment("and/above")
    ->unwrap()
    ->getPath();
 //display "/path/to/the/sky/and/above"
~~~

### Modifier::prependSegment

Prepends a path to the current URI path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
echo Modifier::wrap($uri)
    ->prependSegment("and/above")
    ->unwrap()
    ->getPath();
 //display "/and/above/path/to/the/sky/"
~~~

### Modifier::replaceSegment

Replaces a segment from the current URI path with a new path.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
echo Modifier::wrap($uri)
    ->replaceSegment(3, "sea")
    ->unwrap()
    ->getPath();
 //display "/path/to/the/sea/"
~~~

<p class="message-info">This modifier supports negative offset</p>

The previous example can be rewritten using negative offset:

~~~php
echo Modifier::wrap("http://www.example.com/path/to/the/sky/")
    ->replaceSegment(-1, "sea")
    ->getPath();
//display "/path/to/the/sea/"
~~~

### Modifier::removeSegments

Removes selected segments from the current URI path by providing the segments offset.

~~~php
echo Modifier::wrap("http://www.example.com/path/to/the/sky/")
    ->removeSegments(1, 3)
    ->unwrap()
    ->getPath();
//display "/path/the/"
~~~

<p class="message-info">This modifier supports negative offset</p>

~~~php
echo Modifier::wrap("http://www.example.com/path/to/the/sky/")
    ->removeSegments(-1, -2])
    ->unwrap()
    ->getPath();
//display "/path/the/"
~~~

### Modifier::sliceSegments

Slice the path from the current URI path. Negative offset are also supported.

~~~php
$uri = "http://www.localhost.com/path/to/the/sky/";
echo Modifier::wrap($uri)->sliceSegments(2, 2)->toString();
//display "http://www.localhost.com/the/sky/"
~~~

### Modifier::replaceDataUriParameters

Update Data URI parameters

~~~php
$uri = Uri::new("data:text/plain;charset=US-ASCII,Hello%20World!");
echo Modifier::wrap($uri)
    ->replaceDataUriParameters("charset=utf-8")
    ->unwrap()
    ->getPath();
//display "text/plain;charset=utf-8,Hello%20World!"
~~~

### Modifier::dataPathToBinary

Converts a data URI path from text to its base64 encoded version

~~~php
$uri = Uri::new("data:text/plain;charset=US-ASCII,Hello%20World!");
echo Modifier::wrap($uri)
    ->dataPathToBinary()
    ->unwrap()
    ->getPath();
//display "text/plain;charset=US-ASCII;base64,SGVsbG8gV29ybGQh"
~~~

### Modifier::dataPathToAscii

Converts a data URI path from text to its base64 encoded version

~~~php
$uri = Uri::new("data:text/plain;charset=US-ASCII;base64,SGVsbG8gV29ybGQh");
echo Modifier::wrap($uri)
    ->dataPathToAscii()
    ->unwrap()
    ->getPath();
//display "text/plain;charset=US-ASCII,Hello%20World!"
~~~

## Fragment Modifiers

### Modifier::withFragment

<p class="message-notice">since version <code>7.6.0</code></p>

Change the full fragment component.

~~~php
use League\Uri\Modifier;

echo Modifier::wrap("https://example.com/?kingkong=toto&foo=bar%20baz&kingkong=ape")
    ->withFragment('/path/to')
    ->unwrap()
    ->getFragment(); 
//display "/path/to"
~~~

### Modifier::appendFragmentDirectives

<p class="message-notice">Available since version <code>7.6.0</code></p>

Appends one or more directives to the current URI fragment.

~~~php
$uri = Http::new("http://www.example.com/path/to/the/sky/");
echo Modifier::wrap($uri)
    ->appendFragmentDirectives(new TextDirective(start:"foo", end:"bar"))
    ->appendFragmentDirectives('unknownDirective')
    ->unwrap()
    ->getFragment();
// display ":~:text=foo,bar&unknownDirective"
~~~

### Modifier::prependFragmentDirectives

<p class="message-notice">Available since version <code>7.6.0</code></p>

Prepends one or more directives to the current URI fragment.

~~~php
use Uri\WhatWg\Url;

$uri = new Url("http://www.example.com/path/to/the/sky/");
echo Modifier::wrap($uri)
    ->prependFragmentDirectives(new TextDirective(start:"foo", end:"bar"))
    ->prependFragmentDirectives('unknownDirective')
    ->unwrap()
    ->getFragment();
// display ":~:text=foo,bar&unknownDirective"
~~~

### Modifier::replaceFragmentDirective

<p class="message-notice">Available since version <code>7.6.0</code></p>

Replace a specific directive from the fragment.

~~~php
use Uri\WhatWg\Url;

$uri = new Url("http://www.example.com/path/to/the/sky/#:~:text=foo,bar&unknownDirective");
echo Modifier::wrap($uri)
    ->replaceFragmentDirective(1, new TextDirective(start:"bar", end:"foo"))
    ->unwrap()
    ->getFragment();
// display ":~:text=foo,bar&text=bar,foo"
~~~

### Modifier::removeFragmentDirectives

<p class="message-notice">Available since version <code>7.6.0</code></p>

Remove directives from the fragment using their offsets.

~~~php
use Uri\WhatWg\Url;

$uri = new Url("http://www.example.com/path/to/the/sky/#:~:text=foo,bar&unknownDirective");
echo Modifier::wrap($uri)
    ->removeFragmentDirectives(1)
    ->unwrap()
    ->getFragment();
// display ":~:text=foo,bar"
~~~

### Modifier::sliceFragmentDirectives

<p class="message-notice">Available since version <code>7.6.0</code></p>

Slice directives from the fragment using their offsets.

~~~php
use Uri\WhatWg\Url;

$uri = new Url("http://www.example.com/path/to/the/sky/#:~:text=foo,bar&unknownDirective&text=yes");
echo Modifier::wrap($uri)
    ->sliceDirective(0, 2)
    ->unwrap()
    ->getFragment();
// display ":~:text=yes"
~~~

### Modifier::filterFragmentDirectives

<p class="message-notice">Available since version <code>7.6.0</code></p>

Remove directives from the fragment using a callback.

~~~php
use Uri\WhatWg\Url;

$uri = new Url("http://www.example.com/path/to/the/sky/#:~:text=foo,bar&unknownDirective&text=yes");
echo Modifier::wrap($uri)
    ->filterFragmentDirectives(fn (Directive $directive, int $offset) => $directive->name() !== 'text')
    ->unwrap()
    ->getFragment();
// display ":~:text=foo,bar&text=yes"
~~~

### Modifier::stripFragmentDirectives

<p class="message-notice">Available since version <code>7.6.0</code></p>

Remove the fragment directives part of the fragment if it exists.

~~~php
use Uri\WhatWg\Url;

$uri = new Url("http://www.example.com/path/to/the/sky/#section2:~:text=foo,bar&unknownDirective&text=yes");
echo Modifier::wrap($uri)
    ->stripFragmentDirectives()
    ->unwrap()
    ->getFragment();
// display "section2"
~~~

### Modifier::retainFragmentDirectives

<p class="message-notice">Available since version <code>7.6.0</code></p>

Remove everything from the fragment excepts its fragment directives part if it exists.

~~~php
use Uri\WhatWg\Url;

$uri = new Url("http://www.example.com/path/to/the/sky/#section2:~:text=foo,bar&unknownDirective&text=yes");
echo Modifier::wrap($uri)
    ->retainFragmentDirectives(fn ()
    ->unwrap()
    ->getFragment();
// display ":~:text=foo,bar&text=yes"
~~~

## Other available modifiers

### Modifier::withUserInfo

<p class="message-notice">Available since version <code>7.6.0</code></p>

Allow modifying the user info component

~~~php
use League\Uri\Modifier;
use Uri\Rfc3986\Uri;

$uri = new Uri("http://www.example.com/path/to/the/sky");
echo Modifier::wrap($uri)
    ->withUserInfo(username: null, password: 'pa@ss')
    ->unwrap()
    ->getUserInfo();
// display ":pa%40ss"
// for information the Uri::withUserInfo method only takes a single variable.
~~~

### Modifier::withScheme

<p class="message-notice">Available since version <code>7.6.0</code></p>

Allow modifying the URI scheme

~~~php
use League\Uri\Modifier;
use Uri\Rfc3986\Uri;

$uri = new Uri("http://www.example.com/path/to/the/sky");
$newUri = Modifier::wrap($uri)->withScheme('HtTp')->unwrap();
$newUri->getRawScheme(); // returns 'HtTp'
$newUri->getScheme(); // returns 'http'
~~~

The stored scheme value will depend on the underlying URI object.

### Modifier::withPort

<p class="message-notice">Available since version <code>7.6.0</code></p>

Allow modifying the URI port. Added for completeness. This
method works the same across all the URI objects, but may throw
depending on the restriction from the underlying URI object on
port range.

~~~php
use League\Uri\Modifier;
use Uri\WhatWg\Url;

$uri = new Url("http://www.example.com/path/to/the/sky");
$newUri = Modifier::wrap($uri)->withPort(433)->unwrap();
$newUri->getPort(); // returns 433
~~~
