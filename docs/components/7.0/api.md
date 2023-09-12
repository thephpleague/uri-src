---
layout: default
title: URI components Common API
---

Common API
=======

The League URI components provides at the same time a unified way to access all URI
components while exposing more specific methods to regularly used components like
URI queries, URI domains and URI paths.

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

Because URI component car be formatted differently depending on the context, each objects exposes
different representations:

~~~php
use League\Uri\Components\Domain;
use League\Uri\Components\Path;
use League\Uri\Components\Port;
use League\Uri\Components\Query;
use League\Uri\Components\Scheme;
use League\Uri\Components\UserInfo;

$uri = 'HtTp://john@bébé.be:23#fragment';

$scheme = Scheme::fromUri($uri);
echo $scheme->value();           //displays 'http'
echo $scheme->getUriComponent(); //displays 'http:'

$userinfo = UserInfo::fromUri('john');
echo $userinfo->value();           //displays 'john'
echo $userinfo->getUriComponent(); //displays 'john@'

$host = Domain::fromUri($uri);
echo $host->value();           //displays 'xn--bb-bjab.be'
echo $host->getUriComponent(); //displays 'xn--bb-bjab.be'

$path = Path::fromUri($uri);
echo $path->value();           //displays ''
echo $path->getUriComponent(); //displays ''

$query = Query::fromUri($uri);
echo $query->value();           //displays null
echo $query->getUriComponent(); //displays ''

$port = Port::fromUri($uri);
echo $port->value();           //displays '23'
echo $port->getUriComponent(); //displays ':23'
~~~

The `value()` method returns the normalized and RFC3986 encoded string version of the component or `null` if not value exists
while the `getUriComponent()` returns the URI component value cast as a string with its optional delimiter if it exists.

To allow better interoperability all objects implements PHP's `Stringable` and `JsonSerializable` interface and provide
an explicit `toString()` method to cast the URI component to a string.

<p class="message-notice">Normalization and encoding are component specific.</p>
<p class="message-notice"><code>JsonSerializable</code> encoding <strong>may</strong> differ to improve interoperability with current specification.</p>

## List of URI component objects

Because each component modification is specific there is no generic way of changing
the component content. However, the package provides the following URI
component objects with modifying capabilities.

For Paths:

- [Path](/components/7.0/path/)
- [DataPath](/components/7.0/path/data/)
- [HierarchicalPath](/components/7.0/path/segmented/)

For Hosts:

- [Host](/components/7.0/host/)
- [Domain](/components/7.0/host/domain/)

For Queries:

- [Query](/components/7.0/query/)
- [URLSearchParams](/components/7.0/urlsearchparams/)

Other URI Components:

- [Scheme](/components/7.0/scheme/)
- [Fragment](/components/7.0/fragment/)
- [Port](/components/7.0/port/)
- [Authority](/components/7.0/authority/)
- [UserInfo](/components/7.0/userinfo/)
