<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri;

use BackedEnum;
use League\Uri\Contracts\Conditionable;
use League\Uri\UrlPattern\CaseMode;
use League\Uri\UrlPattern\Component;
use League\Uri\UrlPattern\Parser;
use Stringable;
use Uri\Rfc3986\Uri as Rfc3986Uri;
use Uri\WhatWg\Url as WhatWgUrl;

use function array_map;
use function is_bool;
use function strrpos;
use function substr;

final class UrlPatternBuilder implements Conditionable
{
    private ?Component $scheme = null;
    private ?Component $username = null;
    private ?Component $password = null;
    private ?Component $host = null;
    private ?Component $port = null;
    private ?Component $path = null;
    private ?Component $query = null;
    private ?Component $fragment = null;
    private CaseMode $sensitivity = CaseMode::Sensitive;

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->scheme = null;
        $this->username = null;
        $this->password = null;
        $this->host = null;
        $this->port = null;
        $this->path = null;
        $this->query = null;
        $this->fragment = null;
        $this->sensitivity = CaseMode::Sensitive;
    }

    public static function from(Stringable|string $pattern): self
    {
        $components = (new Parser($pattern))->components();

        return (new self())
            ->scheme($components['scheme'] ?? null)
            ->username($components['username'] ?? null)
            ->password($components['password'] ?? null)
            ->host($components['host'] ?? null)
            ->port($components['port'] ?? null)
            ->path($components['path'] ?? null)
            ->query($components['query'] ?? null)
            ->fragment($components['fragment'] ?? null);
    }

    public function build(Rfc3986Uri|WhatWgUrl|BackedEnum|Stringable|string|null $baseUrl = null): UrlPattern
    {
        $components = [
            'scheme' => $this->scheme ?? null,
            'username' => $this->username ?? null,
            'password' => $this->password ?? null,
            'host' => $this->host ?? null,
            'port' => $this->port ?? null,
            'path' => $this->path ?? null,
            'query' => $this->query ?? null,
            'fragment' => $this->fragment ?? null,
        ];

        if (null !== $baseUrl) {
            $components = self::applyBaseUrl($components, self::uriString($baseUrl));
        }

        $components = array_map(
            static fn (?Component $component): Component => $component ?? Component::fromAsterix(),
            $components
        );

        return new UrlPattern($components, $this->sensitivity);
    }

    public function when(callable|bool $condition, callable $onSuccess, ?callable $onFail = null): static
    {
        if (!is_bool($condition)) {
            $condition = $condition($this);
        }

        return match (true) {
            $condition => $onSuccess($this),
            null !== $onFail => $onFail($this),
            default => $this,
        } ?? $this;
    }

    private static function applyBaseUrl(array $components, string $baseUrl): array
    {
        $baseComponents = UriString::parse($baseUrl);
        $hasScheme = isset($components['scheme']);
        $hasHost = isset($components['host']);
        $hasPort = isset($components['port']);

        if (! $hasScheme) {
            $components['scheme'] = null !== $baseComponents['scheme']
                ? Component::fromPattern($baseComponents['scheme'])
                : Component::fromAsterix();
        }

        if (! $hasScheme && ! $hasHost) {
            $components['host'] = null !== $baseComponents['host']
                ? Component::fromPattern($baseComponents['host'])
                : Component::fromAsterix();
        }

        if (! $hasScheme && ! $hasHost && ! $hasPort) {
            $components['port'] = null !== $baseComponents['port']
                ? Component::fromPattern((string) $baseComponents['port'])
                : Component::fromAsterix();
        }

        $components['path'] = match (true) {
            !isset($components['path']) => null !== $baseComponents['path'] ? Component::fromPattern($baseComponents['path']) : Component::fromAsterix(),
            default => self::resolvePathname($components['path'], $baseComponents['path']),
        };

        return $components;
    }

    private static function uriString(Rfc3986Uri|WhatWgUrl|BackedEnum|Stringable|string $uri): string
    {
        return match (true) {
            $uri instanceof Rfc3986Uri => $uri->toRawString(),
            $uri instanceof WhatWgUrl => $uri->toUnicodeString(),
            $uri instanceof BackedEnum => (string) $uri->value,
            default => (string) $uri,
        };
    }

    private static function resolvePathname(Component $pathname, string $basePathname): Component
    {
        if (str_starts_with($pathname->pattern, '/')) {
            return $pathname;
        }

        $slash = strrpos($basePathname, '/');

        $pattern = false === $slash
            ? $pathname->pattern
            : substr($basePathname, 0, $slash + 1).$pathname->pattern;

        return Component::fromPattern($pattern);
    }

    private static function filter(Component|Stringable|string|null $component): ?Component
    {
        return match (true) {
            $component instanceof Component => $component,
            null === $component => null,
            default => Component::fromPattern((string) $component),
        };
    }

    public function scheme(Component|Stringable|string|null $scheme): self
    {
        $this->scheme = self::filter($scheme);

        return $this;
    }

    public function username(Component|Stringable|string|null $username): self
    {
        $this->username = self::filter($username);

        return $this;
    }

    public function password(Component|Stringable|string|null $password): self
    {
        $this->password = self::filter($password);

        return $this;
    }

    public function host(Component|Stringable|string|null $host): self
    {
        $this->host = self::filter($host);

        return $this;
    }

    public function port(Component|Stringable|string|null $port): self
    {
        $this->path = self::filter($port);

        return $this;
    }

    public function path(Component|Stringable|string|null $path): self
    {
        $this->path = self::filter($path);

        return $this;
    }

    public function query(Component|Stringable|string|null $query): self
    {
        $this->query = self::filter($query);

        return $this;
    }

    public function fragment(Component|Stringable|string|null $fragment): self
    {
        $this->fragment = self::filter($fragment);

        return $this;
    }

    public function ignoreCase(): self
    {
        $this->sensitivity = CaseMode::Insensitive;

        return $this;
    }

    public function preserveCase(): self
    {
        $this->sensitivity = CaseMode::Sensitive;

        return $this;
    }
}
