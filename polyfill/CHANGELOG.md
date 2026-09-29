# Changelog

All Notable changes to `League\Uri` will be documented in this file

## [Next](https://github.com/thephpleague/uri-polyfill/compare/7.8.1...master) - TBD

### Added

- `Uri\Rfc3986\UriType`
- `Uri\Rfc3986\UriHostType`
- `Uri\WhatWg\UriHostType`

### Fixed

- Polyfill Uri\Rfc3986\Uri normalized getters decode percent-encoded reserved characters [#210](https://github.com/thephpleague/uri-src/issues/210)
- Polyfill Uri\Rfc3986\Uri does not remove percent-encoded dot segments during normalization [#211](https://github.com/thephpleague/uri-src/issues/211)
- Polyfill Uri\Rfc3986\Uri keeps a leading "./" in the normalized path of a relative reference [#212](https://github.com/thephpleague/uri-src/issues/212)
- Polyfill Uri\Rfc3986\Uri::parse() accepts ":b", and toString() then throws SyntaxError [#213](https://github.com/thephpleague/uri-src/issues/213)
- Polyfill Uri\Rfc3986\Uri::withPath() prepends "./" to a path with a colon even when the URI has a scheme [#214](https://github.com/thephpleague/uri-src/issues/214)
- Polyfill parameter names and exception constructor signatures differ from native ext/uri [#216](https://github.com/thephpleague/uri-src/issues/216)
- Polyfill Uri\WhatWg\Url::withScheme() has no effect when the URL has user info [#204](https://github.com/thephpleague/uri-src/issues/204)
- Polyfill Uri\WhatWg\Url::getQuery()/getFragment() return null instead of "" for an empty query or fragment [#205](https://github.com/thephpleague/uri-src/issues/205)
- Polyfill Uri\WhatWg\Url reports validation errors of the base URL when resolving a relative URL [#206](https://github.com/thephpleague/uri-src/issues/206)
- Polyfill Uri\WhatWg\Url::withHost() accepts a host with a port instead of throwing [#207](https://github.com/thephpleague/uri-src/issues/207)
- Polyfill Uri\WhatWg\Url::withPort() throws for out-of-range ports on URLs that cannot have a port [#208](https://github.com/thephpleague/uri-src/issues/208)

### Deprecated

- None

### Removed

- None

## [7.8.1](https://github.com/thephpleague/uri-polyfill/compare/7.8.0...7.8.1) - 2026-03-16

### Added

- None

### Fixed

- Simplify caught exception to reduce `League\Uri` dependency
- Update requirement to use `uri-interfaces` 7.8.1

### Deprecated

- None

### Removed

- None

## [7.8.0](https://github.com/thephpleague/uri-polyfill/compare/7.7.0...7.8.0) - TBD

### Added

- None

### Fixed

- Test to validate user info encoding for the `Uri\Rfc3986\Uri` class

### Deprecated

- None

### Removed

- None

## [7.7.0](https://github.com/thephpleague/uri-polyfill/compare/7.6.0...7.7.0) - 2025-12-08

### Added

- None

### Fixed

- Null byte handling see [GH-20366](https://github.com/php/php-src/pull/20489)
- Align password component handling for the Uri class with `Uri\Rfc3986\Uri` implementation [GH-20545](https://github.com/php/php-src/issues/20545)
- `Url::resolve` signature.

### Deprecated

- None

### Removed

- None

## 7.6.0 - 2025-11-18

First Stable Release
