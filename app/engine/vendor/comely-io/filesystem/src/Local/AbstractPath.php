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
use Comely\Filesystem\Exception\PathException;
use Comely\Filesystem\Exception\PathNotExistException;

/**
 * Class AbstractPath
 * @package Comely\Filesystem\Local
 */
abstract class AbstractPath implements PathConstantsInterface
{
    /** @var string */
    private $path;
    /** @var null|int */
    private $type;
    /** @var null|Permissions */
    private $permissions;
    /** @var null|FileTimestamps */
    private $timestamps;
    /** @var null|int */
    private $size;
    /** @var null|bool */
    protected $deleted;

    /**
     * AbstractPath constructor.
     * @param string $path
     * @throws PathNotExistException
     */
    public function __construct(string $path)
    {
        $this->path = realpath($path); // Get an absolute real path
        if (!$this->path) {
            throw new PathNotExistException('File or directory does not exist');
        }

        // Type
        if (is_dir($path)) {
            $this->type = self::IS_DIRECTORY;
        } elseif (is_file($path)) {
            $this->type = self::IS_FILE;
        } elseif (is_link($path)) {
            $this->type = self::IS_LINK;
        }
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            "path" => $this->path,
            "type" => $this->type,
            "size" => $this->size,
            "permissions" => $this->permissions
        ];
    }

    /**
     * @return int|null
     */
    public function type(): ?int
    {
        return $this->type;
    }

    /**
     * @return AbstractPath
     */
    public function clearStatCache(): self
    {
        clearstatcache(true, $this->path());
        $this->permissions()->reset(); // Reset stored permissions
        $this->timestamps()->reset(); // Reset stored timestamps
        $this->size = null; // Clear stored size
        return $this;
    }

    /**
     * @return string
     */
    public function path(): string
    {
        if ($this->deleted) {
            throw new \RuntimeException('File/directory path has been deleted and is no longer valid');
        }

        return $this->path;
    }

    /**
     * @return string
     */
    public function basename(): string
    {
        return basename($this->path);
    }

    /**
     * @return Permissions
     */
    public function permissions(): Permissions
    {
        if (!$this->permissions) {
            $this->permissions = new Permissions($this);
        }

        return $this->permissions;
    }

    /**
     * @return Directory
     * @throws PathException
     */
    public function parent(): Directory
    {
        return new Directory(dirname($this->path));
    }

    /**
     * @return FileTimestamps
     */
    public function timestamps(): FileTimestamps
    {
        if (!$this->timestamps) {
            $this->timestamps = new FileTimestamps($this);
        }

        return $this->timestamps;
    }

    /**
     * @return int
     */
    final public function size(): int
    {
        if (!is_int($this->size)) {
            $this->size = $this->findSizeInBytes();
        }

        return $this->size;
    }

    /**
     * @return int
     */
    abstract protected function findSizeInBytes(): int;
}