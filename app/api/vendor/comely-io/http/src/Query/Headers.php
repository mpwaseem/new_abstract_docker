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
 * Class Headers
 * @package Comely\Http\Query
 */
class Headers extends AbstractDataIterator
{
    /**
     * @param string $name
     * @param string $value
     * @return Headers
     */
    public function set(string $name, string $value): self
    {
        if (!preg_match('/^[\w\-\.]+$/i', $name)) {
            throw new \InvalidArgumentException('Invalid HTTP header key');
        }

        $this->setProp(new Prop($name, $value));
        return $this;
    }

    /**
     * @param string $name
     * @return string|null
     */
    public function get(string $name): ?string
    {
        $prop = $this->getProp($name);
        return $prop ? $prop->value : null;
    }
}