---
layout: default
title: RFC 6570 compliant URI template
description: The UriTemplate class enables expanding a URI template and its submitted parameters into an URI object.
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

### Template Variables

<p class="message-notice">For maximum interoperability you should make sure your variables are 
strings or stringable objects otherwise the value will be cast to string following PHP rules 
except for boolean values <code>true</code> and <code>false</code> which will be converted 
to <code>1</code> and <code>0</code> respectively.</p>

#### Default Variables

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

#### Runtime Variables

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

#### Updating Variables

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

### Limitations

#### Prefix Modifier

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

#### Braces Usage

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

### Interoperability

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

## Variable Extraction

<p class="message-notice">available since version <code>7.9.0</code></p>

While RFC 6570 only defines variable expansion and not extraction, this package provides an API
for extracting variables using the same URI Template syntax.

### Basic usage

~~~php
use League\Uri\UriTemplate;

$template = 'https://example.com/hotels/{hotel}/bookings/{booking}';
$uriString = 'https://example.com/hotels/Rest%20%26%20Relax/bookings/42';

$uriTemplate = new UriTemplate($template);
$result = $uriTemplate->extract($uriString); 
// $result is a League\Uri\UriTemplate\ExtractionResult object

$result->isSuccessful();      
// true

count($result);
// 2

echo $result['booking'];
// '42'

echo $result['hotel'];
// 'Rest & Relax'

isset($result['missing']);
// false

$result->variables();
// [
//   "hotel" => "Rest & Relax"
//   "booking" => "42"
// ]
~~~

The `UriTemplate::extract()` method accepts all supported URI objects, as well as
backed enums, strings and `Stringable` instances.

The returned object is an instance of `League\Uri\UriTemplate\ExtractionResult`,
which provides access to the extraction state and result. You can:

- determine whether the extraction was successful or failed
- count, fetch or inspect extracted values by name
- determine which variables are missing or why the extraction failed

In case of failure, you can still inspect the result:

~~~php
use League\Uri\UriTemplate;

$template = '/{term:1}/{term}{?a,b}';
$uriString = '/j//thomas?a=1';

$uriTemplate = new UriTemplate($template);
$result = $uriTemplate->extract($uriString);
// $result is a League\Uri\UriTemplate\ExtractionResult object

$result->isSuccessful();
// false

$result->reasons();
// [
//   League\Uri\UriTemplate\ExtractionErrorReason::ReconciliationFailed,
//   League\Uri\UriTemplate\ExtractionErrorReason::PrefixLengthExceeded,
// ]
~~~

### Extracted values

~~~php
use League\Uri\UriTemplate;

$template = '/{version}/search/{term:1}/{?q*,limit}';
$uriTemplate = new UriTemplate($template, ['version' => 1.1]);
$result = $uriTemplate->extract("/1.1/search/j/?q=a&q=b&limit=10");
$result->isSuccessful();
// true
~~~

#### Value Access

`ExtractionResult::fetch()` returns an `ExtractedValue` instance for a variable that was
successfully extracted, or `null` if the variable is not present.

`ExtractedValue` provides access to both the extracted value and information about whether
the value is complete or partial.

```php
$variable = $result->fetch('term');

$variable->value;
// "j"

$variable->isPartial;
// true
```

A value is partial when it was extracted from a variable with a position modifier and the
complete value was not present in the input.

If you only need the extracted value itself, `ExtractionResult` implements `ArrayAccess`.
Array access returns the extracted value directly, without the additional `ExtractedValue`
metadata.

```php
$variable = $result['term'];
// "j"
```

Extracted values are **URL-decoded**. Percent-encoded sequences in the input are decoded
before the values are returned. The `+` character is not interpreted as a space.

```php
use League\Uri\UriTemplate;

$uriTemplate = new UriTemplate('/hotels/{hotel}');
$result = $uriTemplate->extract('/hotels/Rest%20%26%20Relax');

$result['hotel'];
// 'Rest & Relax'
```

Some scalar extracted values also have an alternative list interpretation available
through `ExtractedValue::asList`. This allows values that can be represented as a list to
be accessed as such without changing their original scalar value.

