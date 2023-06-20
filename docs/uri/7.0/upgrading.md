---
layout: default
title: Upgrading from 6.x to 7.x
---

# Upgrading from 6.x to 7.x

`League\Uri 7.0` is a new major version that comes with backward compatibility breaks.

This guide will help you migrate from a 6.x version to 7.0. It will only explain backward compatibility breaks, it will not present the new features ([read the documentation for that](/7.0/)).

## Installation

If you are using composer then you should update the require section of your `composer.json` file.

~~~
composer require league/uri:^7.0
~~~

This will edit (or create) your `composer.json` file.

## PHP version requirement

`League\Uri 7.0` requires a PHP version greater or equal than 8.1.0 (was previously 7.2.0).

## Interfaces

The `League\Uri\Http` implements the PSR-7 `UriInterface` v2 and PHP's `JsonSerializable` interfaces.

## Deprecated methods

The following methods are marked as deprecated. They will stay to allow eaiser upgrade path
to version `7.0`, but it is recommended not to use them for new projects.

| Deprecated methods           | New stable methods     |
|------------------------------|------------------------|
| `Uri::createFromString`      | `Uri::new`             |
| `Uri::createFromUri`         | `Uri::new`             |
| `Uri::createFromServer`      | `Uri::fromServer`      |
| `Uri::createFromComponents`  | `Uri::fromComponents`  |
| `Uri::createFromBaseUri`     | `Uri::fromBaseUri`     |
| `Uri::createFromDataPath`    | `Uri::fromDataPath`    |
| `Uri::createFromUnixPath`    | `Uri::fromUnixPath`    |
| `Uri::createFromWindowsPath` | `Uri::fromWindowsPath` |
| `Http::createFromString`     | `Http::new`            |
| `Http::createFromUri`        | `Http::new`            |
| `Http::createFromServer`     | `Http::fromServer`     |
| `Http::createFromComponents` | `Http::fromComponents` |
| `Http::createFromBaseUri`    | `Http::fromBaseUri`    |

## Removed functionnalities

- `__set_state` named constructors is removed without replacements.
- Support for `PSR-7` v1
