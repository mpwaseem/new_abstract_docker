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

use Comely\DataTypes\Integers;
use Comely\Utils\Validator\Exception\InvalidTypeException;
use Comely\Utils\Validator\Exception\NotInArrayException;
use Comely\Utils\Validator\Exception\RangeException;
use Comely\Utils\Validator\Exception\SignedIntegerException;

/**
 * Class IntValidator
 * @package Comely\Utils\Validator
 */
class IntValidator extends AbstractValidator
{
    /** @var null|int */
    private $rangeFrom;
    /** @var null|int */
    private $rangeTo;
    /** @var null|bool */
    private $unSigned;

    /**
     * @param int $min
     * @param int $max
     * @return IntValidator
     */
    public function range(int $min, int $max): self
    {
        $this->rangeFrom = $min;
        $this->rangeTo = $max;
        return $this;
    }

    /**
     * @return IntValidator
     */
    public function unSigned(): self
    {
        $this->unSigned = true;
        return $this;
    }

    /**
     * @param callable|null $customValidator
     * @return int|null
     * @throws InvalidTypeException
     * @throws NotInArrayException
     * @throws RangeException
     * @throws SignedIntegerException
     */
    public function validate(?callable $customValidator = null): ?int
    {
        $value = $this->value;
        if (!is_int($value) && !$value && $this->nullable) {
            return null;
        }

        if (is_string($value)) {
            if (!preg_match('/^[1-9]+[0-9]*$/', $value)) {
                throw new InvalidTypeException();
            }

            return intval($value);
        }

        if (!is_int($value)) {
            throw new InvalidTypeException();
        }

        if (is_bool($this->unSigned)) {
            if ($value < 0) {
                throw new SignedIntegerException();
            }
        }

        if ($this->rangeFrom && $this->rangeTo) {
            if (!Integers::Range($value, $this->rangeFrom, $this->rangeTo)) {
                throw new RangeException();
            }
        }

        // Check if is in defined Array
        if ($this->inArray) {
            if (!in_array($value, $this->inArray)) {
                throw new NotInArrayException();
            }
        }

        // Custom validator
        if ($customValidator) {
            $value = call_user_func($customValidator, $value);
            if (!is_int($value)) {
                throw new \UnexpectedValueException('Integer validator callback must return an int');
            }
        }

        return $value;
    }
}