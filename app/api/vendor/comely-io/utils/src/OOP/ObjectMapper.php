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

namespace Comely\Utils\OOP;

use Comely\Utils\OOP\ObjectMapper\Exception\ObjectMapperException;
use Comely\Utils\OOP\ObjectMapper\ObjectMapperInterface;
use Comely\Utils\OOP\ObjectMapper\ObjectMapProp;

/**
 * Class ObjectMapper
 * @package Comely\Utils\OOP
 */
class ObjectMapper
{
    /** @var int Match only properties that are defined in "objectMapperProps" method, ignore rest */
    public const MATCH_DEFAULT = 0x0a;
    /** @var int Must map ALL of the declared properties */
    public const MATCH_ALL_PROPS = 0x14;
    /** @var int Map ALL keys/values from array, All keys MUST MATCH to their corresponding declared property */
    public const MATCH_ALL_FROM_ARRAY = 0x1e;
    /** @var int Map ALL keys/values from array, Ignoring the ones without corresponding property declared */
    public const MATCH_FROM_ARRAY = 0x28;

    private const APPROACH_FLAGS = [
        self::MATCH_DEFAULT,
        self::MATCH_ALL_PROPS,
        self::MATCH_ALL_FROM_ARRAY,
        self::MATCH_FROM_ARRAY
    ];

    /** @var ObjectMapperInterface */
    private $obj;
    /** @var array */
    private $props;
    /** @var bool */
    private $mapCaseConversion;
    /** @var int */
    private $approach;
    /** @var null|array */
    private $data;
    /** @var null|\ReflectionClass */
    private $reflection;

    /**
     * ObjectMapper constructor.
     * @param ObjectMapperInterface $obj
     */
    public function __construct(ObjectMapperInterface $obj)
    {
        $this->obj = $obj;
        $this->props = [];
        $this->mapCaseConversion = true;
        $this->approach = self::MATCH_DEFAULT;

        // Define properties data domain
        $obj->objectMapperProps($this);
    }

    /**
     * @param bool $trigger
     * @return ObjectMapper
     */
    public function mapCaseConversion(bool $trigger): self
    {
        $this->mapCaseConversion = $trigger;
        return $this;
    }

    /**
     * @param int $flag
     * @return ObjectMapper
     */
    public function approach(int $flag): self
    {
        if (!in_array($flag, self::APPROACH_FLAGS)) {
            throw new \OutOfBoundsException('Invalid object mapping approach flag');
        }

        $this->approach = $flag;
        return $this;
    }

    /**
     * @param string $name
     * @return ObjectMapProp
     */
    public function prop(string $name): ObjectMapProp
    {
        $prop = new ObjectMapProp($name);
        $this->props[$name] = $prop;
        return $prop;
    }

    /**
     * @param array $data
     * @return ObjectMapperInterface
     * @throws ObjectMapperException
     * @throws \Exception
     */
    public function map(array $data): ObjectMapperInterface
    {
        try {
            $this->reflection = new \ReflectionClass($this->obj);
        } catch (\Exception $e) {
            throw new ObjectMapperException('Failed to get reflection');
        }

        $this->data = $data;

        try {
            if ($this->approach === self::MATCH_ALL_PROPS) {
                $reflectionProps = $this->reflection->getProperties();
                foreach ($reflectionProps as $reflectionProp) {
                    $this->setPropValue($reflectionProp, $this->findPropValue($reflectionProp->getName(), $this->mapCaseConversion));
                }
            } elseif (in_array($this->approach, [self::MATCH_ALL_FROM_ARRAY, self::MATCH_FROM_ARRAY])) {
                foreach ($data as $key => $value) {
                    $reflectProp = $this->findProp($key, $this->mapCaseConversion);
                    if (!$reflectProp && $this->approach === self::MATCH_ALL_FROM_ARRAY) {
                        throw new ObjectMapperException(
                            sprintf('Class has not declared corresponding property for key "%s"', $key)
                        );
                    }

                    if ($reflectProp) {
                        $this->setPropValue($reflectProp, $value);
                    }
                }
            } else {
                foreach ($this->props as $prop) {
                    $reflectProp = $this->findProp($prop->name, false);
                    if (!$reflectProp) {
                        throw new ObjectMapperException(sprintf('Class has not declared "%s" prop', $prop->name));
                    }

                    $this->setPropValue($reflectProp, $this->findPropValue($prop->name, $this->mapCaseConversion));
                }
            }
        } catch (ObjectMapperException $e) {
            throw $e->setObjectName($this->obj);
        }

        return $this->obj;
    }

    /**
     * @param \ReflectionProperty $prop
     * @param $value
     * @throws \Exception
     */
    private function setPropValue(\ReflectionProperty $prop, $value): void
    {
        if (!$prop->isDefault()) {
            return; //Ignore dynamically declared props
        }

        /** @var ObjectMapProp $propDomain */
        $propDomain = $this->props[$prop->getName()] ?? null;
        if ($propDomain) {
            try {
                $value = call_user_func([$propDomain, "getValidatedValue"], $value);
                $prop->setAccessible(true);
                $prop->setValue($this->obj, $value);
            } catch (\Exception $e) {
                if ($propDomain->skipOnError) {
                    return;
                }

                throw $e;
            }
        }
    }

    /**
     * @param string $prop
     * @param bool $caseConversion
     * @return \ReflectionProperty|null
     */
    private function findProp(string $prop, bool $caseConversion = false): ?\ReflectionProperty
    {
        try {
            if ($this->reflection->hasProperty($prop)) {
                return $this->reflection->getProperty($prop);
            }

            if ($caseConversion) {
                $possibleKeys = [
                    OOP::snake_case($prop),
                    OOP::camelCase($prop),
                    OOP::PascalCase($prop)
                ];

                foreach ($possibleKeys as $possibleKey) {
                    if ($this->reflection->hasProperty($possibleKey)) {
                        return $this->reflection->getProperty($possibleKey);
                    }
                }
            }
        } catch (\ReflectionException $e) {
        }

        return null;
    }

    /**
     * @param string $prop
     * @param bool $caseConversion
     * @return mixed|null
     */
    private function findPropValue(string $prop, bool $caseConversion = false)
    {
        if (array_key_exists($prop, $this->data)) {
            return $this->data[$prop];
        }

        if ($caseConversion) {
            $possibleKeys = [
                OOP::snake_case($prop),
                OOP::camelCase($prop),
                OOP::PascalCase($prop)
            ];

            foreach ($possibleKeys as $key) {
                if (isset($this->data[$key])) {
                    return $this->data[$key];
                }
            }
        }

        return null;
    }
}