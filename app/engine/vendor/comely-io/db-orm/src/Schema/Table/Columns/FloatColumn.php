<?php
/**
 * This file is a part of "comely-io/db-orm" package.
 * https://github.com/comely-io/db-orm
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comely-io/db-orm/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\Database\Schema\Table\Columns;

use Comely\Database\Schema\Table\Traits\NumericValueTrait;
use Comely\Database\Schema\Table\Traits\PrecisionValueTrait;

/**
 * Class FloatColumn
 * @package Comely\Database\Schema\Table\Columns
 * @property-read int $digits
 * @property-read int $scale
 */
class FloatColumn extends AbstractTableColumn
{
    protected const MAX_DIGITS = 65;
    protected const MAX_SCALE = 30;

    /** @var string */
    protected $type;
    /** @var int */
    private $digits;
    /** @var int */
    private $scale;

    use NumericValueTrait;
    use PrecisionValueTrait;

    /**
     * FloatColumn constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->type = "float";
        $this->dataType = "double";
        $this->digits = 10;
        $this->scale = 0;
        $this->setDefaultValue("0");
    }

    /**
     * @param float $value
     * @return $this
     */
    public function default(float $value = 0)
    {
        if (!preg_match('/^\-?[0-9]+(\.[0-9]+)?$/', $value)) {
            throw new \InvalidArgumentException(sprintf('Bad default float value for col "%s"', $this->name));
        }

        $this->setDefaultValue($value);
        return $this;
    }

    /**
     * @param $prop
     * @return mixed
     */
    public function __get($prop)
    {
        switch ($prop) {
            case "digits":
            case "scale":
                return $this->$prop;
        }

        return parent::__get($prop);
    }

    /**
     * @param string $driver
     * @return string|null
     */
    protected function columnSQL(string $driver): ?string
    {
        switch ($driver) {
            case "mysql":
                return sprintf('%s(%d,%d)', $this->type, $this->digits, $this->scale);
            case "sqlite":
                return "REAL";
        }

        return null;
    }
}
