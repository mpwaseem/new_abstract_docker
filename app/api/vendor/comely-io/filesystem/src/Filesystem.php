<?php
/**
 * This file is a part of "comely-io/filesystem" package.
 * https://github.com/comely-io/filesystem
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comely-io/filesystem/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\Filesystem;

use Comely\Filesystem\Local\PathConstantsInterface;

/**
 * Class Filesystem
 * @package Comely\Filesystem
 */
class Filesystem implements PathConstantsInterface
{
    /** string Version (Major.Minor.Release-Suffix) */
    public const VERSION = "1.0.13";
    /** int Version (Major * 10000 + Minor * 100 + Release) */
    public const VERSION_ID = 10013;

    /**
     * @param string|null $realPath
     */
    public static function clearStatCache(?string $realPath = null): void
    {
        clearstatcache(true, $realPath);
    }

    /**
     * @param string $content
     * @return string
     */
    public static function prependUtf8Bom(string $content): string
    {
        return pack("CCC", 0xef, 0xbb, 0xbf) . $content;
    }

    /**
     * @param string $content
     * @return string
     */
    public static function removeUtf8Bom(string $content): string
    {
        return preg_replace("/^" . pack("H*", "EFBBBF") . "/", "", $content);
    }
}