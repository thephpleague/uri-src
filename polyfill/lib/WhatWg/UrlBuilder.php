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

use League\Uri\HostRecord;
use Rowbot\URL\Component\Host\HostInterface;
use Rowbot\URL\Component\Host\NullHost;
use Rowbot\URL\Component\Host\StringHost;
use Rowbot\URL\Component\OpaquePath;
use Rowbot\URL\Component\PathInterface;
use Rowbot\URL\Component\PathList;
use Rowbot\URL\Component\PathSegment;
use Rowbot\URL\Component\Scheme;
use Rowbot\URL\String\Utf8String;
use Rowbot\URL\URLRecord;
use SensitiveParameter;

use function array_map;
use function explode;
use function preg_match;
use function substr;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80600) {
    final class UrlBuilder
    {
        /** @var int */
        private const PORT_RANGE_MIN = 0;
        /** @var int */
        private const PORT_RANGE_MAX = 65535;

        private Scheme $scheme;
        private Utf8String $username;
        private Utf8String $password;
        private HostInterface $host;
        private ?int $port;
        private PathInterface $path;
        private ?string $query;
        private ?string $fragment;

        public function __construct()
        {
            $this->clear();
        }

        public function clear(): self
        {
            $this->scheme = new Scheme();
            $this->username = new Utf8String();
            $this->password = new Utf8String();
            $this->host = new NullHost();
            $this->port = null;
            $this->path = new PathList();
            $this->query = null;
            $this->fragment = null;

            return $this;
        }

        /**
         * @param ?list<UrlValidationError> $errors
         *
         * @throws InvalidUrlException
         */
        public function build(?Url $baseUrl = null, ?array &$errors = null): Url
        {
            $urlRecord = new URLRecord();
            $urlRecord->scheme = $this->scheme;
            $urlRecord->setUsername($this->username);
            $urlRecord->setPassword($this->password);
            $urlRecord->host = $this->host;
            $urlRecord->port = $this->port;
            $urlRecord->path = (!$this->scheme->isSpecial() || !$this->path instanceof OpaquePath)
                ? $this->path
                : new PathList(array_map(
                    fn (string $path) => new PathSegment($path),
                    explode('/', (string) $this->path)
                ));
            $urlRecord->query = $this->query;
            $urlRecord->fragment = $this->fragment;
            $uri = $urlRecord->serializeURL();

            $errors = [];
            $mustFail = false;
            if ('' === (string) $this->scheme) {
                $errors[] = new UrlValidationError(
                    context: $uri,
                    type: UrlValidationErrorType::MissingSchemeNonRelativeUrl,
                    failure: true
                );
                $mustFail = true;
            }

            if ($this->host == new NullHost()) {
                if ('' !== $this->password->__toString() || '' !== $this->username->__toString()) {
                    $errors[] = new UrlValidationError(
                        context: $uri,
                        type: UrlValidationErrorType::InvalidCredentials,
                        failure: false
                    );
                }

                if ($this->scheme->isSpecial()) {
                    $errors[] = new UrlValidationError(
                        context: $this->scheme->__toString(),
                        type: UrlValidationErrorType::HostMissing,
                        failure: true
                    );
                    $mustFail = true;
                }
            }

            !$mustFail || throw new InvalidUrlException('Invalid URL', $errors);

            $errors = [];
            return new Url($uri, $baseUrl, $errors);
        }

        /**
         * @throws InvalidUrlException
         */
        public function setScheme(?string $scheme): self
        {
            if (null !== $scheme) {
                static $regexp = ',^(?<scheme>[a-zA-Z][a-zA-Z0-9+\-.]*)(:(?://?)?)?$,';
                1 === preg_match($regexp, $scheme, $found) || throw new InvalidUrlException('The scheme `'.$scheme.'` is invalid.');
                $scheme = $found['scheme'];
            }

            $scheme = new Scheme($scheme ?? '');
            if (! $this->scheme->equals($scheme)) {
                $this->scheme = $scheme;
            }

            return $this;
        }

        public function setUsername(?string $username): self
        {
            $username = new Utf8String($username ?? '');
            if ((string) $this->username !== (string) $username) {
                $this->username = $username;
            }

            return $this;
        }

        public function setPassword(#[SensitiveParameter] ?string $password): self
        {
            $password = new Utf8String($password ?? '');
            if ((string) $this->password !== (string) $password) {
                $this->password = $password;
            }

            return $this;
        }

        /**
         * @throws InvalidUrlException
         */
        public function setHost(?string $host): self
        {
            HostRecord::isValid($host) || throw new InvalidUrlException('The host `'.$host.'` is invalid.');
            $host = null === $host ? new NullHost() : new StringHost($host);
            if (!$this->host->equals($host)) {
                $this->host = $host;
            }

            return $this;
        }

        /**
         * @throws InvalidUrlException
         */
        public function setPort(?int $port): self
        {
            if ($this->port !== $port) {
                null === $port || ($port >= self::PORT_RANGE_MIN && $port < self::PORT_RANGE_MAX) || throw new InvalidUrlException('The port value must be null or an integer between 0 and 65535.');

                $this->port = $port;
            }

            return $this;
        }

        public function setPath(?string $path): self
        {
            return $this->assignPath(new OpaquePath(new PathSegment($path ?? '')));
        }

        /**
         * @param list<string> $segments
         */
        public function setPathSegments(array $segments): self
        {
            return $this->assignPath(new PathList(array_map(fn (string $s): PathSegment => new PathSegment($s), $segments)));
        }

        private function assignPath(PathInterface $path): self
        {
            if ($this->path->__toString() !== $path->__toString()) {
                $this->path = $path;
            }

            return $this;
        }

        public function setQuery(?string $query): self
        {
            if (null !== $query && '?' === $query[0]) {
                $query = substr($query, 1);
            }

            if ($this->query !== $query) {
                $this->query = $query;
            }

            return $this;
        }

        public function setFragment(?string $fragment): self
        {
            if (null !== $fragment && '#' === $fragment[0]) {
                $fragment = substr($fragment, 1);
            }

            if ($this->fragment !== $fragment) {
                $this->fragment = $fragment;
            }

            return $this;
        }
    }
}
