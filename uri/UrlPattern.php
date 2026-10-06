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
use League\Uri\Exceptions\SyntaxError;
use League\Uri\UrlPattern\CaseMode;
use League\Uri\UrlPattern\Component;
use League\Uri\UrlPattern\ComponentResult;
use League\Uri\UrlPattern\PartType;
use League\Uri\UrlPattern\Result;
use Stringable;
use Uri\Rfc3986\Uri as Rfc3986Uri;
use Uri\WhatWg\Url as WhatWgUrl;
use ValueError;

use function array_key_exists;
use function array_map;
use function get_debug_type;
use function in_array;
use function preg_match;

/**
 * @property-read ?string $scheme
 * @property-read ?string $username
 * @property-read ?string $password
 * @property-read ?string $host
 * @property-read ?string $port
 * @property-read ?string $path
 * @property-read ?string $query
 * @property-read ?string $fragment
 */
final class UrlPattern
{
    private const COMPONENT_NAMES = ['scheme', 'username', 'password', 'host', 'port', 'path', 'query', 'fragment'];
    /** @var array<'scheme'|'username'|'password'|'host'|'port'|'path'|'query'|'fragment', Component> $components */
    private readonly array $components;
    public readonly CaseMode $caseMode;
    public readonly bool $hasRegexpGroup;

    /**
     * @param array<'scheme'|'username'|'password'|'host'|'port'|'path'|'query'|'fragment', Component> $patternComponents
     */
    public function __construct(array $patternComponents, CaseMode $caseMode = CaseMode::Sensitive)
    {
        $hasRegexpGroup = false;
        $components = [];
        foreach (self::COMPONENT_NAMES as $name) {
            if (!array_key_exists($name, $patternComponents)) {
                $components[$name] = Component::fromAsterix();
            }

            $component = $patternComponents[$name];
            $component instanceof Component || throw new ValueError('the component must be a "'.Component::class.'"; '.get_debug_type($component).' given.');
            $components[$name] = $component;
            if (!$hasRegexpGroup) {
                $hasRegexpGroup = $component->hasRegexpGroup;
            }
        }

        $this->components = $components;
        $this->hasRegexpGroup = $hasRegexpGroup;
        $this->caseMode = $caseMode;
    }

    public static function from(
        Stringable|string $pattern,
        Rfc3986Uri|WhatWgUrl|BackedEnum|Stringable|string|null $baseUrl = null,
        CaseMode $caseMode = CaseMode::Sensitive
    ): self {
        return UrlPatternBuilder::from($pattern)
            ->when(
                CaseMode::Insensitive === $caseMode,
                fn (UrlPatternBuilder $builder) => $builder->ignoreCase(),
                fn (UrlPatternBuilder $builder) => $builder->preserveCase()
            )
            ->build($baseUrl);
    }

    public function __get(string $name): ?string
    {
        in_array($name, self::COMPONENT_NAMES, true) || throw new ValueError('the property named "'.$name.'" does not exist.');

        return array_key_exists($name, $this->components) ? $this->components[$name]->pattern : null;
    }

    public function match(Rfc3986Uri|WhatWgUrl|BackedEnum|Stringable|string $input): bool
    {
        return $this->extract($input) instanceof Result;
    }

    /**
     * @throws SyntaxError
     */
    public function extract(Rfc3986Uri|WhatWgUrl|BackedEnum|Stringable|string $input): ?Result
    {
        $components = array_map(static fn (?string $value): string => (string) $value, UriString::parse(self::uriString($input)));
        $components['username'] = $components['user'];
        $components['password'] = $components['pass'];

        $result = [];
        foreach ($this->components as $name => $component) {
            $found = $this->extractComponent($component, $components[$name], $name);
            if (null === $found) {
                return null;
            }

            $result[$name] = $found;
        }

        return Result::tryFrom($result);
    }

    private function extractComponent(Component $component, string $source, string $name): ?ComponentResult
    {
        $matches = [];
        $modifier = CaseMode::Insensitive === $this->caseMode ? 'i' : '';
        $regexp = '~'.$component->regexp.'~'.$modifier;
        if (1 !== preg_match($regexp, $source, $matches)) {
            return null;
        }

        $data = [];
        $matchIndex = 1;
        foreach ($component->parts as $part) {
            if (PartType::Fixed === $part->type) {
                continue;
            }

            $content = $matches[$matchIndex++] ?? null;
            $data[$part->name] = match ($name) {
                'host' => HostRecord::from($content)->toUnicode(),
                default => Encoder::decodeAll($content),
            };
        }

        return new ComponentResult($component->pattern, $data);
    }

    private static function uriString(Rfc3986Uri|WhatWgUrl|BackedEnum|Stringable|string $uri): string
    {
        return match (true) {
            $uri instanceof Rfc3986Uri => $uri->toRawString(),
            $uri instanceof WhatWgUrl => $uri->toAsciiString(),
            $uri instanceof BackedEnum => (string) $uri->value,
            default => (string) $uri,
        };
    }
}
