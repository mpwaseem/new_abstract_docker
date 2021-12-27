<?php
/**
 * This file is a part of "comely-io/utils" package.
 * https://github.com/comely-io/utils
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comely-io/utils/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\Utils\Security\Cipher;

use Comely\DataTypes\Buffer\Binary;
use Comely\Utils\Security\Exception\CipherException;

/**
 * Class OpenSSL
 * @package Comely\Utils\Security\Cipher
 */
class OpenSSL
{
    /**
     * @param string $method
     * @param string $data
     * @param Binary $key
     * @param Binary|null $iv
     * @param bool $zeroPad
     * @return Binary
     * @throws CipherException
     */
    public static function encrypt(string $method, string $data, Binary $key, Binary $iv, bool $zeroPad = false): Binary
    {
        $options = OPENSSL_RAW_DATA;
        if ($zeroPad) {
            $options = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;
        }

        $encrypted = openssl_encrypt($data, $method, $key->raw(), $options, $iv->raw());
        if (!$encrypted) {
            throw new CipherException('Failed to encrypt data with OpenSSL');
        }

        return new Binary($encrypted);
    }

    /**
     * @param string $method
     * @param Binary $encrypted
     * @param Binary $key
     * @param Binary $iv
     * @param bool $zeroPad
     * @return string
     * @throws CipherException
     */

    public static function decrypt(string $method, Binary $encrypted, Binary $key, Binary $iv, bool $zeroPad = false)
    {
        $options = OPENSSL_RAW_DATA;
        if ($zeroPad) {
            $options = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;
        }

        $decrypted = openssl_decrypt($encrypted->raw(), $method, $key->raw(), $options, $iv->raw());
        if (!$decrypted) {
            throw new CipherException('Failed to decrypt data with OpenSSL');
        }

        return $decrypted;
    }
}