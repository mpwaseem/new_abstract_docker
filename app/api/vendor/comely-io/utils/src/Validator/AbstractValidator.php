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

namespace Comely\Utils\Validator;

/**
 * Class AbstractValidator
 * @package Comely\Utils\Validator
 */
abstract class AbstractValidator
{
    /** @var mixed */
    protected $value;
    /** @var bool */
    protected $nullable;
    /** @var null|array */
    protected $inArray;

    /**
     * AbstractValidator constructor.
     * @param $value
     */
    public function __construct($value)
    {
        $this->value = $value;
        $this->nullable = false;
    }

    /**
     * @return $this
     */
    public function nullable()
    {
        $this->nullable = true;
        return $this;
    }

    /**
     * @param array $opts
     * @return $this
     */
    public function inArray(array $opts)
    {
        $this->inArray = $opts;
        return $this;
    }

    /**
     * @param callable|null $customValidator
     * @return mixed
     */
    abstract public function validate(?callable $customValidator = null);
}