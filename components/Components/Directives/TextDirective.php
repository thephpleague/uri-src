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

namespace League\Uri\Components\Directives;

use League\Uri\Encoder;
use League\Uri\Exceptions\SyntaxError;
use Stringable;

use function explode;
use function preg_match;
use function str_replace;

final class TextDirective implements Directive
{
    private const NAME = 'text';

    private const REGEXP_PATTERN = '/^
        (?:(?<prefix>.+?)-,)?    # optional prefix up to first "-,"
        (?<start>[^,]+?)         # required start (up to "," or end)
        (?:,(?<end>[^,-]*),?)?   # optional end, stop before ",-" if present
        (?:,-(?<suffix>.+))?     # optional suffix (to end)
    $/x';

    public function __construct(
        public readonly string $start,
        public readonly ?string $end = null,
        public readonly ?string $prefix = null,
        public readonly ?string $suffix = null,
    ) {
    }

    /**
     * Create a new instance from a string without the Directive delimiter (:~:) or a separator (&).
     */
    public static function fromString(Stringable|string $value): self
    {
        [$name, $value] = explode('=', (string) $value, 2) + [1 => ''];
        self::NAME === $name || throw new SyntaxError('The submitted text is not a text directive.');

        return self::fromValue($value);
    }

    /**
     * Create a new instance from a string without the Directive name and the separator (=).
     */
    public static function fromValue(Stringable|string $text): self
    {
        if ('' === $text) {
            return new self('');
        }

        1 === preg_match(self::REGEXP_PATTERN, (string) $text, $matches) || throw new SyntaxError('The submitted text is not a text directive.');
        if ('' === $matches['prefix']) {
            $matches['prefix'] = null;
        }

        $matches['suffix'] ??= null;
        $matches['end'] ??= null;
        if ('' === $matches['end']) {
            $matches['end'] = null;
        }

        return new self(
            (string) self::decode($matches['start']),
            self::decode($matches['end']),
            self::decode($matches['prefix']),
            self::decode($matches['suffix']),
        );
    }

    private static function encode(?string $value): ?string
    {
        return null !== $value ? strtr((string) Encoder::encodeQueryOrFragment($value), ['-' => '%2D', ',' => '%2C', '&' => '%26']) : null;
    }

    private static function decode(?string $value): ?string
    {
        return null !== $value ? str_replace('%20', ' ', (string) Encoder::decodeFragment($value)) : null;
    }

    public function name(): string
    {
        return self::NAME;
    }

    public function value(): string
    {
        $str = '';
        if (null !== $this->prefix) {
            $str .= $this->prefix.'-,';
        }

        $str .= $this->start;
        if (null !== $this->end) {
            $str .= ','.$this->end;
        }

        if (null !== $this->suffix) {
            $str .= ',-'.$this->suffix;
        }

        return $str;
    }

    public function toString(): string
    {
        $encodedValue = '';
        if (null !== $this->prefix) {
            $encodedValue .= self::encode($this->prefix).'-,';
        }

        $encodedValue .= self::encode($this->start);
        if (null !== $this->end) {
            $encodedValue .= ','.self::encode($this->end);
        }

        if (null !== $this->suffix) {
            $encodedValue .= ',-'.self::encode($this->suffix);
        }

        return self::NAME.'='.$encodedValue;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Returns a new instance with a new start portion.
     *
     *  This method MUST retain the state of the current instance, and return
     *  an instance that contains the new start portion.
     */
    public function startingOn(string $start): self
    {
        if ($this->start === $start) {
            return $this;
        }

        return new self($start, $this->end, $this->prefix, $this->suffix);
    }

    /**
     * Returns a new instance with a new end portion.
     *
     *  This method MUST retain the state of the current instance, and return
     *  an instance that contains the new end portion.
     */
    public function endingOn(?string $end): self
    {
        if ($this->end === $end) {
            return $this;
        }

        return new self($this->start, $end, $this->prefix, $this->suffix);
    }

    /**
     * Returns a new instance with a new suffix portion.
     *
     *  This method MUST retain the state of the current instance, and return
     *  an instance that contains the new suffix portion.
     */
    public function trailedBy(?string $suffix): self
    {
        if ($this->suffix === $suffix) {
            return $this;
        }

        return new self($this->start, $this->end, $this->prefix, $suffix);
    }

    /**
     * Returns a new instance with a new prefix portion.
     *
     *  This method MUST retain the state of the current instance, and return
     *  an instance that contains the new prefix portion.
     */
    public function leadBy(?string $prefix): self
    {
        if ($this->prefix === $prefix) {
            return $this;
        }

        return new self($this->start, $this->end, $prefix, $this->suffix);
    }
}
