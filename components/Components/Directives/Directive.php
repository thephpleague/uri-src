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

use Stringable;

/**
 * @see https://wicg.github.io/scroll-to-text-fragment/#the-fragment-directive
 */
interface Directive extends Stringable
{
    /**
     * The decoded Directive name.
     *
     * @return non-empty-string
     */
    public function name(): string;

    /**
     * The decoded Directive value.
     */
    public function value(): ?string;

    /**
     * The encoded string representation of the fragment.
     */
    public function toString(): string;

    /**
     * The encoded string representation of the fragment using
     * the Stringable interface.
     *
     * @see Directive::toString()
     */
    public function __toString(): string;

    /**
     * Tells whether the submitted value is equals to the string
     * representation of the given directive.
     */
    public function equals(mixed $directive): bool;
}
