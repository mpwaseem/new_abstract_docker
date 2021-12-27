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

namespace Comely\Utils\Time;

/**
 * Class Time
 * @package Comely\Utils\Time
 */
class Time
{
    /**
     * Number of minutes elapsed (decimal with 1 digit) between 2 timestamps
     * @param int $stamp1
     * @param int|null $stamp2
     * @return int
     */
    public static function difference(int $stamp1, ?int $stamp2 = null): int
    {
        if (!is_int($stamp2)) {
            $stamp2 = time();
        }

        return $stamp2 > $stamp1 ? $stamp2 - $stamp1 : $stamp1 - $stamp2;
    }

    /**
     * Number of minutes elapsed (decimal with 1 digit) between 2 timestamps
     *
     * @param int $stamp1
     * @param int|null $stamp2
     * @return float
     */
    public static function minutesDifference(int $stamp1, ?int $stamp2 = null): float
    {
        return round(self::difference($stamp1, $stamp2) / 60, 1);
    }

    /**
     * Number of hours elapsed (decimal with 1 digit) between 2 timestamps
     *
     * @param int $stamp1
     * @param int|null $stamp2
     * @return float
     */
    public static function hoursDifference(int $stamp1, ?int $stamp2 = null): float
    {
        return round((self::difference($stamp1, $stamp2) / 60) / 60, 1);
    }
}