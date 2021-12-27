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

use Comely\Filesystem\Exception\PathException;
use Comely\Filesystem\Exception\PathOpException;
use Comely\Filesystem\Exception\PathPermissionException;
use Comely\Filesystem\Local\AbstractPath;

/**
 * Class File
 * @package Comely\Filesystem
 */
class File extends AbstractPath
{
    /**
     * File constructor.
     * @param string $path
     * @throws Exception\PathNotExistException
     * @throws PathException
     */
    public function __construct(string $path)
    {
        parent::__construct($path);
        if ($this->type() !== self::IS_FILE) {
            throw new PathException('Cannot instantiate path as File object');
        }
    }

    /**
     * @return string
     * @throws PathException
     * @throws PathOpException
     * @throws PathPermissionException
     */
    public function read(): string
    {
        if (!$this->permissions()->read()) {
            throw new PathPermissionException('File is not readable');
        }

        $bytes = file_get_contents($this->path(), false, null, 0);
        if ($bytes === false) {
            throw new PathOpException('An error occurred while reading file');
        }

        return $bytes;
    }

    /**
     * @param string $bytes
     * @param bool $lock
     * @return int
     * @throws PathException
     * @throws PathOpException
     * @throws PathPermissionException
     */
    public function edit(string $bytes, bool $lock = false): int
    {
        return $this->write($bytes, false, $lock);
    }

    /**
     * @param string $bytes
     * @param bool $lock
     * @return int
     * @throws PathException
     * @throws PathOpException
     * @throws PathPermissionException
     */
    public function append(string $bytes, bool $lock = false): int
    {
        return $this->write($bytes, true, $lock);
    }

    /**
     * @param string $bytes
     * @param bool $append
     * @param bool $lock
     * @return int
     * @throws PathException
     * @throws PathOpException
     * @throws PathPermissionException
     */
    private function write(string $bytes, bool $append, bool $lock): int
    {
        if (!$this->permissions()->write()) {
            throw new PathPermissionException('File is not writable');
        }

        $flags = 0;
        if ($append && $lock) {
            $flags = FILE_APPEND | LOCK_EX;
        } elseif ($append) {
            $flags = FILE_APPEND;
        } elseif ($lock) {
            $flags = LOCK_EX;
        }

        $len = file_put_contents($this->path(), $bytes, $flags, null);
        if (!is_int($len)) {
            throw new PathOpException('An error occurred while editing file');
        }

        return $len;
    }

    /**
     * @throws PathException
     * @throws PathOpException
     * @throws PathPermissionException
     */
    public function delete(): void
    {
        if (!$this->permissions()->write()) {
            throw new PathPermissionException('Cannot delete file; Permission error');
        }

        if (!unlink($this->path())) {
            throw new PathOpException('Failed to delete file');
        }

        $this->deleted = true;
    }

    /**
     * @return int
     * @throws PathException
     * @throws PathOpException
     */
    protected function findSizeInBytes(): int
    {
        $size = filesize($this->path());
        if (!is_int($size)) {
            throw new PathOpException('Failed to check file size');
        }

        return $size;
    }
}