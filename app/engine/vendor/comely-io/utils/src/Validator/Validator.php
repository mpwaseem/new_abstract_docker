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
 * Class Validator
 * @package Comely\Utils\Validator
 */
class Validator
{
    /**
     * @param $value
     * @return StringValidator
     */
    public static function String($value): StringValidator
    {
        return new StringValidator($value);
    }

    /**
     * @param $value
     * @return IntValidator
     */
    public static function Integer($value): IntValidator
    {
        return new IntValidator($value);
    }

    /**
     * @param $value
     * @return NumericValidator
     */
    public static function Numeric($value): NumericValidator
    {
        return new NumericValidator($value);
    }
}