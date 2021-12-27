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

namespace Comely\Utils\Security;

use Comely\DataTypes\Buffer\Base16;
use Comely\DataTypes\Buffer\Binary;
use Comely\Utils\Security\Cipher\Encrypted;
use Comely\Utils\Security\Cipher\OpenSSL;
use Comely\Utils\Security\Exception\CipherException;

/**
 * Class Cipher
 * @package Comely\Utils\Security
 */
class Cipher
{
    public const CIPHER = "aes-256-cbc";
    public const KEY_LEN = 256;
    public const IV_LEN = 16;

    /** @var Binary */
    private $key;

    /**
     * @param Base16 $entropy
     * @return Cipher
     * @throws CipherException
     */
    public static function useBase16(Base16 $entropy): self
    {
        return new self($entropy->binary());
    }

    /**
     * Cipher constructor.
     * @param Binary $entropy
     * @throws CipherException
     */
    public function __construct(Binary $entropy)
    {
        if (!in_array(self::CIPHER, openssl_get_cipher_methods())) {
            throw new CipherException(
                sprintf('Cipher "%s" is not available in current OpenSSL build', self::CIPHER)
            );
        }

        $bitwiseLen = $entropy->bitwise()->len();
        if ($bitwiseLen !== self::KEY_LEN) {
            throw new CipherException(
                sprintf('Expecting %d bit entropy, got %d bit', self::KEY_LEN, $bitwiseLen)
            );
        }

        $this->key = $entropy->copy()->readOnly(true);
    }

    /**
     * @return array
     */
    public function debugInfo(): array
    {
        return [
            "cipher" => self::CIPHER,
            "key" => sprintf('%d-bit', self::KEY_LEN)
        ];
    }

    /**
     * @return Binary
     */
    public function key(): Binary
    {
        return $this->key;
    }

    /**
     * @param string $deterministicPhrase
     * @param int $iterations
     * @return Cipher
     * @throws CipherException
     */
    public function remix(string $deterministicPhrase, int $iterations = 1): self
    {
        return new self($this->pbkdf2("sha256", $deterministicPhrase, $iterations));
    }

    /**
     * @param $data
     * @return Binary
     * @throws CipherException
     * @throws Exception\PRNG_Exception
     */
    public function encrypt($data): Binary
    {
        $iv = PRNG::randomBytes(self::IV_LEN);
        $encrypted = OpenSSL::encrypt(self::CIPHER, serialize(new Encrypted($data)), $this->key, $iv);
        return new Binary($iv->raw() . $encrypted->raw());
    }

    /**
     * @param Binary $encrypted
     * @return mixed|string
     * @throws CipherException
     */
    public function decrypt(Binary $encrypted)
    {
        $iv = $encrypted->copy()->substr(0, self::IV_LEN);
        $encrypted->substr(self::IV_LEN);

        $decrypt = OpenSSL::decrypt(self::CIPHER, $encrypted, $this->key, $iv);
        $obj = unserialize($decrypt);
        if (!$obj || !$obj instanceof Encrypted) {
            throw new CipherException('Failed to unserialize encrypted object');
        }

        return $obj->data();
    }

    /**
     * @param string $algo
     * @param string $data
     * @return Binary
     * @throws CipherException
     */
    public function hmac(string $algo, string $data): Binary
    {
        $hmac = hash_hmac($algo, $data, $this->key->raw(), true);
        if (!$hmac) {
            throw new CipherException('Failed to compute HMAC');
        }

        return new Binary($hmac);
    }

    /**
     * @param string $algo
     * @param string $data
     * @param int $iterations
     * @return Binary
     */
    public function pbkdf2(string $algo, string $data, int $iterations): Binary
    {
        return new Binary(hash_pbkdf2($algo, $data, $this->key->raw(), $iterations, 0, true));
    }
}
