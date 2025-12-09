---
layout: default
title: Host Record
description: a Basic Host record to validate and analyze a Host component
---

Host Record
=======

<p class="message-notice">Available since version <code>7.7.0</code></p>

The `League\Uri\HostRecord` is a PHP Host component validator and analyzer.
Since correctly validating a Host can quickly become complex and error-prone,
by using the `HostRecord` class, you can validate your host component in
a more reliable and predicable way. But also, you analyze the host against
RFC3986 rules and cache the result to speed up Host or URI resolution on
future calls.

## Host Validation

The class exposes several validation rules. The basic validation rules tells
whether the input is considered a valid host:

```php
use League\Uri\HostRecord;

if (HostRecord::isValid('[fe80:1234::]')) {
    //the host is valid you can proceed
    //with whatever you need to do with this host
}
```

The class exposes more specific validation rules that works using the same logic
but allow to check if the host is:

- an IPv4 address using `HostRecord::isIpv4`
- an IPv6 address using `HostRecord::isIpv6`
- an IPvFuture address using `HostRecord::isIpvFuture`
- an IP address using `HostRecord::isIp`
- a Registered Name address using `HostRecord::isRegisteredName`
- a Domain Name address using `HostRecord::isDomain`

## Host Record

if you are more interested on the Host metadata you can use the `from` static method.
a `HostRecord` instance is returned and give you access to the following methods:

```php
use League\Uri\HostRecord;

$hostRecord = HostRecord::from('[fe80:1234::]');
$hostRecord->value;          // returns '[fe80:1234::]'
$hostRecord->type;           // HostType::Ipv6
$hostRecord->format;         // HostFormat::Ascii
$hostRecord->toAscii();      // returns '[fe80:1234::]'
$hostRecord->toUnicode();    // returns '[fe80:1234::]'
$hostRecord->isDomainType(); // returns false
$hostRecord->ipValue();      // returns 'fe80:1234::'
$hostRecord->ipVersion();    // returns '6'
```

In case of an error, a `League\Uri\Contracts\UriException` is thrown.

<p class="message-notice">The <code>HostRecord</code> constructor is marked as private and, thus, should not be used.</p>

### Enums

Three Enums are introduced to handle the `HostRecord` information:

- `HostType`: which returns the RFC3986 host type possibilities (`RegisteredName`, `Ipv4`, `Ipv6`, and `IpvFuture`)
- `HostFormat`: which returns the host encoding format (`Ascii` or `Unicode`)

Of note, a valid URI does not require an IP or a domain name, but since most developers deal with host as domain name,
the `HostRecord::isDomainName` method is added to allow to distinguish the specific host subtype
within the `HostType::RegisteredName` type.

### Caching

Because computing these information can be time-consuming, a in-memory cache has been put in place to reduce 
the calculation load. Whenever you can the `validate` or `from` method the instantiated class
is cached for the duration of the script which ensure that calling multiple time both method with the same
input is not being recalculated each time.

<p class="message-info">Internally, this class is used everytime a host is to be processed to normalize, and speed up
Host validation and normalization.</p>
