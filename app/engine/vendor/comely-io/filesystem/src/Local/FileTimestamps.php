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

use Comely\Filesystem\Exception\PathOpException;

/**
 * Class FileTimestamps
 * @package Comely\Filesystem\Local
 */
class FileTimestamps
{
    /** @var AbstractPath */
    private $path;
    /** @var null|int */
    private $modified;
    /** @var null|int */
    private $access;
    /** @var null|int */
    private $ctime;

    /**
     * FileTimestamps constructor.
     * @param AbstractPath $path
     */
    public function __construct(AbstractPath $path)
    {
        $this->path = $path;
    }

    /**
     * @return FileTimestamps
     */
    public function reset(): self
    {
        $this->modified = null;
        $this->access = null;
        $this->ctime = null;
        return $this;
    }

    /**
     * @return int
     * @throws PathOpException
     * @throws \Comely\Filesystem\Exception\PathException
     */
    public function modified(): int
    {
        if (!is_int($this->modified)) {
            $this->modified = filemtime($this->fileOrDirPath());
            if (!$this->modified) {
                throw new PathOpException('Failed to get last modified timestamp');
            }
        }

        return $this->modified;
    }

    /**
     * @return int
     * @throws PathOpException
     * @throws \Comely\Filesystem\Exception\PathException
     */
    public function access(): int
    {
        if (!is_int($this->access)) {
            $this->access = fileatime($this->fileOrDirPath());
            if (!$this->access) {
                throw new PathOpException('Failed to get last access timestamp');
            }
        }

        return $this->access;
    }

    /**
     * @return int
     * @throws PathOpException
     * @throws \Comely\Filesystem\Exception\PathException
     */
    public function ctime(): int
    {
        if (!is_int($this->ctime)) {
            $this->ctime = filectime($this->fileOrDirPath());
            if (!$this->ctime) {
                throw new PathOpException('Failed to get last access timestamp');
            }
        }

        return $this->ctime;
    }

    /**
     * @return string
     */
    private function fileOrDirPath(): string
    {
        $path = $this->path->path();
        if ($this->path->type() === AbstractPath::IS_DIRECTORY) {
            $path .= DIRECTORY_SEPARATOR . ".";
        }

        return $path;
    }
}