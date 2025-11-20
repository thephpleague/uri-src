---
layout: default
title: RFC8141 compliant URN Object API
description: The Urn object is a specific class developed around URN creation and manipulation as defined in RFC8141.
---

URN Value Object
=======

<p class="message-notice">Available since version <code>7.6.0</code></p>

The `League\Uri\Urn` is a specific class developed around URN creation and manipulation
as defined in [RFC8141](https://datatracker.ietf.org/doc/html/rfc8141).

<p class="message-notice">The class handles a subset of URI schemes as defined by <a href="https://datatracker.ietf.org/doc/html/rfc8141">RF8141</a>.
Because URNs have distinct requirements, the class is still considered generic, and additional
rules <strong>may</strong> apply depending on their respective NID.</p>

## Instantiation

While the default constructor cannot be used as it is marked as private, the `League\Uri\Urn` provides several
named constructors to help creating a new instance.

```php
use League\Uri\Urn;

$urn = Urn::new('urn:example:animal:nose?+foo=bar&fo%26o=b%3Far');
$urnBis = Urn::fromString('urn:example:animal:nose?+foo=bar&fo%26o=b%3Far');
$urnRfc2141 = Urn::fromRfc2141(nid: 'example', nss: 'animal:nose');
Urn::parse('invalid uri'); // returns null on failure
```

The `new()` method allows creating a new instance from an encoded string. The method is an alias of 
the `fromString()` method. By default, the submitted URNs will be validated against RFC8141.
While both methods support strings, you can also use the new native URI classes as input, as well as
`PSR-7` UriInterface implementing objects.

The `fromRfc2141()` named constructor allows creating URNs using the legacy [RFC2141](https://datatracker.ietf.org/doc/html/rfc2141) rules.

While all the previous methods would throw a `SyntaxError` on malformed URN, the `parse()` method returns `null`
to enable using the method during input validation.

## String Representations

The `Urn` class handles URN according to RFC8141, as such you can retrieve its string representation using
the `toString` method. the `__toString()` method is an alias of the `toString()` method.

```php
$urn = Urn::fromString('urn:example:animal:nose?+foo=bar&fo%26o=b%3Far');
echo $urn->toString(); // displays 'urn:example:animal:nose?+foo=bar&fo%26o=b%3Far'
echo $uri;             // displays 'urn:example:animal:nose?+foo=bar&fo%26o=b%3Far'
```

An `Urn` instance can be JSON-encoded using its string representation to allow better interoperability with
JSON supporting languages.

```php
$urn = Urn::fromString('urn:example:animal:nose?+foo=bar&fo%26o=b%3Far');
json_encode($urn); //returns "urn:example:animal:nose?+foo=bar&fo%26o=b%3Far"
```
The `toDisplayString()` method returns a human-readable representation of the URN,
corresponding to its IRI form as defined in RFC 3987. Although the resulting
value may not constitute a syntactically valid URN, it is intended for
presentation purposes — for example, as the textual content of an HTML `<a>` element.

```php
$urn = Urn::fromString('urn:example:%F0%9F%98%88');
echo $urn->toDisplayString(); // displays 'urn:example:😈'
```

The `toRfc2141()` returns the URN legacy representation from the obsolete `RFC2141` specification.

```php
$urn = Urn::fromString('urn:example:animal:nose?+foo=bar&fo%26o=b%3Far');
echo $urn->toString();  // displays 'urn:example:animal:nose?+foo=bar&fo%26o=b%3Far'
echo $urn->toRfc2141(); // displays 'urn:example:animal:nose'
```

The optional components as defined by RFC8141 are stripped if present.

## Accessing Properties

Let’s examine the result of building an URN:

```php
use League\Uri\Urn;

$urn = Urn::fromString('urn:example:animal:ferret:nose?+weight=2.3;length=5.1?=profile=standard#section2');
echo $urn->getNid();        // displays 'example'
echo $urn->getNss();        // displays 'animal:ferret:nose'
echo $urn->getRComponent(); // displays 'weight=2.3;length=5.1'
echo $urn->getQComponent(); // displays 'profile=standard'
echo $urn->getFComponent(); // displays 'section2'
```
<p class="message-notice">The returned value of each component is kept encoded.</p>

## URN Information

The `components` related properties `r-component`, `q-component` and `f-component` are optional, as such,
they can be `null` if they have no value or be a non-empty string. They can never be the empty string.

To ease gathering information about optional component presence the class exposes the following methods:

- `Urn::hasRComponent`: returns `true` if the r-component value is not `null`;
- `Urn::hasQComponent`: returns `true` if the q-component value is not `null`;
- `Urn::hasFComponent`: returns `true` if the f-component value is not `null`;
- `Urn::hasOptionalComponent`: returns `true` if at least one of the optional component is set;

```php
$urn = Urn::fromString('urn:example:animal:nose?+foo=bar&fo%26o=b%3Far');
$urn->hasOptionalComponent(); // returns true
$urn->hasFComponent();        // returns false
$urn->hasRComponent();        // returns true
```

## Modifying Properties

Use the modifying methods exposed by all URN instances to replace one of the URN part or component.
If the modifications do not alter the current object, it is returned as is, otherwise,
a new modified object is returned.

<p class="message-notice">Any modification method can trigger a <code>League\Uri\Contracts\UriException</code> exception
if the resulting URN is not valid. Just like with the instantiation methods, validation is NID-dependent.</p>

```php
$urn = Urn::fromString('urn:example:animal:nose')
            ->withQComponent('foo=bar')
            ->withFComponent('fragment');
echo $urn->toString(); // returns 'urn:example:animal:nose?=foo=bar#fragment'
```

The following modifier methods exist:

- `withNid` : will update the URN namespace identifier part;
- `withNss` : will update the URN namespace specific string part;
- `withRComponent` : will update the URN `r-component` value;
- `withQComponent` : will update the URN `q-component` value;
- `withFComponent` : will update the URN `f-component` value;

<p class="message-info">All methods will correctly encode your input before updating the URN.</p>
<p class="message-notice">To remove any of the component you can give to their respective wither methods
the <code>null</code> value. It will remove any non-empty string attached to the component if it exists.</p>

To ease building or modifying the instance, the `when()` method is added to conditionally create your component.

```php
echo Urn::fromString('urn:example:animal:nose?+foo=bar')
    ->when(
        fn (Urn $urn): bool => $urn->getNid() === 'example',
        fn (Urn $urn): Urn => $urn->withRComponent(null),
    )
    ->toString();
// returns 'urn:example:animal:nose'
```

## Normalization

Out-of-the-box, the only normalization that will occur it that the scheme will be lowercased to `urn`. But
you can improve normalization by lowercasing the URN NIS part. This is done if you call the `normalize()` method.
It will return a new instance fully normalized. This instance is used to compare URN.

```php
use League\Uri\Urn;
use League\Uri\UrnComparisonMode;

$urn = Urn::fromString('UrN:Example:Animal:NOSE');
echo $urn; //returns "urn:Example:Animal:NOSE"
$newUrn = $urn->normalize();
echo $newUrn; //returns "urn:example:Animal:NOSE"
```

<p class="message-info">The NSS and the optional components are not affected by the normalization.</p>

## Equivalence

By default, when comparing two URN only the NIS and the NSS parts are considered as per the requirements of
the RFC. However, depending on the specificity of some URN namespace, the optional component may be used. To
cover both situations the `UrnComparisonMode` enum is used with the `equals` method.

```php
use League\Uri\Urn;
use League\Uri\UrnComparisonMode;

$urn = Urn::fromRfc2141('example', 'animal:nose')->withQComponent('foo/bar');
$urnBis = Urn::fromRfc2141('example', 'animal:nose');

$urn->equals($urnBis, UrnComparisonMode::ExcludeComponents); // returns true
$urn->equals($urnBis, UrnComparisonMode::IncludeComponents); // returns false
```

<p class="message-info">By default, if no <code>UrnComparisonMode</code> is used, optional components are
not taken into account during comparison.</p>

<p class="message-warning"> The <code>Urn::equals()</code> method applies URN-specific
comparison rules that differ from those of <code>Uri::equals()</code>. Consequently,
two values may be considered equal when compared as URNs but not as URIs.</p>

```php
use League\Uri\Urn;

$urn = Urn::fromRfc2141('ExamPLe', 'animal:Nose');
$urnBis = Urn::fromRfc2141('example', 'animal:Nose');
$uri =  $urn->toUri();

$urn->equals($uri);     // returns true comparing using URN rules
$urn->equals($urnBis);  // returns true comparing using URN rules
$uri->equals($urn);     // return true comparing using URI rules
$uri->equals($urnBis);  // returns false comparing using URI rules
```

## URI resolution

It is possible to convert your `League\Uri\Urn` instance into a `League\Uri\Uri` object using the
`resolve()` method. The method returns a `League\Uri\Uri` instance.

```php
use Uri\UriComparisonMode;

$urn = Urn::fromString('urn:example:animal:nose?+foo=bar&fo%26o=b%3Far');
$uri = $urn->resolve(); // returns a League\Uri\Uri instance
$uri->equals($urn, UriComparisonMode::IncludeFragment);   // returns true
```

<p class="message-warning">There is no <code>Uri::toUrn()</code> method attached to the <code>League\Uri\Uri</code>
class because every URN is a URI but not all URIs are URNs.</p>

The `resolve()` method can also take a URI template as its single parameter allowing to specify the
URN to URI resolution based on some URN resolvers. For instance, if we want to resolve an ISBN URN
against the `openlibrary.org` service you can do the following

```php
$urn = Urn::new("urn:isbn:9782266178945");
$urn->resolve(); //returns a League\Uri\Uri instance with the same string representation
$urn->resolve("https://openlibrary.org/isbn/{nss}")->toString();
//returns the League\Uri\Uri instance for "https://openlibrary.org/isbn/9782266178945"
```

The following URI Template variables are available:

- `nid`: the namespace identifier
- `nss`: the namespace specific string
- `r_component`: the encoded `r-component` value
- `q_component`: the encoded `q-component` value
- `f_component`: the encoded `f-component` value
