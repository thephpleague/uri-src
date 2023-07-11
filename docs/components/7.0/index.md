---
layout: default
title: URI components
redirect_from:
    - /components/
---

Uri Components
=======

## Introduction

[![Latest Version](https://img.shields.io/github/release/thephpleague/uri-components.svg?style=flat-square)](https://github.com/thephpleague/uri-components/releases)

While working with URI, you may stumble on some tasks, such as parsing its query string or updating its host,
that are not covered by the URI package.
Thankfully, the URI component package allows you to easily parse, create, manipulate URI component as well as partially
update URIs. By using the package, your application can safely perform tasks around your URIs and provide a better 
user experience to your developers.

~~~php
use League\Uri\Components\Query;
use League\Uri\UriModifier;

$newUri = UriModifier::appendQuery('http://example.com?q=value#fragment', 'q=new.Value');
echo $newUri->toString(); // 'http://example.com?q=value&q=new.Value#fragment';

$query = Query::fromUri($newUri);
$query->get('q');    // returns 'value'
$query->getAll('q'); // returns ['value', 'new.Value']
$query->params('q'); // returns 'new.Value'
~~~

## Common API

The League URI components provides at the same time a unified way to access all URI components while exposing more
specific methods to regularly used components like URI queries, URI domains and URI paths.

To start, each URI component object can be instantiated from a URI object using the `fromUri` named constructor,
or from a RFC3986 compliant string.

~~~php
public static function UriComponent::fromUri(Stringable|string $uri): UriComponentInterface;
public static function UriComponent::fromAuthority(Stringable|string $authority): UriComponentInterface;
public static function UriComponent::new(Stringable|string|null $value = null): UriComponentInterface;
~~~

This `fromUri` method accepts a single `$uri` parameter which represent a URI:

- as an object implementing the `Stringable` interface or
- as a string.

This `fromAuthority` method accepts a single `$authority` parameter which represent a
URI authority component:

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

Last but not least, the `new` method accept at least a string or a stringable object.
Depending on the URI component, the method **MAY** also support 

- integers (ie: the `Port` component) and/or
- the `null` value (except for the `Path` component)

Once instantiated, all URI component objects expose the following methods.

~~~php
public function UriComponent::value(): ?string;
public function UriComponent::toString(): string;
public function UriComponent::getUriComponent(): string;
public function UriComponent::__toString(): string;
public function UriComponent::jsonSerialize(): ?string;
~~~

- `value` returns the normalized and RFC3986 encoded string version of the component or `null` if not value exists.
- `toString` returns the normalized and RFC3986 encoded string version of the component or  the empty strin if not value exists.
- `getUriComponent` returns the same output as `toString` with the component optional delimiter if it exists.
- `__toString` returns the same value as `toString`
- `jsonSerialize` returns the normalized and RFC1738 encoded string version of the component for better interoperability with JavaScript URL standard.

Which will lead to the following results:

~~~php
use League\Uri\Components\Scheme;
use League\Uri\Components\UserInfo;
use League\Uri\Components\Port;
use League\Uri\Components\Query;
use League\Uri\Components\Domain;

$uri = 'HtTp://john@bébé.be:23#fragment';

$scheme = Scheme::fromUri($uri);
echo $scheme; //displays 'http'
echo $scheme->getUriComponent(); //displays 'http:'

$userinfo = UserInfo::fromUri('john');
echo $userinfo->toString();  //displays 'john'
echo $userinfo->getUriComponent(); //displays 'john@'

$host = Domain::fromUri($uri);
echo $host; //displays 'xn--bb-bjab.be'
echo $host->value(); //displays 'xn--bb-bjab.be'

$query = Query::fromUri($uri);
echo $query; //displays ''
echo $query->value(); //displays null

$port = Port::fromUri($uri);
echo $port->value(); //displays '23';
~~~

<p class="message-notice">Normalization and encoding are component specific.</p>

## Modifying URI component object

Because each component modification is specific there is no generic way of changing the component content.

## List of URI component objects

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
