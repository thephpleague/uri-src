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

use League\Uri\Contracts\UriInterface;
use League\Uri\Exceptions\IdnaConversionFailed;
use League\Uri\Exceptions\IdnSupportMissing;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\Idna\Idna;
use League\Uri\Idna\IdnaOption;
use Stringable;
use function array_merge;
use function explode;
use function filter_var;
use function inet_pton;
use function preg_match;
use function rawurldecode;
use function sprintf;
use function strpos;
use function substr;
use const FILTER_FLAG_IPV6;
use const FILTER_VALIDATE_IP;

/**
 * A class to parse a URI string according to RFC3986.
 *
 * @link    https://tools.ietf.org/html/rfc3986
 * @package League\Uri
 * @author  Ignace Nyamagana Butera <nyamsprod@gmail.com>
 * @since   6.0.0
 *
 * @phpstan-import-type ComponentMap from UriInterface
 * @phpstan-type InputComponentMap array{scheme? : ?string, user? : ?string, pass? : ?string, host? : ?string, port? : ?int, path? : ?string, query? : ?string, fragment? : ?string}
 * @phpstan-type AuthorityMap array{user:?string, pass:?string, host:?string, port:?int}
 */
final class UriString
{
    /**
     * Default URI component values.
     */
    private const URI_COMPONENTS = [
        'scheme' => null, 'user' => null, 'pass' => null, 'host' => null,
        'port' => null, 'path' => '', 'query' => null, 'fragment' => null,
    ];

    /**
     * Simple URI which do not need any parsing.
     */
    private const URI_SCHORTCUTS = [
        '' => [],
        '#' => ['fragment' => ''],
        '?' => ['query' => ''],
        '?#' => ['query' => '', 'fragment' => ''],
        '/' => ['path' => '/'],
        '//' => ['host' => ''],
    ];

    /**
     * Range of invalid characters in URI string.
     */
    private const REGEXP_INVALID_URI_CHARS = '/[\x00-\x1f\x7f]/';

