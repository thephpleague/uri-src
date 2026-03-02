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
use function strtolower;
use function substr;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80600) {
    final class UrlBuilder
    {
        /** @var int */
        private const PORT_RANGE_MIN = 0;
        /** @var int */
        private const PORT_RANGE_MAX = 65535;
        private URLRecord $urlRecord;

        public function __construct()
        {
            $this->clear();
        }

        public function clear(): self
        {
            $this->urlRecord = new URLRecord();

            return $this;
        }

        /**
         * @param ?list<UrlValidationError> $errors
         *
         * @throws InvalidUrlException
         */
        public function build(?Url $baseUrl = null, ?array &$errors = null): Url
        {
            // To avoid internal rewrite of user input
            // building is done on a copy of user input
            $urlRecord = clone $this->urlRecord;
            if ($urlRecord->scheme->isSpecial() && $urlRecord->path instanceof OpaquePath) {
                $urlRecord->path = new PathList(array_map(
                    fn (string $path): PathSegment => new PathSegment($path),
                    explode('/', $urlRecord->path->__toString())
                ));
            }

            $uri = $urlRecord->serializeURL();
            $schemeIsEmpty = '' === (string) $urlRecord->scheme;
            $hostIsEmpty = $urlRecord->host->isNull() || $urlRecord->host->isEmpty();
            // Rowbot\URL\URLRecord always assume the scheme to be non-empty
            // in case of relative URI the scheme ':' delimiter character
            // must be removed
            if ($schemeIsEmpty) {
                $uri = substr($uri, 1);
            }

            // Validation is done after the uri string is generated
            // to build the Uri\WhatWg\UrlValidationError instances
            $errors = [];
            if ($schemeIsEmpty && null === $baseUrl) {
                $errors[] = new UrlValidationError($uri, UrlValidationErrorType::MissingSchemeNonRelativeUrl, true);
            }

            if ($hostIsEmpty && $urlRecord->scheme->isSpecial()) {
                $errors[] = new UrlValidationError($uri, UrlValidationErrorType::HostMissing, true);
            }

            if (
                ($hostIsEmpty || 'file' === strtolower((string) $urlRecord->scheme)) &&
                ('' !== $urlRecord->password || '' !== $urlRecord->username || null !== $urlRecord->port)
            ) {
                $errors[] = new UrlValidationError($uri, UrlValidationErrorType::DomainInvalidCodePoint, true);
            }

            [] === $errors || throw new InvalidUrlException('Invalid URL', $errors);

            return new Url($uri, $baseUrl, $errors);
        }

        /**
         * @throws InvalidUrlException
         */
        public function setScheme(?string $scheme): self
        {
            if (null !== $scheme) {
                static $regexp = ',^(?<scheme>[a-zA-Z][a-zA-Z0-9+\-.]*)(:(?://?)?)?$,';
                1 === preg_match($regexp, $scheme, $found) || throw new InvalidUrlException(
                    'The scheme `'.$scheme.'` is invalid.',
                    [new UrlValidationError($scheme, UrlValidationErrorType::MissingSchemeNonRelativeUrl, true)]
                );

                $scheme = $found['scheme'];
            }

            $scheme = new Scheme($scheme ?? '');
            if (! $this->urlRecord->scheme->equals($scheme)) {
                $this->urlRecord->scheme = $scheme;
            }

            return $this;
        }

        public function setUsername(?string $username): self
        {
            $username = Utf8String::fromUnsafe($username ?? '');
            if ($this->urlRecord->username !== (string) $username) {
                $this->urlRecord->setUsername($username);
            }

            return $this;
        }

        public function setPassword(#[SensitiveParameter] ?string $password): self
        {
            $password = Utf8String::fromUnsafe($password ?? '');
            if ($this->urlRecord->password !== (string) $password) {
                $this->urlRecord->setPassword($password);
            }

            return $this;
        }

        /**
         * @throws InvalidUrlException
         */
        public function setHost(?string $host): self
        {
            HostRecord::isValid($host) || throw new InvalidUrlException(
                'The host `'.$host.'` is invalid.',
                [new UrlValidationError((string) $host, UrlValidationErrorType::HostInvalidCodePoint, true)]
            );
            $host = null === $host ? new NullHost() : new StringHost($host);
            if (!$this->urlRecord->host->equals($host)) {
                $this->urlRecord->host = $host;
            }

            return $this;
        }

        /**
         * @throws InvalidUrlException
         */
        public function setPort(?int $port): self
        {
            if ($this->urlRecord->port !== $port) {
                null === $port
                || ($port >= self::PORT_RANGE_MIN && $port <= self::PORT_RANGE_MAX)
                || throw new InvalidUrlException(
                    'The port value must be null or an integer between '.self::PORT_RANGE_MIN.' and '.self::PORT_RANGE_MAX.'.',
                    [new UrlValidationError((string) $port, UrlValidationErrorType::PortOutOfRange, true)]
                );

                $this->urlRecord->port = $port;
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
            if ($this->urlRecord->path->__toString() !== $path->__toString()) {
                $this->urlRecord->path = $path;
            }

            return $this;
        }

        public function setQuery(?string $query): self
        {
            if (null !== $query && '?' === $query[0]) {
                $query = substr($query, 1);
            }

            if ($this->urlRecord->query !== $query) {
                $this->urlRecord->query = $query;
            }

            return $this;
        }

        public function setFragment(?string $fragment): self
        {
            if (null !== $fragment && '#' === $fragment[0]) {
                $fragment = substr($fragment, 1);
            }

            if ($this->urlRecord->fragment !== $fragment) {
                $this->urlRecord->fragment = $fragment;
            }

            return $this;
        }
    }
}
