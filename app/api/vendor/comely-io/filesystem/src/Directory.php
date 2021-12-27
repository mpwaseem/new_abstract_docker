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
use Comely\Filesystem\Exception\PathNotExistException;
use Comely\Filesystem\Exception\PathOpException;
use Comely\Filesystem\Exception\PathPermissionException;
use Comely\Filesystem\Local\AbstractPath;
use Comely\Filesystem\Local\DirFactory;

/**
 * Class Directory
 * @package Comely\Filesystem
 */
class Directory extends AbstractPath
{
    /** @var null|DirFactory */
    private $factory;

    /**
     * Directory constructor.
     * @param string $path
     * @throws PathException
     * @throws PathNotExistException
     */
    public function __construct(string $path)
    {
        parent::__construct($path);
        if ($this->type() !== self::IS_DIRECTORY) {
            throw new PathException('Cannot instantiate path as Directory object');
        }
    }

    /**
     * @param string $path
     * @return string
     */
    public function suffix(string $path): string
    {
        $sep = '(\/|\\\)';
        $path = preg_replace('/' . $sep . '{2,}/', DIRECTORY_SEPARATOR, $path);
        if (!preg_match('/^(' . $sep . '?[\w\-\.]+' . $sep . '?)+$/', $path)) {
            throw new \InvalidArgumentException('Invalid suffix path');
        } elseif (preg_match('/(\.{1,}' . $sep . ')/', $path)) {
            throw new \InvalidArgumentException('Path contains illegal references');
        }

        return $this->path() . DIRECTORY_SEPARATOR . ltrim($path, '\/\\');
    }

    /**
     * @param string $child
     * @return int|null
     */
    public function has(string $child): ?int
    {
        $child = $this->suffix($child);
        if (file_exists($child)) {
            if (is_dir($child)) {
                return self::IS_DIRECTORY;
            } elseif (is_file($child)) {
                return self::IS_FILE;
            } elseif (is_link($child)) {
                return self::IS_LINK;
            }
        }

        return null;
    }

    /**
     * @param string $child
     * @param bool $createIfNotExists
     * @return File
     * @throws PathException
     * @throws PathNotExistException
     * @throws PathOpException
     */
    public function file(string $child, bool $createIfNotExists = false): File
    {
        try {
            return new File($this->suffix($child));
        } catch (PathNotExistException $e) {
            if ($createIfNotExists) {
                return $this->create()->file($child, ""); // Create new blank file
            }

            throw new PathNotExistException('No such file exists in directory');
        }
    }

    /**
     * @param string $child
     * @param bool $createIfNotExists
     * @return Directory
     * @throws PathException
     * @throws PathNotExistException
     * @throws PathOpException
     */
    public function dir(string $child, bool $createIfNotExists = false): Directory
    {
        try {
            return new Directory($this->suffix($child));
        } catch (PathNotExistException $e) {
            if ($createIfNotExists) {
                return $this->create()->dirs($child);
            }

            throw new PathNotExistException('No such sub-directory exists');
        }
    }

    /**
     * @param string $fileName
     * @param string $data
     * @param bool $append
     * @param bool $lock
     * @return int
     * @throws PathOpException
     * @throws PathPermissionException
     */
    public function write(string $fileName, string $data, bool $append = false, bool $lock = false): int
    {
        if (!$this->permissions()->write()) {
            throw new PathPermissionException('Directory is not writable');
        }

        $flags = 0;
        if ($append && $lock) {
            $flags = FILE_APPEND | LOCK_EX;
        } elseif ($append) {
            $flags = FILE_APPEND;
        } elseif ($lock) {
            $flags = LOCK_EX;
        }

        $len = file_put_contents($this->suffix($fileName), $data, $flags, null);
        if (!is_int($len)) {
            throw new PathOpException(sprintf('An error occurred while writing file'));
        }

        return $len;
    }

    /**
     * @param string $child
     * @return Directory|File
     * @throws PathException
     */
    public function child(string $child)
    {
        $child = $this->suffix($child);
        $type = $this->has($child);
        switch ($type) {
            case self::IS_DIRECTORY:
                return new Directory($child);
            case self::IS_FILE:
                return new File($child);
        }

        throw new PathException('No such file or directory exists');
    }

    /**
     * @param bool $absolutePaths
     * @param int $sort
     * @return array
     * @throws PathException
     * @throws PathOpException
     * @throws PathPermissionException
     */
    public function scan(bool $absolutePaths = false, int $sort = SCANDIR_SORT_NONE): array
    {
        if (!$this->permissions()->read()) {
            throw new PathPermissionException('Cannot scan directory; Directory is not readable');
        }

        $directoryPath = $this->path();
        $final = [];
        $scan = scandir($directoryPath, $sort);
        if (!is_array($scan)) {
            throw new PathOpException('Failed to scan directory');
        }

        foreach ($scan as $file) {
            if (in_array($file, [".", ".."])) {
                continue; // Skip dots
            }

            $final[] = $absolutePaths ? $directoryPath . DIRECTORY_SEPARATOR . $file : $file;
        }

        return $final;
    }

