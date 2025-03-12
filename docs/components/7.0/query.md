---
layout: default
title: The Query component
---

The Query
=======

The library provides a `League\Uri\Components\Query` class to ease query string creation and manipulation. This URI component object exposes the [package common API](/components/7.0/), but also provide specific methods to work with the URI query component.

<p class="message-notice">If the modifications do not change the current object, it is returned as is, otherwise, a new modified object is returned.</p>

<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>

## Standard instantiation

### Using an RFC compliant algorithm


~~~php
<?php
public static Query::new(Stringable|string|null $value = null): self
public static Query::fromUri(): self
public static Query::fromRFC3986(Stringable|string $value, string $separator = '&'): self
public static Query::fromRFC1738(Stringable|string $value, string $separator = '&'): self
public static Query::fromFormData(Stringable|string $value, string $separator = '&'): self
~~~

- `new` and `fromRFC3986` instantiates a query string encoded using [RFC3986](https://tools.ietf.org/html/rfc3986#section-3.4) query component rules;
- `fromRFC1738` instantiates a query string encoded using [RFC1738](https://tools.ietf.org/html/rfc1738) rules;
- `fromFormData` instantiates a query string encoded using [application/x-www-form-urlencoded](https://url.spec.whatwg.org/#urlencoded-parsing) rules;

~~~php
<?php

use League\Uri\Components\Query;

$query = Query::new('foo=bar&bar=baz%20bar%2A');
//or
$query = Query::fromRFC3986('foo=bar&bar=baz%20bar%2A', '&');
$query->get('bar'); // returns 'baz bar*'

$query = Query::fromRFC1738('foo=bar&bar=baz+bar%2A', '&');
$query->get('bar'); // returns 'baz bar*'

$query = Query::fromFormData('foo=bar&bar=baz+bar*', '&');
$query->get('bar'); // returns 'baz bar*'
~~~

### Query separator

The query separator is essential to query manipulation. The `Query` object provides two (2) simple methods to interact with its separator:

~~~php
public Query::getSeparator(string $separator): self
public Query::withSeparator(): string
~~~

`Query::getSeparator` returns the current separator attached to the `Query` object while `Query::withSeparator` returns a new `Query` object with an alternate string separator.
`Query::withSeparator` expects a single argument which is a string separator. If the separator is equal to `=` an exception will be thrown.

~~~php
$query    = Query::fromRFC3986('foo=bar&baz=toto');
$newQuery = $query->withSeparator('|');
$newQuery->__toString(); //return foo=bar|baz=toto
~~~

## Component representations

In addition to the common methods from the [package common API](/components/7.0/), the following methods are available.

### RFC3986 representation

The `Query` object can return the query encoded using the [RFC3986](https://tools.ietf.org/html/rfc3986#section-3.4) query component rules

~~~php
$query = Query::fromRFC1738('foo=bar&bar=baz+bar%2A', '&');
$query->toRFC3986();  //returns 'foo=bar&bar=baz%20bar%2A'
$query->value();     //returns 'foo=bar&bar=baz%20bar%2A'
~~~

If the query is undefined, this method returns `null`.

<p class="message-info"><code>Query::toRFC3986()</code> is a alias of <code>Query::value()</code></p>

### RFC1738 representation

The `Query` object returns the query encoded using the [RFC1738](https://tools.ietf.org/html/rfc1738) query component rules

~~~php
$query = Query::fromRFC3986('foo=bar&bar=baz%20bar', '&');
$query->toRFC1738(); // returns 'foo=bar&bar=baz+bar'
$query->jsonSerialize(); //returns 'foo=bar&bar=baz+bar'
~~~

If the query is undefined, this method returns `null`.

### FormData representation

The `Query` object returns the query encoded using the [application/x-www-form-urlencoded](https://url.spec.whatwg.org/#urlencoded-parsing) query component rules

~~~php
$query = Query::fromRFC3986('foo=bar&bar=baz%20bar%2A', '&');
$query->toFormData(); // returns 'foo=bar&bar=baz+bar*'
$query->jsonSerialize(); //returns 'foo=bar&bar=baz+bar*'
~~~

If the query is undefined, this method returns `null`.

<p class="message-info"><code>Query::jsonSerialize()</code> is an alias of <code>Query::toFormData()</code> to improve interoperability with JavaScript.</p>

## Modifying the query

### Query::merge

`Query::merge` returns a new `Query` object with its data merged.

~~~php
<?php

public Query::merge($query): Query
~~~

This method expects a single argument which is a string

~~~php
$query    = Query::fromRFC3986('foo=bar&baz=toto');
$newQuery = $query->merge('foo=jane&r=stone');
$newQuery->__toString(); //return foo=jane&baz=toto&r=stone
// the 'foo' parameter was updated
// the 'r' parameter was added
~~~

<p class="message-info">Values equal to <code>null</code> or the empty string are merge differently.</p>

~~~php
$query    = Query::fromRFC3986('foo=bar&baz=toto');
$newQuery = $query->merge('baz=&r');
$newQuery->__toString(); //return foo=bar&baz=&r
// the 'r' parameter was added without any value
// the 'baz' parameter was updated to an empty string and its = sign remains
~~~

### Query::append

`Query::append` returns a new `Query` object with its data append to it.

~~~php
public Query::append($query): Query
~~~

This method expects a single argument which is a string, a scalar or an object with the `__toString` method.

~~~php
$query    = Query::fromRFC3986('foo=bar&john=doe');
$newQuery = $query->append('foo=baz');
$newQuery->__toString(); //return foo=jane&foo=baz&john=doe
// a new foo parameter is added
~~~

### Query::sort

`Query::sort` returns a `Query` object with its pairs sorted according to its keys. Sorting is done so
that parsing stayed unchanged before and after processing the query.

~~~php
$query    = Query::fromRFC3986('foo=bar&baz=toto&foo=toto');
$newQuery = $query->sort();
$newQuery->__toString(); //return baz=toto&foo=bar&foo=toto
~~~

<p class="message-notice">since version <code>7.3.0</code>, the sorting algorithm has been updated to match <a href="https://url.spec.whatwg.org/#dom-urlsearchparams-sort">WHATG group specification</a></p>

## Using the Query as a PHP data transport layer

~~~php
public static Query::fromVariable($params, string $separator = '&'): self
public Query::parameters(): array
public Query::parameter(string $name): mixed
public Query::hasParameter(string ...$name): bool
public Query::withoutNumericIndices(): self
public Query::withoutParameter(...string $offsets): self
~~~

<p class="message-info"><code>Query::fromVariable</code> replaced the deprecated 
<code>Query::fromParameters</code> named constructor which was 
not inconsistent against <code>http_build_query</code> algorithm.</p>

### Using PHP data structure to instantiate a new Query object

Historically, the query string has been used as a data transport layer of PHP variables. The `fromParams` uses
PHP own data structure to generate a query string *à la* `http_build_query`.

~~~php
parse_str('foo=bar&bar=baz+bar', $params);

$query = Query::fromVariable($params, '|');
echo $query->value(); // returns 'foo=bar|bar=baz%20bar'
~~~

<p class="message-info">The <code>$params</code> input can be any argument type supported by <code>http_build_query</code> which means that it can be an <code>iterable</code> or 
an object with public properties.</p>

<p class="message-notice">If you want a better parsing you can use the <a href="/components/7.0/query-parser-builder/">QueryString</a> class.</p>

### Query::parameters

If you already have an instantiated `Query` object you can return all the query string deserialized arguments using the `Query::parameters` method:

~~~php
$query_string = 'foo.bar=bar&foo_bar=baz';
parse_str($query_string, $out);
var_export($out);
// $out = ["foo_bar" => 'baz'];

$arr = Query::fromRFC3986($query_string)->parameters();
// $arr = ['foo.bar' => 'bar', 'foo_bar' => baz']];
~~~


If you are only interested in a given argument you can access it directly by supplyling the argument name as show below:

~~~php
$query = Query::fromRFC3986('foo[]=bar&foo[]=y+olo&z=');
$query->parameter('foo');   //return ['bar', 'y+olo']
$query->parameter('gweta'); //return null
~~~

The method returns the value of a specific argument. If the argument does not exist it will return `null`.

The class can tell whether a parameter or a range of parameters are present using the `Query::hasParameter` method.
The method will take a variadic number of parameter names and will return `true` only if all the names are
present in the query parameter bag.

~~~php
$query = Query::fromRFC3986('foo[]=bar&foo[]=y+olo&z=');
$query->hasParameter('gweta');        //return false
$query->hasParameter('foo', 'z');     //return true
$query->hasParameter('foo', 'gweta'); //return false
~~~

### Query::withoutParameter

If you want to remove PHP's variable from the query string you can use the `Query::withoutParams` method as shown below

~~~php
$query = Query::fromRFC3986('foo[]=bar&foo[]=y+olo&z=');
$new_query = $query->withoutParameter('foo');
$new_query->params('foo'); //return null
echo $new_query->value(); //return 'z='
~~~

<p class="message-info">This method takes a variadic arguments representing the keys to be removed.</p>

### Query::withoutNumericIndices

If your query string is created with `http_build_query` or the `Query::fromParams` named constructor chances are that numeric indices have been added by the method.

The `Query::withoutNumericIndices` removes any numeric index found in the query string as shown below:

~~~php
$query = Query::fromVariable(['foo' => ['bar', 'baz']]);
echo $query->value(); //return 'foo[0]=bar&foo[1]=baz'
$new_query = $query->withoutNumericIndices();
echo $new_query->value(); //return 'foo[]=bar&foo[]=baz'
//of note both objects returns the same PHP's variables but differs regarding the pairs
$query->parameters(); //return ['foo' => ['bar', 'baz']]
$new_query->parameters(); //return ['foo' => ['bar', 'baz']]
~~~

## Using the Query as a collection of query pairs

This class mainly represents the query string as a collection of key/value pairs.

~~~php
public static Query::fromPairs(iterable $pairs, string $separator = '&'): self
public Query::count(): int
public Query::getIterator(): iterable
public Query::pairs(): iterable
public Query::has(string $key): bool
public Query::hasPair(string $key, ?string $value): bool
public Query::get(string $key): ?string
public Query::getAll(string $key): array
public Query::withPair(string $key, $value): self
public Query::withoutDuplicates(): self
public Query::withoutEmptyPairs(): self
public Query::withoutPairByKey(string ...$keys): self
public Query::withoutPairByValue(?string ...$values): self
public Query::withoutPairByKeyValue(string $key, ?string $value): self
public Query::appendTo(string $key, $value): self
~~~

### Query::fromPairs

~~~php
$pairs = QueryString::parse('foo=bar&bar=baz%20bar', '&', PHP_QUERY_RFC3986);
$query = Query::fromPairs($pairs, '|');

echo $query->value(); // returns 'foo=bar|bar=baz%20bar'
~~~

The `$pairs` input must an iterable which exposes the same structure as `QueryString::parse` return type structure.

Returns a new `Query` object from an `array` or a `Traversable` object.

* `$pairs` : The submitted data must be an `array` or a `Traversable` key/value structure similar to the result of [Query::parse](#parsing-the-query-string-into-an-array).
* `$separator` : The query string separator used for string representation, by default, equals to `&`;

#### Examples

~~~php
$query =  Query::fromPairs([
    ['foo', 'bar'],
    ['p', 'yolo'],
    ['z', ''],
]);
echo $query; //display 'foo=bar&p=yolo&z='

$query =  Query::fromPairs([
    ['foo', 'bar'],
    ['p', null],
    ['z', ''],
]);
echo $query; //display 'foo=bar&p&z='
~~~

### Countable and IteratorAggregate

The class implements PHP's `Countable` and `IteratorAggregate` interfaces. This means that you can count the number of pairs and use the `foreach` construct to iterate over them.

~~~php
$query = new Query::fromRFC1738('foo=bar&p=y+olo&z=');
count($query); //return 3
foreach ($query as $pair) {
    //first round 
    // $pair = ['foo', 'bar']
    //second round
    // $pair = ['p', 'y olo']
}
~~~

<p class="message-info">When looping the key and the value are decoded.</p>

### Query::pairs

The `Query::pairs` method returns an iterator which enable iterating over each pair where the offset represent the pair name
 while the value represent the pair value.

~~~php
$query = Query::fromRFC3986('foo=bar&foo=BAZ&p=y+olo&z=');
foreach ($query->pairs() as $name => $value) {
    //first round 
    // $name = 'foo' and $value = 'bar'
    //second round
    // $name = 'foo' and $value = 'BAZ'
}
~~~

<p class="message-info">The returned iterable contains decoded data.</p>

### Query::has and Query::hasPair

Because a query pair value can be `null` the `Query::has` method is used to remove the possible `Query::get` result ambiguity.

~~~php
$query = Query::fromRFC3986('foo=bar&p&z=');
$query->getPair('foo');   //return 'bar'
$query->getPair('p');     //return null
$query->getPair('gweta'); //return null

$query->has('gweta'); //return false
$query->has('p');     //return true
~~~

`Query::has` can take a variable list of keys to validate that they are **all** pesent in the query.

~~~php
$query = Query::fromRFC3986('foo=bar&p&z=');
$query->has('foo', 'p', 'z');   //return true
$query->has('foo', 'p', 'x');   //return false
~~~

<p class="message-notice">since version <code>7.3.0</code></p>

If you are seeking the presence of a specific pair you may include the pair value in your search using `Query::hasPair`.

~~~php
$query = Query::fromRFC3986('foo=bar&p&z=');
$query->hasPair('foo', 'p');  //return false
$query->has('foo', 'bar');    //return true
~~~

### Query::get

If you are only interested in a given pair you can access it directly using the `Query::get` method as show below:

~~~php
$query = Query::fromRFC3986('foo=bar&foo=BAZ&p=y+olo&z=');
$query->get('foo');   //return 'bar'
$query->get('gweta');  //return null
~~~

The method returns the first value of a specific pair key as explained in the WHATWG documentation. If the key does not exist `null` will be returned.

<p class="message-info">The returned data are fully decoded.</p>

### Query::getAll

This method will return all the value associated with its submitted `$name`.

~~~php
$query = Query::fromRFC3986('foo=bar&foo=BAZ&p=y+olo&z=');
$query->getAll('foo');   //return ['bar', 'BAZ']
$query->getAll('gweta');  //return null
~~~

### Query::withoutPairByKey, Query::withoutPairByValue, Query::withoutPairByKeyAndValue

<p class="message-notice">since version <code>7.3.0</code></p>

`Query::withoutPairByKey` returns a new `Query` object with deleted pairs according to their keys.
`Query::withoutPairByValue` does similar but delete the pairs according to their values. Last
but not least `Query::withoutPairByKeyAndValue` does the deletion depending on the presence of 
the pair key and value.

`Query::withoutPairByKey` and `Query::withoutPairByValue` expect a variable list of key or value to 
be removed as its sole arguments. `Query::withoutPairByKeyAndValue` on the other hand expect two (2)
parameter the pair's key and value.

~~~php
$query = Query::fromRFC3986('foo=bar&p=y+olo&z=');
echo $query->withoutPairByKey('foo', 'p')->toString();  //displays 'z='
echo $query->withoutPairByValue('bar')->toString();  //displays 'p=y+olo&z='
echo $query->withoutPairByKeyAndValue('p', 'y+olo')->toString();  //displays 'foo=bar&z='
~~~

### Query::withoutEmptyPairs

`Query::withoutEmptyPairs` returns a new `Query` object with deleted empty pairs. A pair is considered empty if its key equals the empty string and its value is `null`.

~~~php
$query = Query::fromRFC3986('&&=toto&&&&=&');
$newQuery = $query->withoutEmptyPairs();
echo $query; //displays '&&=toto&&&&=&'
echo $newQuery; //displays '=toto&='
~~~
