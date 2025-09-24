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

namespace Uri\WhatWg;

use Exception;
use League\Uri\Polyfill\UrlValidationErrorCollector;
use ReflectionClass;
use ReflectionProperty;
use Rowbot\Idna\Idna;
use Rowbot\URL\BasicURLParser;
use Rowbot\URL\Component\Host\StringHost;
use Rowbot\URL\ParserState;
use Rowbot\URL\URL as WhatWgURL;
use Rowbot\URL\URLRecord;
use SensitiveParameter;
use Uri\UriComparisonMode;

use function in_array;
use function substr;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80500) {
    /**
     * This is a user-land polyfill to the native Uri\WhatWg\Url class included in PHP8.5.
     *
     * @see https://wiki.php.net/rfc/url_parsing_api
     *
     * @phpstan-import-type UriSerializedShape from UriComparisonMode
     * @phpstan-import-type UriDebugShape from UriComparisonMode
     */
    final class Url
    {
        private const PORT_RANGE_MIN = 0;
        private const PORT_RANGE_MAX = 65535;

        private WhatWgURL $url;
        private ?string $unicodeHost = null;
        private bool $unicodeHostInitialized = false;
        private ?string $urlUnicodeString = null;

        /**
         * @param list<UrlValidationError> $errors
         */
        public static function parse(string $uri, ?self $baseUrl = null, array &$errors = []): ?self
        {
            try {
                return new self($uri, $baseUrl, $errors);
            } catch (InvalidUrlException $exception) {
                $errors = $exception->errors;

                return null;
            }
        }

        /**
         * @param list<UrlValidationError> $softErrors
         *
         * @throws InvalidUrlException
         */
        public function __construct(string $uri, ?self $baseUrl = null, array &$softErrors = [])
        {
            $collector = new UrlValidationErrorCollector();

            try {
                $this->url = new WhatWgURL($uri, $baseUrl?->url->href, ['logger' => $collector]);
            } catch (Exception $exception) {
                throw new InvalidUrlException(
                    message: $exception->getMessage(),
                    errors: $collector->errors(),
                    previous: $exception
                );
            } finally {
                $softErrors = $collector->recoverableErrors();
            }
        }

        private function copy(): self
        {
            $newInstance = (new ReflectionClass(self::class))->newInstanceWithoutConstructor();
            $newInstance->url = clone $this->url;

            return $newInstance;
        }

        public function getScheme(): string
        {
            return substr($this->url->protocol, 0, -1);
        }

        /**
         * @throws InvalidUrlException
         */
        public function withScheme(?string $scheme): self
        {
            $scheme = strtolower((string) $scheme);
            if ($scheme === $this->getScheme() || $scheme === $this->url->protocol) {
                return $this;
            }

            $copy = $this->copy();
            if ('' === $scheme) {
                $copy->url->protocol = '';

                return $copy;
            }

            static $regexp = ',^(?<scheme>[a-zA-Z][a-zA-Z0-9+\-.]*)(:(?://?)?)?$,';

            1 === preg_match($regexp, $scheme, $matches) || throw new InvalidUrlException('The specified scheme is malformed.');

            $copy->url->protocol = $matches['scheme'];

            return $copy;
        }

        public function getUsername(): ?string
        {
            return '' === $this->url->username ? null : $this->url->username;
        }

        /**
         * @throws InvalidUrlException
         */
        public function withUsername(?string $username): self
        {
            if ($username === $this->getUsername() || $username === $this->url->username) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->username = (string) $username;

            return $copy;
        }

        public function getPassword(): ?string
        {
            return  '' === $this->url->password ? null : $this->url->password;
        }

        /**
         * @throws InvalidUrlException
         */
        public function withPassword(#[SensitiveParameter] ?string $password): self
        {
            if ($password === $this->getPassword() || $password === $this->url->password) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->password = (string) $password;

            return $copy;
        }

        public function getAsciiHost(): ?string
        {
            return $this->url->hostname;
        }

        public function getUnicodeHost(): ?string
        {
            if ($this->unicodeHostInitialized) {
                return $this->unicodeHost;
            }

            $this->unicodeHost = $this->setUnicodeHost();
            $this->unicodeHostInitialized = true;

            return $this->unicodeHost;
        }

        /**
         * Set the domain to its Unicode value according to the WHATWG URL spec.
         *
         * @see https://url.spec.whatwg.org/#concept-domain-to-unicode
         */
        private function setUnicodeHost(): ?string
        {
            $host = $this->getAsciiHost();
            if ('' === $host || null === $host) {
                return $host;
            }

            $result = Idna::toUnicode($host, [
                'CheckHyphens' => false,
                'CheckBidi' => true,
                'CheckJoiners' => true,
                'UseSTD3ASCIIRules' => false,
                'Transitional_Processing' => false,
                'IgnoreInvalidPunycode' => false,
            ]);

            if ($result->hasErrors()) {
                return $host;
            }

            return $result->getDomain();
        }

        /**
         * @throws InvalidUrlException
         */
        public function withHost(?string $host): self
        {
            // 1 - the submitted host is equal to the current host
            if (in_array($host, [$this->url->hostname, $this->getAsciiHost(), $this->getUnicodeHost()], true)) {
                return $this;
            }

            // 2 - the path is Opaque, it must be a noop, as per the WHATWG URL Standard.
            if (self::urlRecord($this)->path->isOpaque()) {
                return $this;
            }

            $copy = $this->copy();
            $urlRecord = self::urlRecord($copy);

            // 3 - validated the new host in the context of the new URL
            $collector = new UrlValidationErrorCollector();
            $parser = new BasicUrlParser($collector);
            $result = $parser->parse(
                input: new StringHost((string) $host),
                url: $urlRecord,
                stateOverride: ParserState::HOST
            );

            false !== $result || throw new InvalidUrlException('The specified host is malformed', $collector->errors());

            return $copy;
        }

        public function getPort(): ?int
        {
            return '' === $this->url->port ? null : (int) $this->url->port;
        }

        /**
         * @throws InvalidUrlException
         */
        public function withPort(?int $port): self
        {
            if ($port === $this->getPort()) {
                return $this;
            }

            if (null === $port || (self::PORT_RANGE_MIN <= $port && self::PORT_RANGE_MAX >= $port)) {
                $copy = $this->copy();
                $copy->url->port = (string) $port;

                return $copy;
            }

            throw new InvalidUrlException('The specified port is malformed. Port must be between '.self::PORT_RANGE_MIN.' and '.self::PORT_RANGE_MAX, [new UrlValidationError((string) $port, UrlValidationErrorType::PortOutOfRange, true)]);
        }

        public function getPath(): string
        {
            return $this->url->pathname;
        }

        /**
         * @throws InvalidUrlException
         */
        public function withPath(string $path): self
        {
            if ($path === $this->url->pathname) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->pathname = $path;

            return $copy;
        }

        public function getQuery(): ?string
        {
            return '' === $this->url->search ? null : substr($this->url->search, 1);
        }

        /**
         * @throws InvalidUrlException
         */
        public function withQuery(?string $query): self
        {
            if ($query === $this->url->search || $query === $this->getQuery()) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->search = (string) $query;

            return $copy;
        }

        public function getFragment(): ?string
        {
            return '' === $this->url->hash ? null : substr($this->url->hash, 1);
        }

        /**
         * @throws InvalidUrlException
         */
        public function withFragment(?string $fragment): self
        {
            if ($fragment === $this->url->hash || $fragment === $this->getFragment()) {
                return $this;
            }

            $copy = $this->copy();
            $copy->url->hash = (string) $fragment;

            return $copy;
        }

        public function equals(self $url, UriComparisonMode $uriComparisonMode = UriComparisonMode::ExcludeFragment): bool
        {
            return match (true) {
                $this->url->hash === $url->url->hash,
                UriComparisonMode::IncludeFragment === $uriComparisonMode => $this->url->href === $url->url->href,
                default => self::urlRecord($this)->isEqual(self::urlRecord($url), true),
            };
        }

        /**
         * Retrieve the WHATWG URL object URLRecord property.
         *
         * The URLRecord is an internal representation;
         * therefore, we use reflection to access it
         */
        private static function urlRecord(self $url): URLRecord
        {
            /** @var ?ReflectionProperty $property */
            static $property = null;
            $property ??= (new ReflectionClass(WhatWgURL::class))->getProperty('url');

            /** @var URLRecord $urlRecord */
            $urlRecord = $property->getValue($url->url);

            return $urlRecord;
        }

        public function toAsciiString(): string
        {
            return $this->url->href;
        }

        public function toUnicodeString(): string
        {
            if (null !== $this->urlUnicodeString) {
                return $this->urlUnicodeString;
            }

            $unicodeHost = $this->getUnicodeHost();
            if (null === $unicodeHost || $this->getAsciiHost() === $unicodeHost) {
                $this->urlUnicodeString = $this->url->href;

                return $this->urlUnicodeString;
            }

            $urlRecord = self::urlRecord($this);
            $urlRecord->host = new StringHost($unicodeHost);
            $this->urlUnicodeString = $urlRecord->serializeURL();

            return $this->urlUnicodeString;
        }

        /**
         * @param list<UrlValidationError> $softErrors
         *
         * @throws InvalidUrlException
         */
        public function resolve(string $uri, array &$softErrors = []): self
        {
            return new self($uri, $this, $softErrors);
        }

        /**
         * @return UriSerializedShape
         */
        public function __serialize(): array
        {
            return [['uri' => $this->url->href], []];
        }

        /**
         * @param UriSerializedShape $data
         *
         * @throws Exception|InvalidUrlException
         */
        public function __unserialize(array $data): void
        {
            [$properties] = $data;
            $uri = new self($properties['uri'] ?? throw new Exception('The `uri` property is missing from the serialized object.'));
            $this->url = $uri->url;
        }

        /**
         * @return UriDebugShape
         */
        public function __debugInfo(): array
        {
            return [
                'scheme' => $this->getScheme(),
                'username' => $this->getUsername(),
                'password' => $this->getPassword(),
                'host' => $this->getAsciiHost(),
                'port' => $this->getPort(),
                'path' => $this->getPath(),
                'query' => $this->getQuery(),
                'fragment' => $this->getFragment(),
            ];
        }
    }
}
