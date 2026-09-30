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

use League\Uri\Encoder;
use League\Uri\HostRecord;
use League\Uri\UriString;
use SensitiveParameter;
use Uri\InvalidUriException;

use function strpos;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80600) {
    /**
     * This is a user-land polyfill to the native Uri\Rfc3986\UriBuilder clas included in PHP8.6.
     *
     * @see https://wiki.php.net/rfc/uri_followup#uri_building
     */
    final class UriBuilder
    {
        private ?string $scheme;
        private ?string $userInfo;
        private ?string $host;
        private ?int $port;
        private ?string $path;
        private ?string $query;
        private ?string $fragment;

        public function __construct()
        {
            $this->reset();
        }

        public function reset(): self
        {
            $this->scheme = null;
            $this->userInfo = null;
            $this->host = null;
            $this->port = null;
            $this->path = null;
            $this->query = null;
            $this->fragment = null;

            return $this;
        }

        /**
         * @throws InvalidUriException
         */
        public function build(?Uri $baseUri = null): Uri
        {
            $path = $this->buildPath($authority = $this->buildAuthority());

            return new Uri(
                UriString::buildUri($this->scheme, $authority, $path, $this->query, $this->fragment),
                $baseUri
            );
        }

        /**
         * @throws InvalidUriException
         */
        private function buildAuthority(): ?string
        {
            if (null === $this->host) {
                null === $this->port || throw new InvalidUriException('Cannot set a port without having a host');
                null === $this->userInfo || throw new InvalidUriException('Cannot set a userinfo without having a host');

                return null;
            }

            $authority = $this->host;
            if (null !== $this->userInfo) {
                $authority = $this->userInfo.'@'.$authority;
            }

            if (null !== $this->port) {
                return $authority.':'.$this->port;
            }

            return $authority;
        }

        /**
         * @throws InvalidUriException
         */
        private function buildPath(?string $authority): ?string
        {
            if (null === $this->path || '' === $this->path) {
                return $this->path;
            }

            if (null !== $authority) {
                str_starts_with($this->path, '/') || throw new InvalidUriException('The specified path is malformed');

                return $this->path;
            }

            !str_starts_with($this->path, '//') || throw new InvalidUriException('The path must not begin with "//" when the URI does not contain a host');

            $colonPos = strpos($this->path, ':');
            if (false !== $colonPos && null === $this->scheme) {
                // In the absence of a scheme and of an authority,
                // the first path segment cannot contain a colon (":") character.'
                $slashPos = strpos($this->path, '/');
                (false !== $slashPos && $colonPos > $slashPos) || throw new InvalidUriException('The path must not begin with ":" when the URI does not contain a scheme');
            }

            return $this->path;
        }

        /**
         * @throws InvalidUriException
         */
        public function setScheme(?string $scheme): self
        {
            if ($scheme !== $this->scheme) {
                UriString::isValidScheme($scheme)
                || throw new InvalidUriException('The specified scheme is malformed');

                $this->scheme = $scheme;
            }

            return $this;
        }

        /**
         * @throws InvalidUriException
         */
        public function setUserInfo(#[SensitiveParameter] ?string $userInfo): self
        {
            if ($userInfo !== $this->userInfo) {
                null === $userInfo
                || (UriString::containsRfc3986Chars($userInfo) && Encoder::isUserInfoEncoded($userInfo))
                || throw new InvalidUriException('The specified userinfo is malformed');

                $this->userInfo = $userInfo;
            }

            return $this;
        }

        /**
         * @throws InvalidUriException
         */
        public function setHost(?string $host): self
        {
            if ($host !== $this->host) {
                null === $host
                || (UriString::containsRfc3986Chars($host) && HostRecord::isValid($host))
                || throw new InvalidUriException('The specified host is malformed');

                $this->host = $host;
            }

            return $this;
        }

        /**
         * @throws InvalidUriException
         */
        public function setPort(?int $port): self
        {
            if ($port !== $this->port) {
                null === $port || $port >= 0 || throw new InvalidUriException('The specified port is malformed');

                $this->port = $port;
            }

            return $this;
        }

        /**
         * @throws InvalidUriException
         */
        public function setPath(?string $path): self
        {
            if ($path !== $this->path) {
                null === $path
                || '' === $path
                || (UriString::containsRfc3986Chars($path) && Encoder::isPathEncoded($path))
                || throw new InvalidUriException('The specified path is malformed');

                $this->path = $path;
            }

            return $this;
        }

        /**
         * @throws InvalidUriException
         */
        public function setQuery(?string $query): self
        {
            if ($query !== $this->query) {
                null === $query
                || (UriString::containsRfc3986Chars($query) && Encoder::isQueryEncoded($query))
                || throw new InvalidUriException('The specified query is malformed');

                $this->query = $query;
            }

            return $this;
        }

        /**
         * @throws InvalidUriException
         */
        public function setFragment(?string $fragment): self
        {
            if ($fragment !== $this->fragment) {
                null === $fragment
                || (UriString::containsRfc3986Chars($fragment) && Encoder::isFragmentEncoded($fragment))
                || throw new InvalidUriException('The specified fragment is malformed');

                $this->fragment = $fragment;
            }

            return $this;
        }
    }
}
