---
layout: default
title: The Query component
description: The Query class ease query string creation and manipulation.
---

The Query
=======

The library provides a `League\Uri\Components\Query` class to ease query string creation and manipulation. This URI component object exposes the [package common API](/components/7.0/), but also provide specific methods to work with the URI query component.

<p class="message-notice">If the modifications do not change the current object, it is returned as is, otherwise, a new modified object is returned.</p>

<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Exceptions\SyntaxError</code> exception is thrown.</p>

## Instantiation

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

## Query separator

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

## String Representations

In addition to the common methods from the [package common API](/components/7.0/), the following methods are available.

### RFC3986 Representation

The `Query` object can return the query encoded using the [RFC3986](https://tools.ietf.org/html/rfc3986#section-3.4) query component rules

~~~php
$query = Query::fromRFC1738('foo=bar&bar=baz+bar%2A', '&');
$query->toRFC3986();  //returns 'foo=bar&bar=baz%20bar%2A'
$query->value();     //returns 'foo=bar&bar=baz%20bar%2A'
~~~

If the query is undefined, this method returns `null`.

<p class="message-info"><code>Query::toRFC3986()</code> is a alias of <code>Query::value()</code></p>

### RFC1738 Representation

The `Query` object returns the query encoded using the [RFC1738](https://tools.ietf.org/html/rfc1738) query component rules

~~~php
$query = Query::fromRFC3986('foo=bar&bar=baz%20bar', '&');
$query->toRFC1738(); // returns 'foo=bar&bar=baz+bar'
$query->jsonSerialize(); //returns 'foo=bar&bar=baz+bar'
~~~

If the query is undefined, this method returns `null`.

### FormData Representation

The `Query` object returns the query encoded using the [application/x-www-form-urlencoded](https://url.spec.whatwg.org/#urlencoded-parsing) query component rules

~~~php
$query = Query::fromRFC3986('foo=bar&bar=baz%20bar%2A', '&');
$query->toFormData(); // returns 'foo=bar&bar=baz+bar*'
$query->jsonSerialize(); //returns 'foo=bar&bar=baz+bar*'
~~~

If the query is undefined, this method returns `null`.

<p class="message-info"><code>Query::jsonSerialize()</code> is an alias of <code>Query::toFormData()</code> to improve interoperability with JavaScript.</p>

## Modifying the Query

`Query::merge` returns a new `Query` object with its data merged.

~~~php
<?php

public Query::merge($query): Query
~~~

This method expects a single argument which is a string

~~~php
$query = Query::fromRFC3986('foo=bar&baz=toto');
$newQuery = $query->merge('foo=jane&r=stone');
$newQuery->__toString(); //return foo=jane&baz=toto&r=stone
// the 'foo' parameter was updated
// the 'r' parameter was added
~~~

<p class="message-info">Values equal to <code>null</code> or the empty string are merge differently.</p>

~~~php
$query = Query::fromRFC3986('foo=bar&baz=toto');
$newQuery = $query->merge('baz=&r');
$newQuery->__toString(); //return foo=bar&baz=&r
// the 'r' parameter was added without any value
// the 'baz' parameter was updated to an empty string and its = sign remains
~~~

`Query::append` returns a new `Query` object with its data append to it.

~~~php
public Query::append($query): Query
~~~

This method expects a single argument which is a string, a scalar or an object with the `__toString` method.

~~~php
$query    = Query::fromRFC3986('foo=bar&john=doe');
$newQuery = $query->append('foo=baz');
$newQuery->__toString(); //return foo=bar&foo=baz&john=doe
// a new foo parameter is added
~~~

<p class="message-info">Available since version <code>7.6.0</code></p>

This method expects a single argument which is a string, a scalar or an object with the `__toString` method.

~~~php
$query    = Query::fromRFC3986('foo=bar&john=doe');
$newQuery = $query->prepend('foo=baz');
$newQuery->__toString(); //return foo=baz&foo=bar&john=doe
// a new foo parameter is added
~~~

`Query::sort` returns a `Query` object with its pairs sorted according to its keys. Sorting is done so
that parsing stayed unchanged before and after processing the query.

~~~php
$query    = Query::fromRFC3986('foo=bar&baz=toto&foo=toto');
$newQuery = $query->sort();
$newQuery->__toString(); //return baz=toto&foo=bar&foo=toto
~~~

<p class="message-notice">since version <code>7.3.0</code>, the sorting algorithm has been updated to match <a href="https://url.spec.whatwg.org/#dom-urlsearchparams-sort">WHATG group specification</a></p>

## PHP Data Transporter

Historically, the query string has been used as a data transport layer for PHP variables.
The `Query` class can be seen as a PHP Data Transporter layer and a public API was built
around this concept.

~~~php
public static Query::fromVariable($params, string $separator = '&', QueryBuildingMode $queryBuildingMode = QueryBuildingMode::Native): self
public Query::parameters(): array
public Query::parameter(string $name): mixed
public Query::hasParameter(string ...$name): bool
public Query::withoutNumericIndices(): self
public Query::withoutParameters(...string $offsets): self
public Query::mergeParameters(array $data): self
public Query::replaceParameter(string $name, mixed $value): self;
~~~

<p class="message-info"><code>Query::fromVariable</code> replaced the deprecated 
<code>Query::fromParameters</code> named constructor which was 
not inconsistent against <code>http_build_query</code> algorithm.</p>

### Instantiation

The `fromVariable` uses PHP own data structure to generate a query string *à la* `http_build_query`.

~~~php
parse_str('foo=bar&bar=baz+bar', $params);

$query = Query::fromVariable($params, '|');
echo $query->value(); // returns 'foo=bar|bar=baz%20bar'
~~~

<p class="message-info">The <code>$params</code> input can be any argument type supported by <code>http_build_query</code> which means that it can be an <code>array</code> or an <code>object</code>.</p>
<p class="message-notice">If you want a better parsing you can use the <a href="/components/7.0/query-parser-builder/">QueryString</a> class.</p>

### Accessing Parameters

If you already have an instantiated `Query` object you can return all the query string deserialized 
arguments using the `Query::parameters` method:

~~~php
$query_string = 'foo.bar=bar&foo_bar=baz';
parse_str($query_string, $out);
var_export($out);
// $out = ["foo_bar" => 'baz'];

$arr = Query::fromRFC3986($query_string)->parameters();
// $arr = ['foo.bar' => 'bar', 'foo_bar' => baz']];
~~~

As you can see from the previous example, the data is not mangle hence using the `Query` object you
get two distinctive properties instead of one, using PHP functions.

If you are only interested in a given argument, you can access it directly by supplyling the argument
name as show below:

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

### Modifying Parameters

<p class="message-warning">Whenever the parameters are modified, a new instance is built.
Since the parameters are parsed and build following PHP's own algorithm, some key/value
may not preserve, even if they are not directly involved in the modification process.</p>

#### Query::mergeParameters

<p class="message-notice">Available since <code>7.6</code></p>

This `mergeParameters()` method allows merging new parameters to your query using the `http_build_query` algorithm.
The method expects an `array` or an `object` and you can optionally provide a `$prefix` like with the `http_build_query`.
The new data will be merged with the current available PHP parameters.

~~~php
$query = Query::fromRFC3986('foo[]=bar&foo[]=y+olo&z=');
$newQuery = $query->mergeParameters(['foo' => ['when' => 'today', 'where' => 'here']]);
$newQuery->params('foo'); //return ['when' => 'today', 'where' => 'here']
echo $newQuery->value();  //return 'foo%5Bwhen%5D=today&foo%5Bwhere%5D=here&z='
~~~

#### Query::replaceParameter

<p class="message-notice">Available since <code>7.6</code></p>

This `replaceParameter()` method allows changing a single parameter from your query using
the `http_build_query` algorithm. The method expects a parameter string as a name and its
new value. The data will replace the value in the current query.

~~~php
$query = Query::fromRFC3986('foo[]=bar&foo[]=y+olo&z=');
$newQuery = $query->replaceParameter('foo', 1);
$newQuery->params('foo'); //return 1
echo $newQuery->value();  //return 'foo=1&z='
~~~

<p class="message-warning">If the name does not exist, a <code>ValueError</code> will be thrown</p>

#### Query::withoutParameters

If you want to remove PHP's variable from the query string you can use the `Query::withoutParameters`
method as shown below

~~~php
$query = Query::fromRFC3986('foo[]=bar&foo[]=y+olo&z=');
$new_query = $query->withoutParameters('foo');
$new_query->params('foo'); //return null
echo $new_query->value(); //return 'z='
~~~

<p class="message-info">This method takes a variadic arguments representing the keys to be removed.</p>

#### Query::withoutNumericIndices

If your query string is created with `http_build_query` or the `Query::fromVariable` named constructor chances are that numeric indices have been added by the method.

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

<p class="message-warning">While PHP exclusively uses this algorithm. It is not recommended for
interoperability as it destroys or skips key and/or values. Consider using the
<code>Query Collection</code> approach instead.</p>

## Query Collection

To better support interoperability, the class also represents a query string as an ordered
collection of key/value pairs, while also providing additional APIs to work with parameters
that are parsed as lists using PHP’s bracket notation.

~~~php
public static Query::fromPairs(iterable $pairs, string $separator = '&'): self
public Query::count(): int
public Query::getIterator(): iterable
public Query::pairs(): iterable
public Query::has(string $key): bool
public Query::hasPair(string $key, ?string $value): bool
public Query::get(string $key): ?string
public Query::getAll(string $key): array
public Query::indexOf(string $key, $nth = 0): ?int
public Query::pair(int $offset): ?string
public Query::keyAt(int $offset): string
public Query::valueAt(int $offset): ?string
public Query::withPair(string $key, $value): self
public Query::withoutDuplicates(): self
public Query::withoutEmptyPairs(): self
public Query::withoutPairByKey(string ...$keys): self
public Query::withoutPairByValue(?string ...$values): self
public Query::withoutPairByKeyValue(string $key, ?string $value): self
public Query::appendTo(string $key, $value): self
public Query::getList(string $name): array
public Query::hasList(string ...$names): bool
public Query::appendList(string $name, array $value): bool
public Query::withList(string $name, array $value): bool
public Query::withoutList(string ...$names): bool
~~~

### Instantiation

~~~php
$pairs = QueryString::parse('foo=bar&bar=baz%20bar', '&', PHP_QUERY_RFC3986);
$query = Query::fromPairs($pairs, '|');

echo $query->value(); // returns 'foo=bar|bar=baz%20bar'
~~~

The `$pairs` input must an iterable which exposes the same structure as `QueryString::parse` return type structure.

Returns a new `Query` object from an `array` or a `Traversable` object.

* `$pairs` : The submitted data must be an `array` or a `Traversable` key/value structure similar to the result of [Query::parse](#parsing-the-query-string-into-an-array).
* `$separator` : The query string separator used for string representation, by default, equals to `&`;

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

### Accessing Pairs

A pair is an array containing 2 element, the first element represents the key is always a string.
The second elements and represent the pair value, it can be a string or `null`.

#### Using Pair Position

The `Query::pair` method returns the key/pair value at a given position in the query string.
The method supports negative offset. But if no value is found, an `OutOfBoundException` is triggered.

~~~php
$query = Query::fromRFC3986('foo=bar&foo=BAZ&p=y+olo&z=');
$query->pair(2);  // returns ['p', 'y+olo'];
$query->pair(-1); // returns ['z', ''];
$query->pair(42); // throws OutOfBoundException
~~~

To find a key/value pair specific position you can rely on 2 methods `indexOf` and `indexOfValue`.

`Query::indexOf` returns the key/value pair offset within the query. By default, if the key
appears multiple times, the method returns the offset of its first occurrence. You can
retrieve subsequent occurrences by specifying the desired index using the optional `nth`
parameter. The method accepts negative offset.

~~~php
$query = Query::fromRFC3986('foo=bar&p=y+olo&z=&foo=jazz');
$query->indexOf('foo');         //returns 0
$query->indexOf('foo', 1);      //returns 3
$query->indexOf('foo', -1);     //returns 3
$query->indexOf('unknown', -1); //returns null
~~~

The same is true for `Query::indexOfValue` but its first argument is the pair value instead of the
pair key.

~~~php
$query = Query::fromRFC3986('foo=bar&p=y+olo&z=bar&foo=jazz');
$query->indexOfValue('bar');     //returns 0
$query->indexOfValue('bar', 1);  //returns 2
$query->indexOfValue('bar', -1); //returns 2
$query->indexOfValue('foo', -1); //returns null
~~~

<p class="message-info">For both methods, the occurrence is design with a <code>zero-based indexing</code> so the first
occurrence is <code>0</code> instead of <code>1</code></p>

If you are only interested in the pair key or value at a specific position you can use the
`Query::keyAt()` and `Query::valueAt()` methods to return the key or the value of the pair
at the specified offset. The methods support negative offset but if the offset does not exist
an `OutOfBoundException` is thrown.

```php
$query = Query::fromRFC3986('foo=bar&foo=BAZ&p=y+olo&z=');
$query->valueAt(0);  // return 'bar'
$query->keyAt(2);    // return 'p'
$query->valueAt(42); // throws OutOfBoundException
```

#### Using Pair Key/Value

If you are only interested in a given pair you can access it directly using
the `Query::get` method as show below:

~~~php
$query = Query::fromRFC3986('foo=bar&foo=BAZ&p=y+olo&z=');
$query->get('foo');   //return 'bar'
$query->get('gweta');  //return null
~~~

The method returns the first value of a specific pair key as explained in
the WHATWG documentation. If the key does not exist `null` will be returned.

<p class="message-info">The returned data are fully decoded.</p>

Since version `7.7.0`, `Query::get()` is an alias of `Query::first()`, `Query::last()` is the 
complementary method which returns the last value of a specific pair key as explained.
If the key does not exist `null` will be returned. This value is more in line with what PHP
developer expects when using `_GET` or `parse_str` on non-array like value.

~~~php
$query = Query::fromRFC3986('foo=bar&foo=BAZ&p=y+olo&z=');
$query->first('foo');    // return 'bar'
$query->last('foo');     // return 'BAZ'

parse_str('foo=bar&foo=BAZ&p=y+olo&z=', $arr);
$arr['foo']; // return 'BAZ'
~~~

Last but not least the `Query::getAll` method returns all the values associated with its
submitted `$key`.

~~~php
$query = Query::fromRFC3986('foo=bar&foo=BAZ&p=y+olo&z=');
$query->getAll('foo');   //return ['bar', 'BAZ']
$query->getAll('gweta');  //return null
~~~

<p class="message-info">The returned data are fully decoded.</p>

Because a query pair value can be `null` the `Query::has` method is used to remove the possible result ambiguity
when using `Query::get`, `Query::first` or `Query::last`

~~~php
$query = Query::fromRFC3986('foo=bar&p&z=');
$query->get('foo');   //return 'bar'
$query->get('p');     //return null
$query->get('gweta'); //return null

$query->has('gweta'); //return false
$query->has('p');     //return true
~~~

`Query::has` can take a variable list of keys to validate that they are **all** present in the query.

~~~php
$query = Query::fromRFC3986('foo=bar&p&z=');
$query->has('foo', 'p', 'z');   //return true
$query->has('foo', 'p', 'x');   //return false
~~~

If you are seeking the presence of a specific pair you may include the pair value in your search using `Query::hasPair`.

~~~php
$query = Query::fromRFC3986('foo=bar&p&z=');
$query->hasPair('foo', 'bar');  //return true
$query->hasPair('foo', 'p');    //return false
$query->has('foo', 'p');        //return true
~~~

#### Iterations and count

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

The `Query::pairs` method returns a Generator which enable iterating over each pair
where the offset represent the pair name while the value represent the pair value.

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

### Modifying Pairs

#### Adding Pairs

The `Query::withPair` method will add a new key/value pair to the string:

- if no pair exists with the same name the pair is appended at the end of the pair list;
- if they are already presents, the value will be updated on the first occurrence and all the other pairs with the same key will be removed.

~~~php
Query::new('foo=bar&foo=BAZ&p=y+olo&z=')->withPair('foo', 'new')->toString(); //return 'foo=new&p=y+olo&z='
Query::new('p=y+olo&z=')->withPair('foo', 'new')->toString();  //return p=y+olo&z=&foo=new
~~~

The `Query::appendTo` method will append a new pair to the key/pair list regardless of the presence
of other pairs sharing the same key.

~~~php
Query::new('foo=bar&p=y+olo&z=')->appendTo('foo', 'new')->toString(); 
//return 'foo=bar&p=y+olo&z=&foo=new'
~~~

#### Removing Pairs

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

`Query::withoutEmptyPairs` returns a new `Query` object with deleted empty pairs. A pair is considered empty if its key equals the empty string and its value is `null`.

~~~php
$query = Query::fromRFC3986('&&=toto&&&&=&');
$newQuery = $query->withoutEmptyPairs();
echo $query; //displays '&&=toto&&&&=&'
echo $newQuery; //displays '=toto&='
~~~

#### Replacing Pairs

`Query::replace` replaces a key/value pair at a specific offset. The `$offset` parameter is zero-based: `0` refers to the first pair, `1` to the second, and so on.
Negative offsets are also supported and count from the end (`-1` targets the last pair).
If the given offset does not exist, a `ValueError` is thrown.
If the new pair is identical to the existing one, the current instance is returned unchanged.

~~~php
$query = Query::fromRFC3986('foo=bar&p=y+olo&z=&foo=jazz');
$query->replace(1, 'toto', 'foobar')->toString(); 
//returns 'foo=bar&toto=foobar&z=&foo=jazz'
~~~

### Handling List

<p class="message-notice">Available since version <code>7.8.0</code></p>

PHP relies on the presence of brackets in parameter names to parse query string values into lists.
Since this notation is well established in the PHP community, this library provides a dedicated API
to work with list parameters while still preserving the original key/value pairs.

<p class="message-info">The API operates on the parsed parameter view of the query, not on raw query pairs.</p>

#### Accessing List

The `hasList()` method tells whether a parameter exists as a structured list in the parsed
representation of the query string. (ie the key string should contains open and closes bracket as
per `parse_str` algorithm).

```php
$query = Query::fromUri('http://example.com/?b=2&a[]=1&a[]=2&a[]=3');

$query->hasList('a'); // true
$query->hasList('b'); // false
```
The parameter `a` is written using bracket notation (`a[]`), after parsing using
PHP algorithm, an array is generated. On the otherhand the `b` parameter does not
exist as an array, it is a scalar value so `hasList` returns false.

The `getList()` method returns the values of a parameter **only when that parameter is represented as a list**
in the parsed form of the query string (i.e. using PHP bracket notation).

Scalar occurrences of the same parameter name are **not** included in the result.

```php
$query = Query::fromUri('http://example.com/?b=2&a[foo]=1&a[]=2&a=not-present');

$query->getList('a');   // ['foo' => '1', '0' => '2']
$query->getAll('a');    // ['not-present']
$query->getAll('b');    // [0 => '2']
$query->getAll('a[]');  // [0 => '2']
$query->parameter('a'); // 'not-present' (the array got overwritten by the last pair)
```

The example above shows how `getList` works and differs from `getAll` and `parameter`.

- `getList('a')` returns only the values produced by bracketed parameters (`a[foo]`, `a[]`)
- `getAll('a')` returns only scalar occurrences of `a`
- `parameter('a')` follows PHP's `parse_str` resolution and returns the last scalar value

This illustrates how `getList()` provides structured access to list-style parameters while
preserving the underlying key/value pair model.

#### Modifying Lists

The `Query::withList` method adds or replaces a list parameter in the query string.

- If no list exists with the given name, the list is appended at the end of the pair collection.
- If one or more lists already exist with the same name, the first occurrence is replaced and all other lists with that name are removed.

~~~php
Query::new('foo[]=bar&p=y+olo&z=&foo[]=BAZ')
    ->withList('foo', ['qux' => 'quux'])
    ->toString();
//return foo%5Bqux%5D=quux&p=y%2Bolo&z=

Query::new('p=y+olo&z=')
    ->withList('foo', ['qux' => 'quux'])
    ->toString(); 
//return p=y%2Bolo&z=&foo%5Bqux%5D=quux
~~~

The `Query::appendList()` method always appends a new list parameter, regardless of whether other lists with the same name already exist.

~~~php
Query::new('foo[]=bar&p=y+olo&z=&foo[]=BAZ')
    ->appendList('foo', ['qux' => 'quux'])
    ->toString(); 
//return 'foo%5B%5D=bar&p=y%2Bolo&z=&foo%5B%5D=BAZ&foo%5Bqux%5D=quux'
~~~

#### Removing Lists

The `Query::withoutList()` method removes all list parameters with the given names from the query string.

Only parameters represented using bracket notation are affected.
Scalar parameters with the same name **are preserved**.

~~~php
Query::new('foo[]=bar&p=y+olo&z=&foo[qux]=quux&foo=scalar')
    ->withoutList('foo')
    ->toString();
// returns 'p=y%2Bolo&z=&foo=scalar'
~~~

The `Query::withoutLists` removes **all list-based parameters** from the query string.

~~~php
Query::new('a=1&a[]=2&b=3&b[]=4')
    ->withoutLists()
    ->toString();
// returns a=1&b=3
~~~

The `Query::onlyLists` removes **all scalar parameters** and keeps only lists.

~~~php
Query::new('a=1&a[]=2&b=3&b[]=4')
    ->onlyLists()
    ->toString();
// returns a%5B%5D=2&b%5B%5D=4
~~~