    /**
     * RFC3986 regular expression URI splitter.
     *
     * @link https://tools.ietf.org/html/rfc3986#appendix-B
     */
    private const REGEXP_URI_PARTS = ',^
        (?<scheme>(?<scontent>[^:/?\#]+):)?    # URI scheme component
        (?<authority>//(?<acontent>[^/?\#]*))? # URI authority part
        (?<path>[^?\#]*)                       # URI path component
        (?<query>\?(?<qcontent>[^\#]*))?       # URI query component
        (?<fragment>\#(?<fcontent>.*))?        # URI fragment component
    ,x';

    /**
     * URI scheme regular expresssion.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.1
     */
    private const REGEXP_URI_SCHEME = '/^([a-z][a-z\d+.-]*)?$/i';

    /**
     * IPvFuture regular expression.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    private const REGEXP_IP_FUTURE = '/^
        v(?<version>[A-F0-9])+\.
        (?:
            (?<unreserved>[a-z0-9_~\-\.])|
            (?<sub_delims>[!$&\'()*+,;=:])  # also include the : character
        )+
    $/ix';

    /**
     * General registered name regular expression.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    private const REGEXP_REGISTERED_NAME = '/(?(DEFINE)
        (?<unreserved>[a-z0-9_~\-])   # . is missing as it is used to separate labels
        (?<sub_delims>[!$&\'()*+,;=])
        (?<encoded>%[A-F0-9]{2})
        (?<reg_name>(?:(?&unreserved)|(?&sub_delims)|(?&encoded))*)
    )
    ^(?:(?&reg_name)\.)*(?&reg_name)\.?$/ix';

    /**
     * Invalid characters in host regular expression.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    private const REGEXP_INVALID_HOST_CHARS = '/
        [:\/?#\[\]@ ]  # gen-delims characters as well as the space character
    /ix';

    /**
     * Invalid path for URI without scheme and authority regular expression.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.3
     */
    private const REGEXP_INVALID_PATH = ',^(([^/]*):)(.*)?/,';

    /**
     * Host and Port splitter regular expression.
     */
    private const REGEXP_HOST_PORT = ',^(?<host>\[.*\]|[^:]*)(:(?<port>.*))?$,';

    /**
     * IDN Host detector regular expression.
     */
    private const REGEXP_IDN_PATTERN = '/[^\x20-\x7f]/';

    /**
     * Only the address block fe80::/10 can have a Zone ID attach to
     * let's detect the link local significant 10 bits.
     */
    private const ZONE_ID_ADDRESS_BLOCK = "\xfe\x80";

    /**
     * Generate a URI string representation from its parsed representation
     * returned by League\UriString::parse() or PHP's parse_url.
     *
     * If you supply your own array, you are responsible for providing
     * valid components without their URI delimiters.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-5.3
     * @link https://tools.ietf.org/html/rfc3986#section-7.5
     *
     * @param InputComponentMap $components
     */
    public static function build(array $components): string
    {
        $uri = $components['path'] ?? '';
        if (isset($components['query'])) {
            $uri .= '?'.$components['query'];
        }

        if (isset($components['fragment'])) {
            $uri .= '#'.$components['fragment'];
        }

        $scheme = null;
        if (isset($components['scheme'])) {
            $scheme = $components['scheme'].':';
        }

        $authority = self::buildAuthority($components);
        if (null !== $authority) {
            $authority = '//'.$authority;
        }

        return $scheme.$authority.$uri;
    }

    /**
     * Generate a URI authority representation from its parsed representation.
     *
     * @param InputComponentMap $components
     */
    public static function buildAuthority(array $components): ?string
    {
        if (!isset($components['host'])) {
            return null;
        }

        $authority = $components['host'];
        if (isset($components['port'])) {
            $authority .= ':'.$components['port'];
        }

        if (!isset($components['user'])) {
            return $authority;
        }

        $authority = '@'.$authority;
        if (!isset($components['pass'])) {
            return $components['user'].$authority;
        }

        return $components['user'].':'.$components['pass'].$authority;
    }

    /**
     * Parse a URI string into its components.
     *
     * This method parses a URI and returns an associative array containing any
     * of the various components of the URI that are present.
     *
     * <code>
     * $components = UriString::parse('http://foo@test.example.com:42?query#');
     * var_export($components);
     * //will display
     * array(
     *   'scheme' => 'http',           // the URI scheme component
     *   'user' => 'foo',              // the URI user component
     *   'pass' => null,               // the URI pass component
     *   'host' => 'test.example.com', // the URI host component
     *   'port' => 42,                 // the URI port component
     *   'path' => '',                 // the URI path component
     *   'query' => 'query',           // the URI query component
     *   'fragment' => '',             // the URI fragment component
     * );
     * </code>
     *
     * The returned array is similar to PHP's parse_url return value with the following
     * differences:
     *
     * <ul>
     * <li>All components are always present in the returned array</li>
     * <li>Empty and undefined component are treated differently. And empty component is
     *   set to the empty string while an undefined component is set to the `null` value.</li>
     * <li>The path component is never undefined</li>
     * <li>The method parses the URI following the RFC3986 rules, but you are still
     *   required to validate the returned components against its related scheme specific rules.</li>
     * </ul>
     *
     * @link https://tools.ietf.org/html/rfc3986
     *
     * @throws SyntaxError if the URI contains invalid characters
     * @throws SyntaxError if the URI contains an invalid scheme
     * @throws SyntaxError if the URI contains an invalid path
     *
     * @return ComponentMap
     */
    public static function parse(Stringable|string|int $uri): array
    {
        $uri = (string) $uri;
        if (isset(self::URI_SCHORTCUTS[$uri])) {
            /** @var ComponentMap $components */
            $components = array_merge(self::URI_COMPONENTS, self::URI_SCHORTCUTS[$uri]);

            return $components;
        }

        if (1 === preg_match(self::REGEXP_INVALID_URI_CHARS, $uri)) {
            throw new SyntaxError(sprintf('The uri `%s` contains invalid characters', $uri));
        }

        //if the first character is a known URI delimiter parsing can be simplified
        $first_char = $uri[0];

        //The URI is made of the fragment only
        if ('#' === $first_char) {
            [, $fragment] = explode('#', $uri, 2);
            $components = self::URI_COMPONENTS;
            $components['fragment'] = $fragment;

            return $components;
        }

        //The URI is made of the query and fragment
        if ('?' === $first_char) {
            [, $partial] = explode('?', $uri, 2);
            [$query, $fragment] = explode('#', $partial, 2) + [1 => null];
            $components = self::URI_COMPONENTS;
            $components['query'] = $query;
            $components['fragment'] = $fragment;

            return $components;
        }

        //use RFC3986 URI regexp to split the URI
        preg_match(self::REGEXP_URI_PARTS, $uri, $parts);
        $parts += ['query' => '', 'fragment' => ''];

        if (':' === $parts['scheme'] || 1 !== preg_match(self::REGEXP_URI_SCHEME, $parts['scontent'])) {
            throw new SyntaxError(sprintf('The uri `%s` contains an invalid scheme', $uri));
        }

        if ('' === $parts['scheme'].$parts['authority'] && 1 === preg_match(self::REGEXP_INVALID_PATH, $parts['path'])) {
            throw new SyntaxError(sprintf('The uri `%s` contains an invalid path.', $uri));
        }

        /** @var ComponentMap $components */
        $components = array_merge(
            self::URI_COMPONENTS,
            '' === $parts['authority'] ? [] : self::parseAuthority($parts['acontent']),
            [
                'path' => $parts['path'],
                'scheme' => '' === $parts['scheme'] ? null : $parts['scontent'],
                'query' => '' === $parts['query'] ? null : $parts['qcontent'],
                'fragment' => '' === $parts['fragment'] ? null : $parts['fcontent'],
            ]
        );

        return $components;
    }

    /**
     * Parses the URI authority part.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2
     *
     * @throws SyntaxError If the port component is invalid
     *
     * @return AuthorityMap
     */
    public static function parseAuthority(?string $authority): array
    {
        $components = ['user' => null, 'pass' => null, 'host' => null, 'port' => null];
        if (null === $authority) {
            return $components;
        }

        $components['host'] = '';
        if ('' === $authority) {
            return $components;
        }

        $parts = explode('@', $authority, 2);
        if (isset($parts[1])) {
            [$components['user'], $components['pass']] = explode(':', $parts[0], 2) + [1 => null];
        }

        preg_match(self::REGEXP_HOST_PORT, $parts[1] ?? $parts[0], $matches);
        $matches += ['port' => ''];

        $components['port'] = self::filterPort($matches['port']);
        $components['host'] = self::filterHost($matches['host']);

        return $components;
    }

    /**
     * Filter and format the port component.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @throws SyntaxError if the registered name is invalid
     */
    private static function filterPort(string $port): ?int
    {
        return match (true) {
            '' === $port => null,
            1 === preg_match('/^\d*$/', $port) => (int) $port,
            default => throw new SyntaxError(sprintf('The port `%s` is invalid', $port)),
        };
    }

    /**
     * Returns whether a hostname is valid.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @throws SyntaxError if the registered name is invalid
     */
    private static function filterHost(string $host): string
    {
        return match (true) {
            '' === $host => '',
            '[' !== $host[0] || !str_ends_with($host, ']') => self::filterRegisteredName($host),
            !self::isIpHost(substr($host, 1, -1)) => throw new SyntaxError(sprintf('Host `%s` is invalid : the IP host is malformed', $host)),
            default => $host,
        };
    }

    /**
     * Returns whether the host is an IPv4 or a registered named.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     *
     * @throws SyntaxError       if the registered name is invalid
     * @throws IdnSupportMissing if IDN support or ICU requirement are not available or met.
     */
    private static function filterRegisteredName(string $host): string
    {
        $formatted_host = rawurldecode($host);
        if (1 === preg_match(self::REGEXP_REGISTERED_NAME, $formatted_host)) {
            return $host;
        }

        //to test IDN host non-ascii characters must be present in the host
        if (1 !== preg_match(self::REGEXP_IDN_PATTERN, $formatted_host)) {
            throw new SyntaxError(sprintf('Host `%s` is invalid : the host is not a valid registered name', $host));
        }

        $info = Idna::toAscii($host, IdnaOption::forIDNA2008Ascii());
        if ($info->hasErrors()) {
            throw IdnaConversionFailed::dueToIDNAError($host, $info);
        }

        return $host;
    }

    /**
     * Validates a IPv6/IPfuture host.
     *
     * @link https://tools.ietf.org/html/rfc3986#section-3.2.2
     * @link https://tools.ietf.org/html/rfc6874#section-2
     * @link https://tools.ietf.org/html/rfc6874#section-4
     */
    private static function isIpHost(string $ipHost): bool
    {
        if (false !== filter_var($ipHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        if (1 === preg_match(self::REGEXP_IP_FUTURE, $ipHost, $matches)) {
            return !in_array($matches['version'], ['4', '6'], true);
        }

        $pos = strpos($ipHost, '%');
        if (false === $pos || 1 === preg_match(self::REGEXP_INVALID_HOST_CHARS, rawurldecode(substr($ipHost, $pos)))) {
            return false;
        }

        $ipHost = substr($ipHost, 0, $pos);

        return false !== filter_var($ipHost, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            && str_starts_with((string)inet_pton($ipHost), self::ZONE_ID_ADDRESS_BLOCK);
    }
}
