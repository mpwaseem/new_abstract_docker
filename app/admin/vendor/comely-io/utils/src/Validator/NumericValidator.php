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

use Comely\DataTypes\BcMath\BcMath;
use Comely\Utils\Validator\Exception\NotInArrayException;
use Comely\Utils\Validator\Exception\RangeException;

/**
 * Class NumericValidator
 * @package Comely\Utils\Validator
 */
class NumericValidator extends AbstractValidator
{
    /** @var null|int */
    private $scale;
    /** @var null|string */
    private $rangeFrom;
    /** @var null|string */
    private $rangeTo;

    /**
     * @param int $scale
     * @return NumericValidator
     */
    public function scale(int $scale): self
    {
        $this->scale = $scale;
        return $this;
    }

    /**
     * @param $from
     * @param $to
     * @return NumericValidator
     */
    public function range($from, $to): self
    {
        $this->rangeFrom = BcMath::Value($from);
        $this->rangeTo = BcMath::Value($to);
        return $this;
    }

    /**
     * @param callable|null $customValidator
     * @return \Comely\DataTypes\BcNumber|mixed|null
     * @throws NotInArrayException
     * @throws RangeException
     */
    public function validate(?callable $customValidator = null)
    {
        $value = BcMath::isNumeric($this->value);
        if ($this->scale) {
            $value->scale($this->scale);
        }

        if (isset($this->rangeFrom, $this->rangeTo)) {
            if (!$value->inRange($this->rangeFrom, $this->rangeTo)) {
                throw new RangeException();
            }
        }

        if ($this->inArray) {
            if (!in_array($value->value(), $this->inArray)) {
                throw new NotInArrayException();
            }
        }

        // Custom validator
        if ($customValidator) {
            $value = call_user_func($customValidator, $value);
            if (!is_int($value)) {
                throw new \UnexpectedValueException('Numeric validator callback must return an instance of BcNumber');
            }
        }

        return $value;
    }
}