```php
$uriTemplate = new UriTemplate('{count}');
$result = $uriTemplate->extract('one,two,three');

$variable = $result->fetch('count');

$variable->value;
// "one,two,three"

$variable->asList;
// ["one", "two", "three"]
```

The original scalar value remains available through `value` and `string()`, while
`array()` uses the list interpretation when one is available.

`ExtractionResult::variables()` returns all extracted values as an associative array.
Each key is a variable name and each value is the corresponding extracted value.

```php
$result->variables();
// [
//   "version" => "1.1"
//   "term" => "j"
//   "q" => [
//     "a",
//     "b",
//   ]
//   "limit" => "10"
// ]
```

#### Type Inference

`ExtractionResult` also provides typed accessors for retrieving an extracted value in a
specific type:

* `ExtractionResult::string()`
* `ExtractionResult::boolean()`
* `ExtractionResult::integer()`
* `ExtractionResult::float()`
* `ExtractionResult::array()`
* `ExtractionResult::date()`
* `ExtractionResult::enum()`

These methods retrieve the raw extracted value and attempt to convert it to the requested
type. If the value is missing or cannot be converted, `null` is returned.

```php
use League\Uri\UriTemplate;

$uriTemplate = new UriTemplate('/search/{when}/{?limit,status}');
$result = $uriTemplate->extract('/search/2025-03-08/?limit=10&status=published');

$result['limit'];
// "10"

$result->string('limit');
// "10"

$result->integer('limit');
// 10

$result->float('limit');
// 10.0

$result->boolean('limit');
// false

$result->date('when', '!Y-m-d');
// DateTimeImmutable object

$result->enum('status', Status::class);
// Status::Published
```

`ExtractionResult::array()` returns an extracted `array` directly. When the extracted value
is scalar but provides an alternative list interpretation through `ExtractedValue::asList`,
that list is returned instead. Scalar values without a list interpretation and missing
values return an empty array.

```php
use League\Uri\UriTemplate;

$uriTemplate = new UriTemplate('{count}'); 
$result = $uriTemplate->extract('one,two,three'); 
$result->string('count');
// "one,two,three"

$result->array('count');
// ["one", "two", "three"]
```

#### Collection

The same typed accessors are available for extracted values that are expected to be
arrays:

* `ExtractionResult::strings()`
* `ExtractionResult::booleans()`
* `ExtractionResult::integers()`
* `ExtractionResult::floats()`
* `ExtractionResult::dates()`
* `ExtractionResult::enums()`

These methods are based on the value returned by the `array()` method.
An empty array is returned if the `array()` method returns an empty array,
or if any member of the array cannot be converted to the requested type.

```php
$template = '/search{.format}{?date*}';
$uri = '/search.json?to=2026-09-14&from=2026-01-01';

$uriTemplate = Template::new($template);
$result = $uriTemplate->extract($uri);

$result['date'];
// [
//   "to" => "2026-09-14"
//   "from" => "2026-01-01"
// ]

$result->dates('date', '!Y-m-d', 'Europe/Brussels');
// [
//   "to" => DateTimeImmutable::createFromFormat(
//       '!Y-m-d',
//       '2026-09-14',
//       new DateTimeZone('Europe/Brussels'),
//   ),
//   "from" => DateTimeImmutable::createFromFormat(
//       '!Y-m-d',
//       '2026-01-01',
//       new DateTimeZone('Europe/Brussels'),
//   ),
// ]
```

### Strict Mode

`UriTemplate::extract()` always returns an `ExtractionResult`, even when the input cannot be matched by the template,
or, when some variable can not be extracted because they are missing. If you need extraction to fail explicitly
in these situations, use `UriTemplate::extractOrFail()` which throws a `VariableCanNotBeExtracted`
exception if the extraction fails for any reason.

~~~php
use League\Uri\UriTemplate;

$template = '/{version}/search/{term:1}/{?q*,limit}';
$uriTemplate = new UriTemplate($template, ['version' => 1.1]);
$result = $uriTemplate->extract("/foo/bar");
$result->isSuccessful();
// false

$uriTemplate->extractOrFail("/foo/bar");
// throws a League\Uri\UriTemplate\VariableCanNotBeExtracted exception

