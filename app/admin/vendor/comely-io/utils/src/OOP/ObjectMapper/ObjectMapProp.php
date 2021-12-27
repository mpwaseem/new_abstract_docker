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

namespace Comely\Utils\OOP\ObjectMapper;

use Comely\Utils\OOP\ObjectMapper\Exception\ObjectMapperException;

/**
 * Class ObjectMapProp
 * @package Comely\Utils\OOP\ObjectMapper
 * @property-read string $name
 * @property-read bool $skipOnError
 * @property-read bool $nullable
 * @method getValidatedValue($arg)
 */
class ObjectMapProp
{
    private const DATA_TYPES = ["boolean", "integer", "double", "string", "array", "object"];

    /** @var string */
    private $name;
    /** @var array */
    private $dataTypes;
    /** @var bool */
    private $nullable;
    /** @var null|callable */
    private $validate;
    /** @var null|bool */
    private $validateException;
    /** @var bool */
    private $skipOnError;

    /**
     * AbstractProp constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->dataTypes = [];
        $this->nullable = false;
        $this->skipOnError = false;
    }

    /**
     * @param $prop
     * @return mixed
     */
    public function __get($prop)
    {
        switch ($prop) {
            case "name":
            case "skipOnError":
                return $this->$prop;
        }

        throw new \DomainException('Cannot get value for inaccessible property');
    }

    /**
     * @param string ...$types
     * @return ObjectMapProp
     */
    public function dataTypes(string ...$types): self
    {
        foreach ($types as $type) {
            if (!in_array($type, self::DATA_TYPES)) {
                throw new \DomainException('Invalid suggested data type');
            }
        }

        $this->dataTypes = $types;
        return $this;
    }

    /**
     * Callback validation method will return value as argument; NULL will NOT be passed on to any validation method
     * Callback validation method should return validated value according to data type OR NULL;
     * If NULL is returned, an ObjectMapperException will be thrown indicating that value from input array that does not adhere to defined data domain,
     * Any exception thrown within callback function will be caught, and replaced with ObjectMapperException exception, Unless second argument is set to TRUE
     * @param callable $callback
     * @param bool $validateException
     * @return ObjectMapProp
     */
    public function validate(callable $callback, bool $validateException = false): self
    {
        $this->validate = $callback;
        $this->validateException = $validateException;
        return $this;
    }

    /**
     * @param $method
     * @param $args
     * @return bool|null
     * @throws \Exception
     */
    public function __call($method, $args)
    {
        switch ($method) {
            case "getValidatedValue":
                return $this->validatedValue($args[0]);
        }

        throw new \DomainException('Cannot call inaccessible method');
    }

    /**
     * @return ObjectMapProp
     */
    public function nullable(): self
    {
        $this->nullable = true;
        return $this;
    }

    /**
     * @return ObjectMapProp
     */
    public function skipOnError(): self
    {
        $this->skipOnError = true;
        return $this;
    }

    /**
     * @param $value
     * @return mixed|null
     * @throws ObjectMapperException
     * @throws \Exception
     */
    private function validatedValue($value)
    {
        if (is_null($value)) {
            if (!$this->nullable) {
                throw new ObjectMapperException(sprintf('Prop "%s" is not nullable', $this->name));
            }

            return null;
        }

        if ($this->validate) {
            try {
                $value = call_user_func($this->validate, $value);
            } catch (\Exception $e) {
                if ($e instanceof ObjectMapperException) {
                    throw $e;
                }

                if ($this->validateException) {
                    throw $e;
                }

                throw new ObjectMapperException(sprintf('Invalid value for prop "%s"', $this->name));
            }

            if (is_null($value)) {
                throw new ObjectMapperException(sprintf('NULL value for prop "%s"', $this->name));
            }
        }

        if ($this->dataTypes) {
            if (!in_array(gettype($value), $this->dataTypes)) {
                $expectedTypes = array_map(function ($type) {
                    return sprintf('"%s"', $type);
                }, $this->dataTypes);

                throw new ObjectMapperException(
                    sprintf('Value for prop "%s" must be of type [%s], got "%s"', $this->name, implode(",", $expectedTypes), gettype($value))
                );
            }
        }

        return $value;
    }
}