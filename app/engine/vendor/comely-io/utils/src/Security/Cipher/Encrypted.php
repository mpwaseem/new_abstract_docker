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

use Comely\Utils\Security\Exception\CipherException;

/**
 * Class Encrypted
 * All items (Strings,Integer,Floats,Arrays and Objects) are stored in this class first to preserve their data types,
 * and it also allows Cipher encrypt method to accept Arrays and Objects as argument
 * @package Comely\Utils\Security\Cipher
 */
class Encrypted implements \Serializable
{
    /** @var string */
    private $type;
    /** @var string */
    private $data;

    /**
     * Encrypted constructor.
     * @param $data
     * @throws CipherException
     */
    public function __construct($data)
    {
        $this->type = gettype($data);
        switch ($this->type) {
            case "integer":
            case "double":
            case "string":
                $this->data = $data;
                break;
            case "array":
            case "object":
                $this->data = base64_encode(serialize($data));
                break;
            default:
                throw new CipherException(sprintf('Cannot encrypt data of type "%s"', $this->type));
        }
    }

    /**
     * @return string
     */
    public function serialize(): string
    {
        return strlen($this->type) . $this->type . $this->data;
    }

    /**
     * @param string $serialized
     * @throws CipherException
     */
    public function unserialize($serialized)
    {
        $typeLen = intval(substr($serialized, 0, 1));
        $this->type = substr($serialized, 1, $typeLen);
        $this->data = substr($serialized, $typeLen + 1);
        if ($this->type === false || $this->data === false) {
            throw new CipherException('Failed to unserialize encrypted obj');
        }
    }

    /**
     * @return mixed|string
     * @throws CipherException
     */
    public function data()
    {
        switch ($this->type) {
            case "integer":
            case "double":
            case "string":
                return $this->data;
            case "array":
            case "object":
                $obj = unserialize(base64_decode($this->data));
                if ($obj === false || gettype($obj) !== $this->type) {
                    throw new CipherException(
                        sprintf('Failed to unserialize encrypted data of type "%s"', $this->type)
                    );
                }
                return $obj;
        }

        throw new CipherException('Invalid encrypted data type');
    }
}