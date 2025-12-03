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
use League\Uri\Idna\Converter as IdnaConverter;
use League\Uri\UriString;
use Uri\InvalidUriException;

use function array_reduce;
use function array_shift;
use function count;
use function filter_var;
use function in_array;
use function inet_pton;
use function preg_match;
use function rawurldecode;
use function str_replace;
use function strpos;
use function substr;

use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;
use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80600) {
    /**
     * This is a user-land polyfill to the native Uri\Rfc3986\UriBuilder clas included in PHP8.6.
     *
     * @see https://wiki.php.net/rfc/uri_followup#host_type_detection
     */
    final class UriBuilder
    {
        /**
         * General registered name regular expression.
         *
         * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
         * @var string
         */
        private const REGEXP_REGISTERED_NAME = '/(?(DEFINE)
            (?<unreserved>[a-z0-9_~\-])   # . is missing as it is used to separate labels
            (?<sub_delims>[!$&\'()*+,;=])
            (?<encoded>%[A-F0-9]{2})
            (?<reg_name>(?:(?&unreserved)|(?&sub_delims)|(?&encoded))*)
        )
        ^(?:(?&reg_name)\.)*(?&reg_name)\.?$/ix';

        /**
         * IPvFuture regular expression.
         *
         * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
         * @var string
         */
        private const REGEXP_IP_FUTURE = '/^
            v(?<version>[A-F0-9])+\.
            (?:
                (?<unreserved>[a-z0-9_~\-\.])|
                (?<sub_delims>[!$&\'()*+,;=:])  # also include the : character
            )+
        $/ix';

        /**
         * Invalid characters in host regular expression.
         *
         * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
         * @var string
         */
        private const REGEXP_INVALID_HOST_CHARS = '/
            [:\/?#\[\]@ ]  # gen-delims characters as well as the space character
        /ix';

        /**
         * Only the address block fe80::/10 can have a Zone ID attach to
         * let's detect the link local significant 10 bits.
         *
         * @var string
         */
        private const ZONE_ID_ADDRESS_BLOCK = "\xfe\x80";

        /**
         * IDN Host detector regular expression.
         *
         * @var string
         */
        private const REGEXP_IDN_PATTERN = '/[^\x20-\x7f]/';

        /**
         * Maximum number of host cached.
         *
         * @var int
         */
        private const MAXIMUM_HOST_CACHED = 100;

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
        public function scheme(?string $scheme): self
        {
            if ($scheme === $this->scheme) {
                return $this;
            }

            UriString::isValidScheme($scheme) || throw new InvalidUriException('The scheme `'.$scheme.'` is invalid.');

            $clone = clone $this;
            $clone->scheme = $scheme;

            return $clone;
        }

        /**
         * @throws InvalidUriException
         */
        public function userInfo(?string $userInfo): self
        {
            if ($userInfo === $this->userInfo) {
                return $this;
            }

            null === $userInfo || (UriString::containsRfc3986Chars($userInfo) && Encoder::isUserInfoEncoded($userInfo)) || throw new InvalidUriException('The userInfo `'.$userInfo.'` contains invalid characters.');

            $clone = clone $this;
            $clone->userInfo = $userInfo;

            return $clone;
        }

        /**
         * @throws InvalidUriException
         */
        public function host(?string $host): self
        {
            if ($host === $this->host) {
                return $this;
            }

            self::isHost($host) || throw new InvalidUriException('The host `'.$host.'` is invalid.');

            $clone = clone $this;
            $clone->host = $host;

            return $this;
        }

        /**
         * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
         * @link https://tools.ietf.org/html/rfc6874#section-2
         * @link https://tools.ietf.org/html/rfc6874#section-4
         */
        private static function isHost(?string $host): bool
        {
            if (null === $host || '' === $host) {
                return true;
            }

            /** @var array<string, 1> $hostCache */
            static $hostCache = [];
            if (isset($hostCache[$host])) {
                return true;
            }

            if (self::MAXIMUM_HOST_CACHED < count($hostCache)) {
                array_shift($hostCache);
            }

             if (self::isValidHost($host)) {
                 $hostCache[$host] = true;

                 return true;
             }

             return false;
        }

        private static function isValidHost(string $host): bool
        {
            if ('[' !== $host[0] || !str_ends_with($host, ']')) {
                $formattedHost = rawurldecode($host);
                if ($formattedHost !== $host) {
                    return !IdnaConverter::toAscii($formattedHost)->hasErrors();
                }

                if (1 === preg_match(self::REGEXP_REGISTERED_NAME, $formattedHost)) {
                    return true;
                }

                //to test IDN host non-ascii characters must be present in the host
                if (1 !== preg_match(self::REGEXP_IDN_PATTERN, $formattedHost)) {
                    return false;
                }

                return !IdnaConverter::toAscii($host)->hasErrors();
            }

            if (false !== filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                return true;
            }

            if (1 === preg_match(self::REGEXP_IP_FUTURE, $host, $matches)) {
                return !in_array($matches['version'], ['4', '6'], true);
            }

            $pos = strpos($host, '%');
            if (false === $pos || 1 === preg_match(self::REGEXP_INVALID_HOST_CHARS, rawurldecode(substr($host, $pos)))) {
                return false;
            }

            $host = substr($host, 0, $pos);

            return false !== filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                && str_starts_with((string)inet_pton($host), self::ZONE_ID_ADDRESS_BLOCK);
        }

        /**
         * @throws InvalidUriException
         */
        public function port(?int $port): self
        {
            if ($port === $this->port) {
                return $this;
            }

            $port === null || ($port >= 0 && $port < 65535) || throw new InvalidUriException('The port value must be null or an integer between 0 and 65535.');

            $clone = clone $this;
            $clone->port = $port;

            return $clone;
        }

        /**
         * @throws InvalidUriException
         */
        public function path(?string $path): self
        {
            if ($path === $this->path) {
                return $this;
            }

            null === $path || '' === $path || (UriString::containsRfc3986Chars($path) && Encoder::isPathEncoded($path)) || throw new InvalidUriException('The path `'.$path.'` contains invalid characters.');

            $clone = clone $this;
            $clone->path = $path;

            return $clone;
        }

        /**
         * @param list<string> $segments
         *
         * @throws InvalidUriException
         */
        public function pathSegments(array $segments): self
        {
            /**
             * @param list<string> $carry
             * @param string $segment
             *
             * @throws InvalidUriException
             * @return list<string>
             */
            $formatSegments = static function (array $carry, string $segment): array {
                UriString::containsRfc3986Chars($segment) || throw new InvalidUriException('The path segment `'.$segment.'` contains invalid characters.');
                $carry[] = str_replace('/', '%2F', $segment);

                return $carry;
            };

            return $this->path([] === $segments ? null : implode('/', array_reduce($segments, $formatSegments, [])));
        }

        /**
         * @throws InvalidUriException
         */
        public function query(?string $query): self
        {
            if ($query === $this->query) {
                return $this;
            }

            null === $query || (UriString::containsRfc3986Chars($query) && Encoder::isQueryEncoded($query)) || throw new InvalidUriException('The query string `'.$query.'` contains invalid characters.');

            $clone = clone $this;
            $clone->query = $query;

            return $clone;
        }

        /**
         * @throws InvalidUriException
         */
        public function fragment(?string $fragment): self
        {
            if ($fragment === $this->fragment) {
                return $this;
            }

            null === $fragment || (UriString::containsRfc3986Chars($fragment) && Encoder::isFragmentEncoded($fragment)) || throw new InvalidUriException('The fragment string `'.$fragment.'` contains invalid characters.');
            $clone = clone $this;
            $clone->fragment = $fragment;

            return $clone;
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

        private function buildAuthority(): ?string
        {
            if (null === $this->host) {
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

        private function buildPath(?string $authority): string
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
            if (false === $colonPos) {
                return $this->path;
            }

            // In the absence of a scheme and of an authority,
            // the first path segment cannot contain a colon (":") character.'
            $slashPos = strpos($this->path, '/');
            if (false === $slashPos || $colonPos < $slashPos) {
                return './'.$this->path;
            }

            return $this->path;
        }
    }
}
