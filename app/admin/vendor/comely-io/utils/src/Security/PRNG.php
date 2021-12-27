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

namespace Comely\Utils\Security;

use Comely\DataTypes\Buffer\Binary;
use Comely\Utils\Security\Exception\PRNG_Exception;

/**
 * Class PRNG (Pseudo-random number generator)
 * @package Comely\Utils\Security
 */
class PRNG
{
    /**
     * @param int $len
     * @return Binary
     * @throws PRNG_Exception
     */
    public static function randomBytes(int $len): Binary
    {
        try {
            return new Binary(random_bytes($len));
        } catch (\Exception $e) {
            throw new PRNG_Exception('Failed to generate PRNG entropy');
        }
    }
}