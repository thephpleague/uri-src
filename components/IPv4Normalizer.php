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

use League\Uri\Components\Host;
use League\Uri\Contracts\AuthorityInterface;
use League\Uri\Contracts\HostInterface;
use League\Uri\Contracts\UriInterface;
use League\Uri\Exceptions\IPv4CalculatorMissing;
use League\Uri\IPv4Calculators\BCMathCalculator;
use League\Uri\IPv4Calculators\GMPCalculator;
use League\Uri\IPv4Calculators\IPv4Calculator;
use League\Uri\IPv4Calculators\NativeCalculator;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use function array_pop;
use function count;
use function explode;
use function extension_loaded;
use function ltrim;
use function preg_match;
use function sprintf;
use function substr;
use const PHP_INT_SIZE;

final class IPv4Normalizer
{
    private const REGEXP_IPV4_HOST = '/
        (?(DEFINE) # . is missing as it is used to separate labels
            (?<hexadecimal>0x[[:xdigit:]]*)
            (?<octal>0[0-7]*)
            (?<decimal>\d+)
            (?<ipv4_part>(?:(?&hexadecimal)|(?&octal)|(?&decimal))*)
        )
        ^(?:(?&ipv4_part)\.){0,3}(?&ipv4_part)\.?$
    /x';
    private const REGEXP_IPV4_NUMBER_PER_BASE = [
        '/^0x(?<number>[[:xdigit:]]*)$/' => 16,
        '/^0(?<number>[0-7]*)$/' => 8,
        '/^(?<number>\d+)$/' => 10,
    ];

    private mixed $maxIpv4Number;

    public function __construct(private IPv4Calculator $calculator)
    {
        $this->maxIpv4Number = $calculator->sub($calculator->pow(2, 32), 1);
    }

    /**
     * Returns an instance using a GMP calculator.
     */
    public static function createFromGMP(): self
    {
        return new self(new GMPCalculator());
    }

    /**
     * Returns an instance using a Bcmath calculator.
     */
    public static function createFromBCMath(): self
    {
        return new self(new BCMathCalculator());
    }

    /**
     * Returns an instance using a PHP native calculator (requires 64bits PHP).
     */
    public static function createFromNative(): self
    {
        return new self(new NativeCalculator());
    }

    /**
     * Returns an instance using a detected calculator depending on the PHP environment.
     *
     * @throws IPv4CalculatorMissing If no IPv4Calculator implementing object can be used
     *                               on the platform
     *
     * @codeCoverageIgnore
     */
    public static function createFromServer(): self
    {
        return match (true) {
            extension_loaded('gmp') => self::createFromGMP(),
            extension_loaded('bcmath') => self::createFromBCMath(),
            4 < PHP_INT_SIZE => self::createFromNative(),
            default => throw new IPv4CalculatorMissing(sprintf(
                'No %s found. Use a x.64 PHP build or install the GMP or the BCMath extension.',
                IPv4Calculator::class
            ))
        };
    }

    /**
     * Normalizes the URI host content to a IPv4 dot-decimal notation if possible
     * otherwise returns the uri instance unchanged.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv4-parser
     */
    public function normalizeUri(UriInterface|Psr7UriInterface $uri): UriInterface|Psr7UriInterface
    {
        $host = Host::createFromUri($uri);
        $normalizedHost = $this->normalizeHost($host)->value();

        return match (true) {
            $normalizedHost === $host->value() => $uri,
            $uri instanceof UriInterface => $uri->withHost($normalizedHost),
            default => $uri->withHost((string) $normalizedHost),
        };
    }

    /**
     * Normalizes the authority host content to a IPv4 dot-decimal notation if possible
     * otherwise returns the uri instance unchanged.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv4-parser
     */
    public function normalizeAuthority(AuthorityInterface $authority): AuthorityInterface
    {
        $host = Host::createFromAuthority($authority);
        $normalizeHost = $this->normalizeHost($host)->value();

        if ($normalizeHost === $host->value()) {
            return $authority;
        }

        return $authority->withHost($normalizeHost);
    }

    /**
     * Normalizes the host content to a IPv4 dot-decimal notation if possible
     * otherwise returns the Host instance unchanged.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv4-parser
     */
    public function normalizeHost(HostInterface $host): HostInterface
    {
        if (!$host->isDomain()) {
            return $host;
        }

        $hostString = (string) $host;
        if ('' === $hostString || 1 !== preg_match(self::REGEXP_IPV4_HOST, $hostString)) {
            return $host;
        }

        if (str_ends_with($hostString, '.')) {
            $hostString = substr($hostString, 0, -1);
        }

        $ipv4host = $this->convertHost($hostString);
        if (null === $ipv4host) {
            return $host;
        }

        return new Host($ipv4host);
    }

    /**
     * Converts a IPv4 hexadecimal or a octal notation into a IPv4 dot-decimal notation.
     *
     * Returns null if it can not correctly convert the label
     *
     * @see https://url.spec.whatwg.org/#concept-ipv4-parser
     */
    private function convertHost(string $hostString): ?string
    {
        $numbers = [];
        foreach (explode('.', $hostString) as $label) {
            $number = $this->labelToNumber($label);
            if (null === $number) {
                return null;
            }

            $numbers[] = $number;
        }

        $ipv4 = array_pop($numbers);
        $max = $this->calculator->pow(256, 6 - count($numbers));
        if ($this->calculator->compare($ipv4, $max) > 0) {
            return null;
        }

        foreach ($numbers as $offset => $number) {
            if ($this->calculator->compare($number, 255) > 0) {
                return null;
            }

            $ipv4 = $this->calculator->add($ipv4, $this->calculator->multiply(
                $number,
                $this->calculator->pow(256, 3 - $offset)
            ));
        }

        return $this->long2Ip($ipv4);
    }

    /**
     * Converts a domain label into a IPv4 integer part.
     *
     * @see https://url.spec.whatwg.org/#ipv4-number-parser
     *
     * @return mixed Returns null if it can not correctly convert the label
     */
    private function labelToNumber(string $label): mixed
    {
        foreach (self::REGEXP_IPV4_NUMBER_PER_BASE as $regexp => $base) {
            if (1 !== preg_match($regexp, $label, $matches)) {
                continue;
            }

            $number = ltrim($matches['number'], '0');
            if ('' === $number) {
                return 0;
            }

            $number = $this->calculator->baseConvert($number, $base);
            if (0 <= $this->calculator->compare($number, 0) && 0 >= $this->calculator->compare($number, $this->maxIpv4Number)) {
                return $number;
            }
        }

        return null;
    }

    /**
     * Generates the dot-decimal notation for IPv4.
     *
     * @see https://url.spec.whatwg.org/#concept-ipv4-parser
     *
     * @param mixed $ipAddress the number representation of the IPV4address
     */
    private function long2Ip(mixed $ipAddress): string
    {
        $output = '';
        for ($offset = 0; $offset < 4; $offset++) {
            $output = $this->calculator->mod($ipAddress, 256).$output;
            if ($offset < 3) {
                $output = '.'.$output;
            }
            $ipAddress = $this->calculator->div($ipAddress, 256);
        }

        return $output;
    }
}
