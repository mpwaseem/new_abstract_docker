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
use Comely\Utils\Validator\Exception\InvalidValueException;
use Comely\Utils\Validator\Exception\LengthException;
use Comely\Utils\Validator\Exception\NotInArrayException;

/**
 * Class StringValidator
 * @package Comely\Utils\Validator
 */
class StringValidator extends AbstractValidator
{
    /** @var null|string */
    private $encoding;
    /** @var null|int */
    private $len;
    /** @var null|int */
    private $minLen;
    /** @var null|int */
    private $maxLen;
    /** @var null|string */
    private $pattern;

    /**
     * @param int $lenOrMinLen
     * @param int|null $maxLen
     * @return StringValidator
     */
    public function len(int $lenOrMinLen, ?int $maxLen = null): self
    {
        if ($maxLen > 0) {
            $this->len = null;
            $this->minLen = $lenOrMinLen;
            $this->maxLen = $maxLen;
            return $this;
        }

        $this->len = $lenOrMinLen;
        $this->minLen = null;
        $this->maxLen = null;
        return $this;
    }

    /**
     * @param string $pattern
     * @return StringValidator
     */
    public function match(string $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * @param string $encoding
     * @return StringValidator
     */
    public function mb(string $encoding): self
    {
        if (!in_array($encoding, mb_list_encodings())) {
            throw new \OutOfBoundsException('Invalid multi-byte encoding');
        }

        $this->encoding = $encoding;
        return $this;
    }

    /**
     * @param string $encoding
     * @return StringValidator
     */
    public function multiByte(string $encoding): self
    {
        return $this->mb($encoding);
    }

    /**
     * @return StringValidator
     */
    public function lowerCase(): self
    {
        $this->value = $this->encoding ? mb_strtolower($this->value, $this->encoding) : strtolower($this->value);
        return $this;
    }

    /**
     * @return StringValidator
     */
    public function upperCase(): self
    {
        $this->value = $this->encoding ? mb_strtoupper($this->value, $this->encoding) : strtoupper($this->value);
        return $this;
    }

    /**
     * @param callable|null $customValidator
     * @return string|null
     * @throws InvalidTypeException
     * @throws InvalidValueException
     * @throws LengthException
     * @throws NotInArrayException
     */
    public function validate(?callable $customValidator = null): ?string
    {
        $value = $this->value;
        if (!$value && $this->nullable) {
            return null;
        }

        // Type
        if (!is_string($value)) {
            throw new InvalidTypeException();
        }

        // Check length
        if ($this->len || $this->minLen) {
            $len = $this->encoding ? mb_strlen($value, $this->encoding) : strlen($value);
            if ($this->len && $this->len !== $len) {
                throw new LengthException();
            }

            if ($this->minLen && $this->maxLen) {
                if (!Integers::Range($len, $this->minLen, $this->maxLen)) {
                    throw new LengthException();
                }
            }
        }

        // PREG pattern match
        if ($this->pattern) {
            if (!preg_match($this->pattern, $value)) {
                throw new InvalidValueException();
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
            if (!is_string($value)) {
                throw new \UnexpectedValueException('String validator callback must return a string value');
            }
        }

        return $value;
    }
}