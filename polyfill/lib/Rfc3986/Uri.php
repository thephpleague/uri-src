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

namespace Uri\Rfc3986;

use Exception;
use League\Uri\Encoder;
use League\Uri\UriString;
use SensitiveParameter;
use Uri\InvalidUriException;
use Uri\UriComparisonMode;

use function explode;
use function strpos;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80500) {
    /**
     * This is a user-land polyfill to the native Uri\Rfc3986\Uri class included in PHP8.5.
     *
     * @see https://wiki.php.net/rfc/url_parsing_api
     *
     * @phpstan-type Components array{scheme: ?string, userInfo: ?string, user: ?string, pass: ?string, host: ?string, port: ?int, path: string, query: ?string, fragment: ?string}
     * @phpstan-import-type ComponentMap from UriString
     * @phpstan-import-type InputComponentMap from UriString
     * @phpstan-import-type UriSerializedShape from UriComparisonMode
     * @phpstan-import-type UriDebugShape from UriComparisonMode
     */
    final class Uri
    {
        private const TYPE_RAW = 'raw';
        private const TYPE_NORMALIZED = 'normalized';
        /** @var Components */
        private const DEFAULT_COMPONENTS = ['scheme' => null, 'userInfo' => null, 'user' => null, 'pass' => null, 'host' => null, 'port' => null, 'path' => '', 'query' => null, 'fragment' => null];
        /** @var Components */
        private readonly array $rawComponents;
        private readonly string $rawUri;
        /** @var Components */
        private array $normalizedComponents = self::DEFAULT_COMPONENTS;
        private ?string $normalizedUri = null;
        private bool $isNormalized;

        public static function parse(string $uri, ?self $baseUri = null): ?Uri
        {
            try {
                return new self($uri, $baseUri);
            } catch (Exception) {
                return null;
            }
        }

        /**
         * @throws InvalidUriException
         */
        public function __construct(string $uri, ?self $baseUri = null)
        {
            UriString::containsValidRfc3986Characters($uri) || throw new InvalidUriException('The URI `'.$uri.'` contains invalid RFC3986 characters.');

            try {
                $uri = null !== $baseUri ? UriString::resolve($uri, $baseUri->toRawString()) : $uri;
                $components = self::addUserInfoComponent(UriString::parse($uri));
            } catch (Exception $exception) {
                throw new InvalidUriException($exception->getMessage(), previous: $exception);
            }

            Encoder::isUserInfoEncoded($components['userInfo']) || throw new InvalidUriException('The encoded userInfo string component `'.$components['userInfo'].'` contains invalid characters.');
            Encoder::isPathEncoded($components['path']) || throw new InvalidUriException('The encoded path component `'.$components['path'].'` contains invalid characters.');
            Encoder::isQueryEncoded($components['query']) || throw new InvalidUriException('The encoded query string component `'.$components['query'].'` contains invalid characters.');
            Encoder::isFragmentEncoded($components['fragment']) || throw new InvalidUriException('The encoded fragment string component `'.$components['fragment'].'` contains invalid characters.');

            $this->rawUri = $uri;
            $this->rawComponents = $components;
            $this->isNormalized = false;
        }

        /**
         * @param ComponentMap $parts
         *
         * @return Components
         */
        private static function addUserInfoComponent(array $parts): array
        {
            $components = [...self::DEFAULT_COMPONENTS, ...$parts];
            $components['userInfo'] = $components['user'];
            if (null === $components['user'] || null === $components['pass']) {
                $components['pass'] = null;

                return $components;
            }

            $components['userInfo'] .= ':'.$components['pass'];

            return $components;
        }

        /**
         * @param self::TYPE_RAW|self::TYPE_NORMALIZED $type
         */
        private function getComponent(string $type, string $name): ?string
        {
            if (self::TYPE_NORMALIZED === $type) {
                $this->setNormalizedComponents();
            }

            $value = self::TYPE_NORMALIZED === $type ? $this->normalizedComponents[$name] : $this->rawComponents[$name];
            if (null === $value) {
                return null;
            }

            return (string) $value;
        }

        private function setNormalizedComponents(): void
        {
            if ($this->isNormalized) {
                return;
            }

            $components = [
                ...self::addUserInfoComponent(UriString::parseNormalized($this->rawUri)),
                ...['host' => Encoder::normalizeHost($this->rawComponents['host'])],
            ];

            $authority = UriString::buildAuthority($components);
            // preserving the first `/./` segment in case of normalization
            // when no authority is present,
            // see https://github.com/php/php-src/issues/19897
            if (str_starts_with($this->rawComponents['path'], '/./') && null === $authority) {
                $components['path'] = '/.'.$components['path'];
            }

            $this->normalizedComponents = $components;
            $this->isNormalized = true;
        }

        /**
         * @param InputComponentMap $components
         *
         * @throws InvalidUriException
         */
        private function withComponent(array $components): self
        {
            try {
                $uri = UriString::build([...$this->rawComponents, ...$components]);
            } catch (Exception $exception) {
                throw new InvalidUriException($exception->getMessage(), previous: $exception);
            }

            return new self($uri);
        }

        public function getRawScheme(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'scheme');
        }

        public function getScheme(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'scheme');
        }

        /**
         * @throws InvalidUriException
         */
        public function withScheme(?string $scheme): self
        {
            if ($scheme === $this->getRawScheme()) {
                return $this;
            }

            if (!UriString::isValidScheme($scheme)) {
                throw new InvalidUriException('The scheme string component `'.$scheme.'` is an invalid scheme.');
            }

            $components = $this->rawComponents;
            $components['scheme'] = $scheme;
            $components['path'] = $this->preparePathForModification($components['path'], $components);

            return  $this->withComponent($components);
        }

        public function getRawUserInfo(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'userInfo');
        }

        public function getUserInfo(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'userInfo');
        }

        /**
         * @throws InvalidUriException
         */
        public function withUserInfo(#[SensitiveParameter] ?string $userInfo): self
        {
            if ($this->getRawUserInfo() === $userInfo) {
                return $this;
            }

            Encoder::isUserInfoEncoded($userInfo) || throw new InvalidUriException('The encoded userInfo string component `'.$userInfo.'` contains invalid characters.');

            $user = null;
            $pass = null;
            if (null !== $userInfo) {
                [$user, $pass] = explode(':', $userInfo, 2) + [1 => null];
            }

            return $this->withComponent(['user' => $user, 'pass' => $pass]);
        }

        public function getRawUsername(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'user');
        }

        public function getUsername(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'user');
        }

        public function getRawPassword(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'pass');
        }

        public function getPassword(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'pass');
        }

        public function getRawHost(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'host');
        }

        public function getHost(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'host');
        }

        /**
         * @throws InvalidUriException
         */
        public function withHost(?string $host): self
        {
            if ($host === $this->getRawHost()) {
                return $this;
            }

            if (!UriString::isValidHost($host)) {
                throw new InvalidUriException('The host component value `'.$host.'` is not a valid host.');
            }

            $components = $this->rawComponents;
            $components['host'] = $host;
            $components['path'] = $this->preparePathForModification($components['path'], $components);

            return  $this->withComponent($components);
        }

        public function getPort(): ?int
        {
            return $this->rawComponents['port'];
        }

        /**
         * @throws InvalidUriException
         */
        public function withPort(?int $port): self
        {
            return match (true) {
                $port === $this->getPort() => $this,
                null === $port || 0 <= $port => $this->withComponent(['port' => $port]),
                default => throw new InvalidUriException('The port component value must be null or an integer between 0 and 65535.'),
            };
        }

        public function getRawPath(): string
        {
            return (string) $this->getComponent(self::TYPE_RAW, 'path');
        }

        public function getPath(): string
        {
            return (string) $this->getComponent(self::TYPE_NORMALIZED, 'path');
        }

        /**
         * A path segment that contains a colon character (e.g., "this:that")
         * cannot be used as the first segment of a relative-path reference, as
         * it would be mistaken for a scheme name. Such a segment must be
         * preceded by a dot-segment (e.g., "./this:that") to make a relative-path
         * reference.
         *
         * @throws InvalidUriException
         */
        public function withPath(string $path): self
        {
            return match (true) {
                $path === $this->getRawPath() => $this,
                Encoder::isPathEncoded($path) => $this->withComponent(['path' => $this->preparePathForModification($path, $this->rawComponents)]),
                default => throw new InvalidUriException('The encoded path component `'.$path.'` contains invalid characters.'),
            };
        }

        public function getRawQuery(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'query');
        }

        public function getQuery(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'query');
        }

        /**
         * @throws InvalidUriException
         */
        public function withQuery(?string $query): self
        {
            return match (true) {
                $query === $this->getRawQuery() => $this,
                Encoder::isQueryEncoded($query) => $this->withComponent(['query' => $query]),
                default => throw new InvalidUriException('The encoded query string component `'.$query.'` contains invalid characters.'),
            };
        }

        public function getRawFragment(): ?string
        {
            return $this->getComponent(self::TYPE_RAW, 'fragment');
        }

        public function getFragment(): ?string
        {
            return $this->getComponent(self::TYPE_NORMALIZED, 'fragment');
        }

        /**
         * @throws InvalidUriException
         */
        public function withFragment(?string $fragment): self
        {
            return match (true) {
                $fragment === $this->getRawFragment() => $this,
                Encoder::isFragmentEncoded($fragment) => $this->withComponent(['fragment' => $fragment]),
                default => throw new InvalidUriException('The encoded fragment string component `'.$fragment.'` contains invalid characters.'),
            };
        }

        public function equals(self $uri, UriComparisonMode $uriComparisonMode = UriComparisonMode::ExcludeFragment): bool
        {
            return match (true) {
                $this->getFragment() === $uri->getFragment(),
                UriComparisonMode::IncludeFragment === $uriComparisonMode => $this->normalizedComponents === $uri->normalizedComponents,
                default => [...$this->normalizedComponents, ...['fragment' => null]] === [...$uri->normalizedComponents, ...['fragment' => null]],
            };
        }

        public function toRawString(): string
        {
            return $this->rawUri;
        }

        public function toString(): string
        {
            $this->setNormalizedComponents();
            $this->normalizedUri ??= UriString::build($this->normalizedComponents);

            return $this->normalizedUri;
        }

        /**
         * @throws InvalidUriException
         */
        public function resolve(string $uri): self
        {
            return new self($uri, $this);
        }

        /**
         * @return UriSerializedShape
         */
        public function __serialize(): array
        {
            return [['uri' => $this->toRawString()], []];
        }

        /**
         * @param UriSerializedShape $data
         *
         * @throws Exception|InvalidUriException
         */
        public function __unserialize(array $data): void
        {
            [$properties] = $data;
            $uri = new self($properties['uri'] ?? throw new Exception('The `uri` property is missing from the serialized object.'));

            $this->rawComponents = $uri->rawComponents;
            $this->rawUri = $uri->rawUri;
            $this->isNormalized = false;
        }

        /**
         * @return UriDebugShape
         */
        public function __debugInfo(): array
        {
            return [
                'scheme' => $this->rawComponents['scheme'],
                'username' => $this->rawComponents['user'],
                'password' => $this->rawComponents['pass'],
                'host' => $this->rawComponents['host'],
                'port' => $this->rawComponents['port'],
                'path' => $this->rawComponents['path'],
                'query' => $this->rawComponents['query'],
                'fragment' => $this->rawComponents['fragment'],
            ];
        }

        /**
         * Formatting the path when setting the path to avoid
         * exception to be thrown on an invalid path.
         * see https://github.com/php/php-src/issues/19897.
         *
         * @param InputComponentMap $components
         */
        private function preparePathForModification(string $path, array $components): string
        {
            $isAbsolute = str_starts_with($path, '/');
            $authority = UriString::buildAuthority($components);
            if (null !== $authority) {
                // If there is an authority, the path must start with a `/`
                return $isAbsolute ? $path : '/'.$path;
            }

            // If there is no authority, the path cannot start with `//`
            if ($isAbsolute) {
                return '/.'.$path;
            }

            $colonPos = strpos($path, ':');
            if (false === $colonPos) {
                return $path;
            }

            // In the absence of a scheme and of an authority,
            // the first path segment cannot contain a colon (":") character.'
            $slashPos = strpos($path, '/');
            if (false === $slashPos || $colonPos < $slashPos) {
                return './'.$path;
            }

            return $path;
        }
    }
}
