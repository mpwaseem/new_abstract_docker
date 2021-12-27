<?php
/**
 * This file is a part of "comely-io/http" package.
 * https://github.com/comely-io/http
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comely-io/http/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\Http\Query;

use Comely\DataTypes\Buffer\AbstractBuffer;
use Comely\DataTypes\Buffer\Binary;

/**
 * Class Payload
 * @package Comely\Http\Query
 */
class Payload extends AbstractDataIterator
{
    /**
     * @return Payload
     */
    public function flush(): self
    {
        $this->data = [];
        $this->count = 0;
        return $this;
    }

    /**
     * @param array $data
     * @return int
     */
    public function use(array $data): int
    {
        $added = 0;
        foreach ($data as $key => $value) {
            $this->set(strval($key), $value);
            $added++;
        }

        return $added;
    }

    /**
     * @param string $key
     * @param $value
     * @return Payload
     */
    public function set(string $key, $value): self
    {
        // Key
        if (!preg_match('/^[\w\-]+$/i', $key)) {
            throw new \InvalidArgumentException('Invalid HTTP payload key');
        }

        // Value
        $prop = null;
        if (is_scalar($value) || is_null($value)) {
            $prop = new Prop($key, $value); // Scalar or NULL type
        } elseif (is_array($value) || is_object($value)) {
            if ($value instanceof AbstractBuffer) {
                $filtered = $value instanceof Binary ? $value->base16()->hexits(true) : $value->value();
            } else {
                $filtered = json_decode(json_encode($value), true);
                if (!is_array($filtered)) {
                    throw new \UnexpectedValueException('Could not set object/array Http Payload value');
                }
            }

            $prop = new Prop($key, $filtered); // Safe array
        }

        if (!$prop) {
            throw new \UnexpectedValueException(
                sprintf('Cannot set Http Payload value of type "%s"', gettype($value))
            );
        }

        $this->setProp($prop);
        return $this;
    }

    /**
     * Special method to retrieve values from child arrays (i.e. "user.prop1.prop1b" => $user["prop1"]["prop1b"])
     * @param string $key
     * @return array|float|int|mixed|string|null
     */
    public function find(string $key)
    {
        if (!preg_match('/^[\w\-]+(\.[\w\-]+)+$/i', $key)) {
            return $this->get($key);
        }

        $tokens = explode(".", $key);
        $arr = $this->get($tokens[0]);
        array_shift($tokens);

        if (!is_array($arr)) {
            return null;
        }

        $deep = array_map(function ($prop) {
            return sprintf('["%s"]', $prop);
        }, $tokens);

        $eval = "return \$arr" . implode($deep) . " ?? null;";
        return eval($eval);
    }

    /**
     * @param string $prop
     * @return array|float|int|string|null
     */
    public function get(string $prop)
    {
        $prop = $this->getProp($prop);
        return $prop ? $prop->value : null;
    }
}
