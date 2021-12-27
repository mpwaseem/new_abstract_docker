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

use Comely\Filesystem\Directory;
use Comely\Filesystem\Exception\PathOpException;
use Comely\Filesystem\Exception\PathPermissionException;
use Comely\Filesystem\File;
use Comely\Filesystem\Filesystem;

/**
 * Class DirFactory
 * @package Comely\Filesystem\Local
 */
class DirFactory
{
    /** @var Directory */
    private $dir;

    /**
     * DirFactory constructor.
     * @param Directory $directory
     */
    public function __construct(Directory $directory)
    {
        $this->dir = $directory;
    }

    /**
     * @param string $path
     * @param string $data
     * @return File
     * @throws PathOpException
     * @throws \Comely\Filesystem\Exception\PathException
     */
    public function file(string $path, string $data): File
    {
        if (!$this->dir->permissions()->write()) {
            throw new PathPermissionException('Cannot create a new file; Directory is not writable');
        }

        $child = $this->dir->suffix($path);
        $len = file_put_contents($child, $data);
        if (!is_int($len)) {
            throw new PathOpException('Failed to write new file');
        }

        Filesystem::clearStatCache($child);
        return new File($child);
    }

    /**
     * @param string $path
     * @param string $mode
     * @return Directory
     * @throws PathOpException
     * @throws \Comely\Filesystem\Exception\PathException
     */
    public function dirs(string $path, string $mode = "0777"): Directory
    {
        if (!$this->dir->permissions()->write()) {
            throw new PathPermissionException('Cannot create new sub-dir(s); Directory is not writable');
        }

        $child = $this->dir->suffix($path);
        if (!preg_match('/^0[0-9]{3}$/', $mode)) {
            throw new \InvalidArgumentException('Invalid mode argument, expecting octal number as string');
        }

        if (!mkdir($child, intval($mode, 8), true)) {
            throw new PathOpException('Failed to create child directories');
        }

        Filesystem::clearStatCache($child);
        return new Directory($child);
    }
}