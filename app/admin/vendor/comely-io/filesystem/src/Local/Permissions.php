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
 * Class Permissions
 * @package Comely\Filesystem\Local
 */
class Permissions
{
    /** @var AbstractPath */
    private $path;
    /** @var null|bool */
    private $read;
    /** @var null|bool */
    private $write;
    /** @var null|bool */
    private $execute;

    /**
     * Permissions constructor.
     * @param AbstractPath $path
     */
    public function __construct(AbstractPath $path)
    {
        $this->path = $path;
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        $permissions = [];
        foreach (["read", "write", "execute"] as $perm) {
            if (is_bool($this->$perm)) {
                $permissions[$perm] = $this->$perm;
            }
        }

        return $permissions;
    }

    /**
     * @return Permissions
     */
    public function reset(): self
    {
        $this->read = null;
        $this->write = null;
        $this->execute = null;
        return $this;
    }

    /**
     * @param string $mode
     * @return Permissions
     * @throws PathOpException
     * @throws \Comely\Filesystem\Exception\PathException
     */
    public function chmod(string $mode): self
    {
        if (!preg_match('/^0[0-9]{3}$/', $mode)) {
            throw new \InvalidArgumentException('Invalid chmod argument, expecting octal number as string');
        }

        if (!chmod($this->path->path(), intval($mode, 8))) {
            throw new PathOpException('Cannot change file/directory permissions');
        }

        $this->path->clearStatCache();
        return $this;
    }

    /**
     * @return bool
     */
    public function read(): bool
    {
        if (!is_bool($this->read)) {
            $this->read = is_readable($this->path->path());
        }

        return $this->read;
    }

    /**
     * @return bool
     */
    public function write(): bool
    {
        if (!is_bool($this->write)) {
            $this->write = is_writable($this->path->path());
        }

        return $this->write;
    }

    /**
     * @return bool
     */
    public function execute(): bool
    {
        if (!is_bool($this->execute)) {
            $this->execute = is_executable($this->path->path());
        }

        return $this->execute;
    }
}