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

use Exception;
use Uri\InvalidUriException;
use ValueError;

use function array_is_list;

use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80500) {
    /**
     * This is a user-land polyfill to the native Uri\WhatWg\InvalidUrlException class included in PHP8.5.
     *
     * @see https://wiki.php.net/rfc/url_parsing_api
     */
    class InvalidUrlException extends InvalidUriException
    {
        /** @var list<UrlValidationError> */
        public readonly array $errors;

        /**
         * @param list<UrlValidationError> $errors
         */
        public function __construct(string $message, array $errors = [], int $code = 0, ?Exception $previous = null)
        {
            if (!array_is_list($errors)) {
                throw new ValueError('the error argument must be a list.');
            }

            $filter = static fn (mixed $error): bool => $error instanceof UrlValidationError;
            if ($errors !== array_filter($errors, $filter)) {
                throw new ValueError('the error argument must be a list containing only '.UrlValidationError::class);
            }

            $title = [];
            foreach ($errors as $error) {
                $title[] = $error->type->name;
            }

            parent::__construct($message.' ('.implode(' ', $title).')', $code, $previous);

            $this->errors = $errors;
        }
    }
}