$uriTemplate->match("/foo/bar");
// false
~~~

When strict extraction fails, `VariableCanNotBeExtracted::getReasons()` returns the distinct
`ExtractionErrorReason` cases encountered during extraction. `VariableCanNotBeExtracted::getMissingVariables()`
returns the names of variables that were not provided by the input.

~~~php
use League\Uri\UriTemplate;

$template = '/{version}/search/{term:1}/{?q*,limit}';
try { 
    $uriTemplate->extractOrFail('/foo/bar'); 
} catch (VariableCanNotBeExtracted $exception) { 
    $exception->getReasons(); 
    // [ 
    // ExtractionErrorReason::LiteralMismatch, 
    // ... 
    // ] 
    
    $exception->getMissingNames(); 
    // [...]
}
~~~

The `UriTemplate::match()` method can be used when you only need to know whether an input
matches the template, without extracting its variables. It uses the same strict matching
rules as `UriTemplate::extractOrFail()`, while `UriTemplate::extract()` allows variables
defined by the template to be missing.

Unlike `extract()`, both `extractOrFail()` and `match()` require the input to
completely match the template and all variables to be successfully extracted.

### Limitations

Variable extraction is an extension provided by this package; it is **not defined by RFC 6570**,
which only specifies URI Template expansion.

Extraction also has some inherent limitations:

#### Template structure.

The Extraction is based on the template structure. A URI can match a template
without containing enough information to reconstruct the complete value of a
variable. In such cases, a variable may be returned as a partial value.

  For example, with the template:

~~~text
  /{id:3}
~~~

  extracting from a given variable whose value is `123456` can only recover the first three characters:

~~~php
use League\Uri\UriTemplate;

$uriTemplate = new UriTemplate('/{id:3}');
$uri = $uriTemplate->expand(['id' => 123456]);
echo $uri, PHP_EOL;
// "/123"

$res = $uriTemplate->extract($uri);
$res['id'];
// "123"
~~~

#### Application semantics

Extraction does not interpret application semantics. The library extracts values from
the URI according to the URI Template syntax; it does not validate whether those
values are meaningful to your application.

  For example:

~~~text
  /users/{id}
~~~

  extracting from `/users/abc` produces:

~~~php
  ['id' => 'abc']
~~~

  Whether `abc` is a valid user identifier is an application-level concern.

#### Ambiguous templates

Ambiguous templates require a deterministic match. When different parts of a
template can match the same input in multiple ways, extraction uses the
structure and delimiters of the template to determine the boundaries and
may backtrack between possible matches. It cannot determine the
application's intended interpretation when the template itself
does not provide enough information.

  For example:

~~~text
  {/segments*}/{file}
~~~

can match:

~~~text
  /path/to/file
~~~

in more than one way. The extraction algorithm uses the template's delimiters and matching rules
to determine the boundary between `segments` and `file`; it cannot know an application's
intended interpretation beyond those rules.

For these reasons, variable extraction should be considered a convenient way to recover variables
from URIs that follow a known template, rather than a general-purpose URI parser.

### Combining extraction and expansion

An `ExtractionResult` can be passed directly to `UriTemplate::expand*()` methods. This makes it possible
to extract variables from a URI, modify them if needed, and expand a different template.

```php
$template = 'https://api.twitter.com/{version}/search/{term:1}/{?q*,limit}';
$uriTemplate = new UriTemplate($template, ['version' => 1.1]);
$result = $uriTemplate->extract(
    'https://api.twitter.com/1.1/search/j/?q=a&q=b&limit=10'
);

$uriTemplate->expand($result)->toString();
// https://api.twitter.com/1.1/search/j/?q=a&q=b&limit=10
```

This provides a convenient extraction-to-expansion workflow:

```text
URI → extract() → ExtractionResult → expand() → URI
```

<p class="message-warning"><strong>The round trip is not necessarily lossless.</strong> A variable 
extracted as a <strong>partial value</strong>, for example because of a prefix modifier, only
contains the portion that could be recovered from the input. Expanding that result
therefore uses the extracted value and cannot reconstruct information that was
not captured.</p>
