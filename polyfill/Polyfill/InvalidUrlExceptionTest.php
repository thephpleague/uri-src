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

namespace League\Uri\Polyfill;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Uri\WhatWg\InvalidUrlException;
use ValueError;

#[Group('uri-polyfill')]
final class InvalidUrlExceptionTest extends TestCase
{
    #[Test]
    public function it_can_not_be_instantiated_with_an_array_which_is_not_a_list(): void
    {
        $this->expectException(ValueError::class);

        new InvalidUrlException('message', ['foo' => 'bar']); /* @phpstan-ignore-line */
    }

    #[Test]
    public function it_can_not_be_instantiated_with_a_list_which_contains_other_than_url_validation_error_instances(): void
    {
        $this->expectException(ValueError::class);

        new InvalidUrlException('message', ['error']); /* @phpstan-ignore-line */
    }
}
