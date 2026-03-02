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
use function explode;
use const PHP_VERSION_ID;

if (PHP_VERSION_ID < 80600) {
    /**
     * This is a user-land polyfill to the native Uri\Rfc3986\LeadingSlashPolicy Enum included in PHP8.6.
     *
     * @see https://wiki.php.net/rfc/uri_followup#percent-encoding_and_decoding_support
     */
    enum UriPercentEncoder
    {
        case UserInfo;
        case Host;
        case RelativeReferencePath;
        case RelativeReferenceFirstPathSegment;
        case Path;
        case PathSegment;
        case Query;
        case FormQuery;
        case Fragment;
        case AllReservedCharacters;
        case All;

        public function encode(string $input): string
        {
            return $input;
        }

        public function decode(string $input): string
        {
            if ($this === self::UserInfo) {
                [$user, $pass] = explode(':', $input, 2) + [1 => null];
                $user = (string) Encoder::decodeAll($user);
                if (null === $pass) {
                    return $user;
                }

                return $user.':'.Encoder::decodeAll($pass);
            }

            if ($this === self::Fragment) {
                return (string) Encoder::decodeAll($input);
            }

            return $input;
        }
    }
}
