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

use Comely\DataTypes\Integers;

/**
 * Class Passwords
 * @package Comely\Utils\Security
 */
class Passwords
{
    /**
     * @param int $length
     * @param int $minimumScore
     * @return string
     */
    public static function Generate(int $length = 12, int $minimumScore = 4): string
    {
        if (!Integers::Range($length, 6, 32)) {
            throw new \LengthException('Password length must be between 6 and 32');
        }

        if (!Integers::Range($minimumScore, 1, 6)) {
            throw new \InvalidArgumentException('Minimum score argument must be between 1-6');
        }

        $password = "";
        while (strlen($password) < $length) {
            $password .= chr(mt_rand(33, 126));
        }

        return self::Strength($password) >= $minimumScore ? $password : self::Generate($length);
    }

    /**
     * @param string $password
     * @return string
     */
    public static function Hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * @param string $password
     * @return int
     */
    public static function Strength(string $password): int
    {
        $score = 0;
        $passwordLength = strlen($password);

        // Lowercase alphabets... +1
        if (preg_match('/[a-z]/', $password)) $score++;
        // Uppercase alphabets... +1
        if (preg_match('/[A-Z]/', $password)) $score++;
        // Numerals... +1
        if (preg_match('/[0-9]/', $password)) $score++;
        // Special characters... +1
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score++;

        // Length over or equals 12 ... +1
        if ($passwordLength >= 12) $score++;
        // Length over or equals 12 ... +1
        if ($passwordLength >= 16) $score++;

        return $score;
    }
}