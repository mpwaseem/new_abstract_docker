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

namespace Comely\Utils\OOP\ObjectMapper\Exception;

use Comely\Utils\OOP\ObjectMapper\ObjectMapperInterface;

/**
 * Class ObjectMapperException
 * @package Comely\Utils\OOP\ObjectMapper\Exception
 */
class ObjectMapperException extends \Exception
{
    /**
     * @param ObjectMapperInterface $obj
     * @return ObjectMapperException
     */
    public function setObjectName(ObjectMapperInterface $obj): self
    {
        $this->message = sprintf('[%s] %s', get_class($obj), $this->message);
        return $this;
    }
}