    /**
     * @param string $pattern
     * @param bool $absolutePaths
     * @param int $flags
     * @return array
     * @throws PathException
     * @throws PathOpException
     * @throws PathPermissionException
     */
    public function glob(string $pattern, bool $absolutePaths = false, int $flags = 0): array
    {
        if (!$this->permissions()->read()) {
            throw new PathPermissionException('Cannot use glob; Directory is not readable');
        }

        if (!preg_match('/^[\w\*\-\.]+$/', $pattern)) {
            throw new \InvalidArgumentException('Unacceptable glob pattern');
        }

        $directoryPath = $this->path() . DIRECTORY_SEPARATOR;
        $final = [];
        $glob = glob($directoryPath . $pattern, $flags);
        if (!is_array($glob)) {
            throw new PathOpException('Directory glob failed');
        }

        foreach ($glob as $file) {
            if (in_array($file, [".", ".."])) {
                continue; // Skip dots
            }

            $final[] = $absolutePaths ? $directoryPath . $file : $file;
        }

        return $final;
    }

    /**
     * Create new file or sub-directories
     * @return DirFactory
     */
    public function create(): DirFactory
    {
        if (!$this->factory) {
            $this->factory = new DirFactory($this);
        }

        return $this->factory;
    }

    /**
     * Alias of create() method
     * @return DirFactory
     */
    public function new(): DirFactory
    {
        return $this->create();
    }

    /**
     * @param string $child
     * @param string $mode
     * @throws PathException
     * @throws PathOpException
     */
    public function chmod(string $child, string $mode = "0755"): void
    {
        if (!$child) {
            throw new \InvalidArgumentException('Expecting relative path to file/sub-directory');
        }

        if (!preg_match('/^0[0-9]{3}$/', $mode)) {
            throw new \InvalidArgumentException('Invalid chmod argument, expecting octal number as string');
        }

        $child = $this->suffix($child);
        if (!chmod($child, intval($mode, 8))) {
            throw new PathOpException('Cannot change file/directory permissions');
        }
    }

    /**
     * @param string|null $child
     * @throws PathException
     * @throws PathOpException
     * @throws PathPermissionException
     */
    public function delete(?string $child = null): void
    {
        if (!$this->permissions()->write()) {
            throw new PathPermissionException('Cannot use delete op; Directory is not writable');
        }

        // Remove a file or sub-directory
        if ($child) {
            $childExists = $this->has($child);
            if (!$childExists) {
                throw new PathOpException('Cannot delete; Target file/sub-directory does not exist');
            }

            if ($childExists === self::IS_DIRECTORY) {
                (new Directory($child))->delete(); // Remove sub-directory
                return;
            }

            if (!unlink($child)) {
                throw new PathOpException(sprintf('Failed to delete file "%s"', basename($child)));
            }
        }

        // Remove directory
        $this->flush(); // Delete all files and sub-directories
        if (!rmdir($this->path())) {
            throw new PathOpException('Failed to delete directory');
        }

        $this->deleted = true;
    }

    /**
     * Deletes all files and sub-directories inside this directory
     * @param bool $ignoreFails If TRUE then keeps deleting files even if one of the files has failed to delete
     * @return int Number of files and sub-directories deleted
     * @throws PathException
     * @throws PathOpException
     * @throws PathPermissionException
     */
    public function flush(bool $ignoreFails = false): int
    {
        if (!$this->permissions()->write()) {
            throw new PathPermissionException('Cannot flush directory; Directory is not writable');
        }

        $deleted = 0;
        foreach ($this->scan(true) as $file) {
            if (is_dir($file)) {
                $deleted += (new Directory($file))->flush($ignoreFails);
                if (!rmdir($file)) {
                    if (!$ignoreFails) {
                        throw new PathOpException(sprintf('Could not delete sub-directory "%s"', basename($file)));
                    }
                }

                continue;
            }

            if (!unlink($file)) {
                if ($ignoreFails) {
                    continue;
                }

                throw new PathOpException(sprintf('Could not delete file "%s"', basename($file)));
            }

            $deleted++;
        }

        return $deleted;
    }

    /**
     * @return int
     * @throws PathException
     * @throws PathOpException
     * @throws PathPermissionException
     */
    protected function findSizeInBytes(): int
    {
        $sizeInBytes = 0;
        $list = $this->scan(true);
        foreach ($list as $file) {
            if (is_dir($file)) {
                $sizeInBytes += (new Directory($file))->size();
                continue;
            }

            $fileSize = filesize($file);
            if (!is_int($fileSize)) {
                throw new PathOpException(sprintf('Could not find size of file "%s"', basename($file)));
            }

            $sizeInBytes += $fileSize;
        }

        return $sizeInBytes;
    }
}