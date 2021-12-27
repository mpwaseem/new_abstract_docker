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

/**
 * Class AbstractDataIterator
 * @package Comely\Http\Query
 */
abstract class AbstractDataIterator implements \Iterator, \Countable
{
    /** @var array */
    protected $data;
    /** @var int */
    protected $count;

    /**
     * AbstractDataIterator constructor.
     */
    public function __construct()
    {
        $this->data = [];
        $this->count = 0;
    }

    /**
     * @param Prop $prop
     * @return void
     */
    protected function setProp(Prop $prop): void
    {
        $this->data[strtolower($prop->key)] = $prop;
        $this->count++;
    }

    /**
     * @param string $key
     * @return Prop|null
     */
    protected function getProp(string $key): ?Prop
    {
        return $this->data[strtolower($key)] ?? null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists(strtolower($key), $this->data);
    }

    /**
     * @return array
     */
    final public function array(): array
    {
        $array = [];
        /** @var Prop $prop */
        foreach ($this->data as $key => $prop) {
            $array[$prop->key] = $prop->value;
        }

        return $array;
    }

    /**
     * @return int
     */
    final public function count(): int
    {
        return $this->count;
    }

    /**
     * @return void
     */
    final public function rewind(): void
    {
        reset($this->data);
    }

    /**
     * @return mixed
     */
    final public function current()
    {
        /** @var Prop $prop */
        $prop = current($this->data);
        return $prop->value;
    }

    /**
     * @return string
     */
    final public function key(): string
    {
        /** @var Prop $prop */
        $prop = $this->data[key($this->data)];
        return $prop->key;
    }

    /**
     * @return void
     */
    final public function next(): void
    {
        next($this->data);
    }

    /**
     * @return bool
     */
    final public function valid(): bool
    {
        return is_null(key($this->data)) ? false : true;
    }
}