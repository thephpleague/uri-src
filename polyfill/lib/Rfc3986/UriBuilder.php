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

use function array_map;
use function implode;
use function str_replace;
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
        private ?string $scheme = null;
        private ?string $userInfo = null;
        private ?string $host = null;
        private ?int $port = null;
        private ?string $path = null;
        private ?string $query = null;
        private ?string $fragment = null;

        /**
         * @throws InvalidUriException
         */
        public function setScheme(?string $scheme): self
        {
            if ($scheme !== $this->scheme) {
                UriString::isValidScheme($scheme)
                || throw new InvalidUriException('The scheme `'.$scheme.'` is invalid.');

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
                || throw new InvalidUriException('The userInfo `'.$userInfo.'` contains invalid characters.');

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
                || (UriString::containsRfc3986Chars($host) && HostRecord::validate($host))
                || throw new InvalidUriException('The host `'.$host.'` is invalid.');

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
                null === $port
                || ($port >= 0 && $port < 65535)
                || throw new InvalidUriException('The port value must be null or an integer between 0 and 65535.');

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
                || throw new InvalidUriException('The path `'.$path.'` contains invalid characters.');

                $this->path = $path;
            }

            return $this;
        }

        /**
         * @param list<string> $segments
         *
         * @throws InvalidUriException
         */
        public function setPathSegments(array $segments): self
        {
            return $this->setPath(
                [] === $segments
                    ? null
                    : implode('/', array_map(
                        fn (string $segment): string => str_replace('/', '%2F', $segment),
                        $segments
                    ))
            );
        }

        /**
         * @throws InvalidUriException
         */
        public function setQuery(?string $query): self
        {
            if ($query !== $this->query) {
                null === $query
                || (UriString::containsRfc3986Chars($query) && Encoder::isQueryEncoded($query))
                || throw new InvalidUriException('The query string `'.$query.'` contains invalid characters.');

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
                || throw new InvalidUriException('The fragment string `'.$fragment.'` contains invalid characters.');

                $this->fragment = $fragment;
            }

            return $this;
        }

        /**
         * @throws InvalidUriException
         */
        public function build(?Uri $baseUri = null): Uri
        {
            $authority = $this->buildAuthority();

            return new Uri(
                UriString::buildUri(
                    $this->scheme,
                    $authority,
                    $this->buildPath($authority),
                    $this->query,
                    $this->fragment
                ),
                $baseUri
            );
        }

        /**
         * @throws InvalidUriException
         */
        private function buildAuthority(): ?string
        {
            if (null === $this->host) {
                (null === $this->userInfo && null === $this->port)
                || throw new InvalidUriException('The UserInfo and/or the port component are set without a host component being present.');

                return null;
            }

            $authority = '';
            if (null !== $this->userInfo) {
                $authority .= $this->userInfo.'@';
            }

            $authority .= $this->host;
            if (null !== $this->port) {
                $authority .= ':'.$this->port;
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
                // If there is an authority, the path must start with a `/`
                return str_starts_with($this->path, '/') ? $this->path : '/'.$this->path;
            }

            // If there is no authority, the path cannot start with `//`
            if (str_starts_with($this->path, '//')) {
                return '/.'.$this->path;
            }

            $colonPos = strpos($this->path, ':');
            if (false !== $colonPos && null === $this->scheme) {
                // In the absence of a scheme and of an authority,
                // the first path segment cannot contain a colon (":") character.'
                $slashPos = strpos($this->path, '/');
                (false !== $slashPos && $colonPos > $slashPos) || throw new InvalidUriException(
                    'In absence of the scheme and authority components, the first path segment cannot contain a colon (":") character.'
                );
            }

            return $this->path;
        }
    }
}
