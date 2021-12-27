<?php
/**
 * This file is a part of "comely-io/http" package.
 * https://github.com/comely-io/http
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comely-io/http/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\Http\Query;

use Comely\Http\Exception\HttpRequestException;

/**
 * Class URL
 * @package Comely\Http\Query
 */
class URL
{
    /** @var string */
    private $url;
    /** @var array */
    private $parsed;
    /** @var string|null */
    private $path;
    /** @var array */
    private $pathParts;

    /**
     * URL constructor.
     * @param string $url
     * @throws HttpRequestException
     */
    public function __construct(string $url)
    {
        $parsed = parse_url($url);
        if (!is_array($parsed) || !$parsed) {
            throw new HttpRequestException('Invalid Http request URL');
        }

        $this->url = $url;
        $this->parsed = $parsed;
        $this->path = $this->parsed["path"] ?? null;
        $this->pathParts = explode("/", trim($this->path ?? "", "/"));
    }

    /**
     * @return string
     */
    public function full(): string
    {
        return $this->url;
    }

    /**
     * @return string|null
     */
    public function scheme(): ?string
    {
        return $this->parsed["scheme"] ?? null;
    }

    /**
     * @return string|null
     */
    public function host(): ?string
    {
        return $this->parsed["host"] ?? null;
    }

    /**
     * @return int|null
     */
    public function port(): ?int
    {
        return $this->parsed["port"] ?? null;
    }

    /**
     * @return string|null
     */
    public function username(): ?string
    {
        return $this->parsed["user"] ?? null;
    }

    /**
     * @return string|null
     */
    public function password(): ?string
    {
        return $this->parsed["pass"] ?? null;
    }

    /**
     * @param int|null $index
     * @return string|null
     */
    public function path(?int $index = null): ?string
    {
        if (is_null($index)) {
            return $this->path;
        }

        return $this->pathParts[$index] ?? null;
    }

    /**
     * @param string|null $suffix
     * @return string
     */
    public function root(?string $suffix = null): string
    {
        return str_repeat("../", count($this->pathParts)) . ltrim($suffix ?? "", "/");
    }

    /**
     * @return string|null
     */
    public function query(): ?string
    {
        return $this->parsed["query"] ?? null;
    }

    /**
     * @return string|null
     */
    public function frag(): ?string
    {
        return $this->parsed["fragment"] ?? null;
    }

    /**
     * @return array
     */
    public function parsed(): array
    {
        return $this->parsed;
    }
}