---
layout: default
title: URI common interfaces and tools
---

Uri Interfaces
=======

This package contains interfaces to help represent URI objects according to [RFC 3986](http://tools.ietf.org/html/rfc3986).

## RFC3986 URI Interface

The `League\Uri\Contract\UriInterface` interface models generic URIs as specified in [RFC 3986](http://tools.ietf.org/html/rfc3986).
The interface provides methods for interacting with the various URI parts, which will obviate
the need for repeated parsing of the URI. It also specifies:

- a `__toString()` method for casting the modeled URI to its string representation.
- a `jsonSerialize()` method to improve interoperability with [WHATWG URL Living standard](https://url.spec.whatwg.org/)

### Accessing URI properties

The `UriInterface` interface defines the following methods to access the URI string representation, its individual parts and components.

~~~php
public UriInterface::getScheme(): ?string
public UriInterface::getUserInfo(): ?string
public UriInterface::getUsername(): ?string
public UriInterface::getPassword(): ?string
public UriInterface::getHost(): ?string
public UriInterface::getPort(): ?int
public UriInterface::getAuthority(): ?string
public UriInterface::getPath(): string
public UriInterface::getQuery(): ?string
public UriInterface::getFragment(): ?string
public UriInterface::getComponents(): array
public UriInterface::toString(): string
public UriInterface::toAsciiString(): string
public UriInterface::toUnicodeString(): string
public UriInterface::__toString(): string
public UriInterface::jsonSerialize(): string
~~~

The `toAsciiString` and `toUnicodeString` are added to allow a better representation of the URI
depending on IDNA context being taking into account or not.

### Modifying URI properties

The `Uri` interface defines the following modifying methods. these methods **must** be implemented such that they retain the internal state of the current instance and return an instance that contains the changed state.

Delimiter characters are not part of the URI component and **must not** be added to the modifying method submitted value. If present they will be treated as part of the URI component content.

**These methods will trigger a `League\Uri\Contract\UriException` exception if the resulting URI is not valid. The validation is scheme dependent.**

~~~php
public UriInterface::withScheme(Stringable|string|null $scheme): self
public UriInterface::withUserInfo(Stringable|string|null $user [, Stringable|string|null $password = null]): self
public UriInterface::withUsername(Stringable|string|null $user): self
public UriInterface::withPassword(Stringable|string|null $password): self
public UriInterface::withHost(Stringable|string|null $host): self
public UriInterface::withPort(?int $port): self
public UriInterface::withPath(Stringable|string $path): self
public UriInterface::withQuery(Stringable|string|null $query): self
public UriInterface::withFragment(Stringable|string|null $fragment): self
~~~

### URI resolution

RFC3986 exposes an algorithm and steps to resolve or normalize URI before being able
to compare them each other. And to complement both actions, the UriInterface contract
adds a `relativize` method to enable the inverse of resolve an URI.

~~~php
public UriInterface::resolve(Stringable|string|null $scheme): self
public UriInterface::relativize(Stringable|string|null $user [, Stringable|string|null $password = null]): self
public UriInterface::normalize(): self
~~~

### Relation with [PSR-7](http://www.php-fig.org/psr/psr-7/#3-5-psr-http-message-uriinterface)

This interface exposes the same methods as `Psr\Http\Message\UriInterface`. But, differs on the following keys:

- This interface does not require the `http` and `https` schemes to be supported.
- Setter and Getter component methods, except the path component, accept and can return the `null` value.
- If no scheme is present, the requirement to fall back to `http` and `https` schemes specific validation rules is not enforced.

## URI components Interfaces

The `League\Uri\Contract\UriComponentInterface` interface models generic URI components as specified in [RFC 3986](http://tools.ietf.org/html/rfc3986). The interface provides methods for interacting with an URI component, which will obviate the need for repeated parsing of the URI component. It also specifies a `__toString()` method for casting the modeled URI component to its string representation.

### String Representations

The `UriComponentInterface` interface defines the following methods to access the URI component content.

~~~php
public UriComponentInterface::value(): ?string
public UriComponentInterface::toString(): string
public UriComponentInterface::getUriComponent(): ?string
public UriComponentInterface::jsonSerialize(): ?string
public UriComponentInterface::__toString(): string
~~~

### Specific interfaces

Because each URI component has specific needs most have specialized interface which all extends
the `UriComponentInterface` interface. The following interfaces also exist:

- `League\Uri\Contract\AuthorityInterface`
- `League\Uri\Contract\DataPathInterface`
- `League\Uri\Contract\DomainHostInterface`
- `League\Uri\Contract\FragmentInterface`
- `League\Uri\Contract\UserInfoInterface`
- `League\Uri\Contract\HostInterface`
- `League\Uri\Contract\IpHostInterface`
- `League\Uri\Contract\PathInterface`
- `League\Uri\Contract\PortInterface`
- `League\Uri\Contract\QueryInterface`
- `League\Uri\Contract\SegmentedPathInterface`
- `League\Uri\Contract\FragmentDirective` **since version 7.6**
