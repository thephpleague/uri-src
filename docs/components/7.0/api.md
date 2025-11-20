---
layout: default
title: URI components Common API
description: An unified API to access all URI components 
---

Common API
=======

The toolkit provides at the same time a unified way to access all URI
components while exposing more specific methods to regularly used components like
URI queries, URI domains and URI paths.

## Instantiation

To start, each URI component object can be instantiated from:

- its value without its delimiter;
- a URI or;
- an authority, if the component is part of the authority string;

~~~php
use League\Uri\Components\Host;
use League\Uri\Components\Path;
use League\Uri\Components\Port;
use League\Uri\Components\Query;

$uri = 'http://EXamPLe.com?q=value#fragment';

Query::new('q=value')->value();              // displays 'q=value'
Host::fromUri($uri)->value();                // displays 'example.com'
Port::fromAuthority('example.com')->value(); // displays null
Path::new()->value();                        // displays ''
~~~ 

## String Representations

Because URI components can be formatted differently depending on the context, each object exposes
different representations:

~~~php
use League\Uri\Components\Scheme;

$uri = 'HtTp://john@bébé.be:23#fragment';

$scheme = Scheme::fromUri($uri);
echo $scheme->value();           //displays 'http'
echo $scheme->toString();        //displays 'http'
echo $scheme->getUriComponent(); //displays 'http:'
~~~

The `value()` method returns the normalized and RFC3986 encoded string version of the component or `null` if not value exists
while the `getUriComponent()` returns the URI component value cast as a string with its optional delimiter if it exists.

To allow better interoperability, all objects implements PHP's `Stringable` and `JsonSerializable` interface and provide
an explicit `toString()` method to cast the URI component to a string.

<p class="message-info">Normalization and encoding are component specific.</p>

## Equivalence

<p class="message-notice">The <code>equals()</code> method is available since version <code>7.6.0</code></p>

Equivalence between two components is based on the `getUriComponent()` method for each component.

~~~php
use League\Uri\Components\Domain;
use League\Uri\Components\Query;

$uri = 'HtTp://john@bébé.be:23#fragment';

$host = Domain::fromUri($uri);
echo $host->value();           //displays 'xn--bb-bjab.be'
echo $host->getUriComponent(); //displays 'xn--bb-bjab.be'

$query = Query::fromUri($uri);
$host->equals($query);           // returns false
$host->equals('bébé.be');        // returns true
$host->equals('xn--bb-bjab.be'); // returns true
~~~

## Conditional Builder

<p class="message-notice">The <code>when</code> method is available since version <code>7.6.0</code></p>
To ease building components, the `when` method is added to all components to conditionally create your component.

```php
use League\Uri\Components\Query;

$foo = '';
echo Query::fromUri('https://uri.thephpleague.com/components/7.0/modifiers/')
    ->when(
        '' !== $foo, 
        fn (Query $query) => $query->withPair(['foo', $foo]),  //on true
        fn (Query $query) => $query->withPair(['bar', 'baz']), //on false
    )
    ->getUriComponent();
// returns '?bar=baz';
```

## Available components

Because each component modification is specific, there is no generic way of changing
the component content. However, the package provides the following URI
component objects with modifying capabilities.

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<div>
<p class="font-bold text-blue-700">Query components</p>
<ul>
  <li><a href="/components/7.0/query/">Query</a></li>
  <li><a href="/components/7.0/urlsearchparams/">URLSearchParams</a></li>
</ul>
</div>
<div>
<p class="font-bold text-blue-700">Host components</p>
<ul>
  <li><a href="/components/7.0/host/">Host</a></li>
  <li><a href="/components/7.0/domain/">Domain</a></li>
</ul>
</div>
<div>
<p class="font-bold text-blue-700">Path components</p>
<ul>
  <li><a href="/components/7.0/path/">Path</a></li>
  <li><a href="/components/7.0/hierarchical-path/">HierarchicalPath</a></li>
  <li><a href="/components/7.0/data-path/">DataPath</a></li>
</ul>
</div>
<div>
<p class="font-bold text-blue-700">Fragment components</p>
<ul>
  <li><a href="/components/7.0/fragment/">Fragment</a></li>
  <li><a href="/components/7.0/fragment-directives/">Fragment Directives</a></li>
</ul>
</div>
<div>
<p class="font-bold text-blue-700">Other components</p>
<ul>
    <li><a href="/components/7.0/scheme/">Scheme</a></li>
    <li><a href="/components/7.0/userinfo/">UserInfo</a></li>
    <li><a href="/components/7.0/authority/">Authority</a></li>
    <li><a href="/components/7.0/port/">Port</a></li>
</ul>
</div>
</div>
