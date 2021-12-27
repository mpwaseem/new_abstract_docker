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
 * Class Authentication
 * @package Comely\Http\Query
 */
class Authentication
{
    public const BASIC = 0x64;
    public const DIGEST = 0xc8;

    /** @var null|int */
    private $type;
    /** @var null|string */
    private $username;
    /** @var null|string */
    private $password;

    /**
     * Basic HTTP authorization
     * @param string $username
     * @param string $password
     */
    public function basic(string $username, string $password)
    {
        $this->type = self::BASIC;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * @return int|null
     */
    public function type(): ?int
    {
        return $this->type;
    }

    /**
     * @return string|null
     */
    public function username(): ?string
    {
        return $this->username;
    }

    /**
     * @return string|null
     * @return string|null
     */
    public function password(): ?string
    {
        return $this->password;
    }

    /**
     * @param $method
     * @param $args
     * @throws HttpRequestException
     */
    public function __call($method, $args)
    {
        switch ($method) {
            case "register":
                $this->register($args[0] ?? null);
                return;
        }

        throw new HttpRequestException(sprintf('Cannot call inaccessible method "%s"', $method));
    }

    /**
     * @param $ch
     * @throws HttpRequestException
     */
    private function register($ch)
    {
        if (!is_resource($ch)) {
            throw new HttpRequestException('Cannot register Authentication opts to a non-resource');
        }

        switch ($this->type) {
            case self::BASIC:
                curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:%s', $this->username, $this->password));
                break;
        }
    }
}