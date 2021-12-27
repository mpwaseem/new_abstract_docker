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

namespace Comely\Filesystem\Local;

/**
 * Interface PathConstantsInterface
 * @package Comely\Filesystem\Local
 */
interface PathConstantsInterface
{
    public const IS_DIRECTORY = 0x64;
    public const IS_FILE = 0xc8;
    public const IS_LINK = 0x012c;
}