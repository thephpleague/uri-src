---
layout: default
title: URI components
---

Components common API
=======

Each URI component object can be instantiated from a URI object using the `fromUri` named constructor.

~~~php
public static function UriComponent::fromUri(Stringable|string $uri): UriComponentInterface;
~~~

This method accepts a single `$uri` parameter which represent a URI:

- as an object implementing the `Stringable` interface or
- as a string.

In both case, the URI is expected to be RFC3986 compliant.

~~~php
use League\Uri\Components\Host;
use League\Uri\Components\Path;
use League\Uri\Components\Port;
use League\Uri\Components\Query;

$uri = 'http://example.com?q=value#fragment';
$host = Host::fromUri($uri)->value();   //displays 'example.com'
$query = Query::fromUri($uri)->value(); //displays 'q=value'
$port = Port::fromInt($uri)->value();   //displays null
$path = Path::fromUri($uri)->value();   //displays ''
~~~ 

Once instantiated, all URI component objects expose the following methods.

~~~php
public function UriComponent::value(): ?string;
public function UriComponent::toString(): string;
public function UriComponent::getUriComponent(): string;
public function UriComponent::__toString(): string;
public function UriComponent::jsonSerialize(): ?string;
~~~

Which will lead to the following results:

~~~php
$scheme = Scheme::new('HtTp');
echo $scheme; //displays 'http'
echo $scheme->getUriComponent(); //displays 'http:'

$userinfo = new UserInfo('john');
echo $userinfo->toString();  //displays 'john'
echo $userinfo->getUriComponent(); //displays 'john@'

$host = Host::new('bébé.be');
echo $host; //displays 'xn--bb-bjab.be'
echo $host->value(); //displays 'xn--bb-bjab.be'

$query = Query::new();
echo $query; //displays ''
echo $query->value(); //displays null

$port = Port::new(23);
echo $port->value(); //displays '23';
~~~

- `value` returns the normalized and RFC3986 encoded string version of the component or `null` if not value exists.
- `toString` returns the normalized and RFC3986 encoded string version of the component or  the empty strin if not value exists.
- `getUriComponent` returns the same output as `toString` with the component optional delimiter if it exists.
- `__toString` returns the same value as `toString`
- `jsonSerialize` returns the normalized and RFC1738 encoded string version of the component for better interoperability with JavaScript URL standard.

<p class="message-notice">Normalization and encoding are component specific.</p>

## Modifying URI component object

Because each component modification is specific there is no generic way of changing the component content.

List of URI component objects
--------

The following URI component objects are defined (order alphabetically):

- [Authority](/components/7.0/authority/) : the Data Path component
- [DataPath](/components/7.0/path/data/) : the Data Path component
- [Domain](/components/7.0/host/domain/) : the Host component
- [Fragment](/components/7.0/fragment/) : the Fragment component
- [HierarchicalPath](/components/7.0/path/segmented/) : the Segmented Path component
- [Host](/components/7.0/host/) : the Host component
- [Path](/components/7.0/path/) : the generic Path component
- [Port](/components/7.0/port/) : the Port component
- [Query](/components/7.0/query/) : the Query component
- [Scheme](/components/7.0/scheme/) : the Scheme component
- [UserInfo](/components/7.0/userinfo/) : the User Info component
