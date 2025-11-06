---
layout: default
title: URI template
---

URI Template
=======

The `League\Uri\UriTemplate` class enables expanding a URI object based on a URI template and its 
submitted parameters following [RFC 6570 URI Template](http://tools.ietf.org/html/rfc6570).

## Template Expansion

### RFC Expansion

The `UriTemplate::expand` public method expands a URI template to generate a valid URI conforming
to RFC3986.

~~~php
<?php

use League\Uri\UriTemplate;

$template = 'https://example.com/hotels/{hotel}/bookings/{booking}';
$params = ['booking' => '42', 'hotel' => 'Rest & Relax'];

$uriTemplate = new UriTemplate($template);
$uri = $uriTemplate->expand($params);              // instance of League\Uri\Uri
$rfc3986Uri = $uriTemplate->expandToUri($params);  // instance of Uri\Rfc3986\Uri
$whatWgUrl = $uriTemplate->expandToUrl($params);   // instance of Uri\Whatwg\Url
$psr7Uri = $uriTemplate->expandToPsr7Uri($params); // instance of Psr7 UriInterface

echo $uri; //display https://example.com/hotels/Rest%20%26%20Relax/bookings/42"
~~~

<p class="message-notice"><code>expandToUri()</code>, <code>expandToUrl()</code> and <code>expandToPsr7Uri()</code> are available since
version <code>7.6.0</code></p>

<p class="message-info">Since version <code>7.6</code> the <code>expand()</code> method takes a second optional parameter
which serves as a base URI. The generated URI is resolved against the Base URI.</p>

~~~php
<?php

use League\Uri\UriTemplate;

$template = '/hotels/{hotel}/bookings/{booking}';
$params = ['booking' => '42', 'hotel' => 'Rest & Relax'];

$uriTemplate = new UriTemplate($template);
$uri = $uriTemplate->expand($params, 'https://example.com');             // instance of League\Uri\Uri
$rfc3986Uri = $uriTemplate->expandToUri($params, 'https://example.com'); // instance of Uri\Rfc3986\Uri
$whatWgUrl = $uriTemplate->expandToUrl($params, 'https://example.com');  // instance of Uri\Whatwg\Url
$whatWgUrl = $uriTemplate->expandToPsr7Uri($params, 'https://example.com');  // instance of League\Uri\Http

echo $uri; //display https://example.com/hotels/Rest%20%26%20Relax/bookings/42"
~~~

### Strict Expansion

By default, if variables are missing or are not provided an empty string is used as replacement
string as per the RFC. If you want to force correct expansion you can use the `expandOrFail`
method. It behaves exactly like the `expand` method but will additionnally throw an
exception if there are missing required variables.

~~~php
$template = 'https://api.twitter.com/{version}/search/{term}/{?q*,limit}';

$params = [
    'term' => ['john', 'doe'],
    'q' => ['a', 'b'],
    'limit' => '10',
];

$uriTemplate = new UriTemplate($template);
echo $uriTemplate->expand($params), PHP_EOL;
// display https://api.twitter.com//search/john,doe/?q=a&q=b&limit=10 with missing version

echo $uriTemplate->expandOrFail($params);
// will throw a TemplateCanNotBeExpanded exception with the following message
// Missing variables `version`
~~~

<p class="message-notice"><code>expandToUriOrFail()</code> and <code>expandToUrlOrFail()</code> are available since
version <code>7.6.0</code></p>

## Template Variables

<p class="message-notice">For maximum interoperability you should make sure your variables are 
strings or stringable objects otherwise the value will be cast to string following PHP rules 
except for boolean values <code>true</code> and <code>false</code> which will be converted 
to <code>1</code> and <code>0</code> respectively.</p>

### Default Variables

The constructor takes an optional set of default variables that can be applied by default when
expanding the URI template.

~~~php
$template = 'https://api.twitter.com/{version}/search/{term:1}/{term}/{?q*,limit}';

$params = [
    'term' => 'john',
    'q' => ['a', 'b'],
    'limit' => '10',
];

$uriTemplate = new UriTemplate($template, ['version' => 1.1]);
echo $uriTemplate->expand($params);
//displays https://api.twitter.com/1.1/search/j/john/?q=a&q=b&limit=10
~~~

### Runtime Variables

The default variables are overwritten by those supplied to the `expand` method.

~~~php
$template = 'https://api.twitter.com/{version}/search/{term:1}/{term}/{?q*,limit}';

$params = [
    'term' => 'john',
    'q' => ['a', 'b'],
    'limit' => '10',
    'version' => '2.0'
];

$uriTemplate = new UriTemplate($template, ['version' => '1.1']);
echo $uriTemplate->expand($params), PHP_EOL;
//displays https://api.twitter.com/2.0/search/j/john/?q=a&q=b&limit=10
~~~

The `expandToUri()`, `expandToUrl()` and `expandToPsr7Uri()` methods will act exactly like the `expand()` method
but will instead return a `Uri\Rfc3986\Uri`, a `Uri\Whatwg\Url` or a `Psr7\Http\Message\UriInterface` object respectively.

<p class="message-warning">a WHATWG URL must always be absolute if the URI template is not you
MUST provide a base URL to <code>expandToUrl()</code> otherwise an <code>Uri\WhatWg\InvalidUrlException</code>
exception will be thrown.</p>

~~~php
$template = '/{version}/search/{term:1}/{term}/{?q*,limit}';
//the template represents a non-absolute URL

$params = [
    'term' => 'john',
    'q' => ['a', 'b'],
    'limit' => '10',
    'version' => '2.0'
];

$uriTemplate = new UriTemplate($template, ['version' => '1.1']);

echo $uriTemplate->expandToUrl($params, 'https://api.twitter.com')->toAsciiString(); //works
echo $uriTemplate->expandToUrl($params); //will throw
~~~

<p class="message-info">You may return a different <code>Psr7\Http\Message\UriInterface</code> object if you
provide to the <code>expandToPsr7Uri</code> your own <code>Psr\Http\Message\UriFactoryInterface</code> class
as the third optional argument.</p>

~~~php
use League\Uri\UriTemplate;
use Laminas\Diactoros\UriFactory;

$template = '/{version}/search/{term:1}/{term}/{?q*,limit}';
//the template represents a non-absolute URL

$params = [
    'term' => 'john',
    'q' => ['a', 'b'],
    'limit' => '10',
    'version' => '2.0'
];

$uriTemplate = new UriTemplate($template, ['version' => '1.1']);
$uri = $uriTemplate->expandToPsr7Uri($params, 'https://api.twitter.com', new UriFactory());
$uri::class; // returns 'Laminas\Diactoros\Uri'
~~~

By default, if no factory is provided, the returned PSR-7 `UriInterface` object is a `League\Uri\Http` instance.

### Updating the Default Variables

At any given time you may update your default variables but since the `UriTemplate`
is an immutable object instead of modifying the current instance, a new
instance with the modified default variables will be returned.

~~~php
$template = 'https://api.twitter.com/{version}/search/{term:1}/{term}/{?q*,limit}';

$params = [
    'term' => 'john',
    'q' => ['a', 'b'],
    'limit' => '10',
    'version' => '2.0'
];

$uriTemplate = new UriTemplate($template, ['version' => '1.0', 'foo' => 'bar']);
$uriTemplate->getDefaultVariables(); //returns new VariableBag(['version' => '1.0'])
$newUriTemplate = $uriTemplate->withDefaultVariables(['version' => '1.1']);
$newUriTemplate->getDefaultVariables(); //returns  new VariableBag(['version' => '1.1'])
~~~

<p class="message-warning">Following RFC6570 requirements means not support for
nested array like the one used with <code>http_build_query</code></p>

~~~php
$template = 'https://example.com/hotels/{hotel}/book{?query*}';
$params = [
    'hotel' => 'Rest & Relax',
    'query' => [
        'period' => [
            'start' => '2020-01-12',
            'end' => '2020-01-15',
        ],
    ],
];

$uriTemplate = new UriTemplate($template);
$uriTemplate->expand($params);
// will throw a League\Uri\UriTemplate\TemplateCanNotBeExpanded when trying to expand the `period` value.
~~~

### Prefix Modifier Limitations

While the RFC does not forbid this, the `UriTemplate` class will throw an exception 
if an attempt is made to use the prefix modifier with a list of value. Other 
implementations will silently ignore the modifier **but** this package will
trigger the exception to alert the user that something might be wrong and 
that the generated URI might not be the one expected.

~~~php
$template = 'https://api.twitter.com/{version}/search/{term:1}/{term}/{?q*,limit}';

$params = [
    'term' => ['john', 'doe'],
    'q' => ['a', 'b'],
    'limit' => '10',
    'version' => '2.0'
];

$uriTemplate = new UriTemplate($template);
echo $uriTemplate->expand($params), PHP_EOL;
// throw a League\Uri\UriTemplate\TemplateCanNotBeExpanded because the term variable is a list and not a string.
~~~

## Expressions

### Braces Usage

The following implementation disallows the use of braces `{` or  `}` outside of being URI
template expression delimiters. If not used as the boundary of an expression an
exception will be triggered. 

~~~php
$template = 'https://example.com/hotels/{/book{?query*}';
$uriTemplate = new UriTemplate($template);
// will throw a League\Uri\Exceptions\SyntaxError on instantiation
~~~

If your template do require them you should URL encode them.

~~~php
$template = 'https://example.com/hotels/%7B/{hotel}';
$params = ['booking' => 42, 'hotel' => 'Rest & Relax'];

$uriTemplate = new UriTemplate($template);
echo $uriTemplate->expand($params), PHP_EOL;
// https://example.com/hotels/%7B/Rest%20%26%20Relax
~~~

## Interoperability

<p class="message-notice">Available since <code>version 7.6</code></p>

To allow easier integration with other PHP packages and especially [PSR-13](https://www.php-fig.org/psr/psr-13/)
the `UriTemplate` class implements the `Stringable` interface.

~~~php
use League\Uri\UriTemplate;
use Symfony\Component\WebLink\Link;

$uriTemplate = new UriTemplate('https://google.com/search{?q*}');

$link = (new Link())
    ->withHref($uriTemplate)
    ->withRel('next')
    ->withAttribute('me', 'you');

// Once serialized will return
// '<https://google.com/search{?q*}>; rel="next"; me="you"'
~~~

The `Symfony\Component\WebLink\Link` package implements `PSR-13` interfaces.
