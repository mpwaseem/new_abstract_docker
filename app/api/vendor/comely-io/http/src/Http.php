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

namespace Comely\Http;

/**
 * Class Http
 * @package Comely\Http
 */
class Http
{
    /** string Version (Major.Minor.Release-Suffix) */
    public const VERSION = "1.0.47";
    /** int Version (Major * 10000 + Minor * 100 + Release) */
    public const VERSION_ID = 10047;

    // HTTP methods
    public const METHODS = ["GET", "POST", "PUT", "DELETE", "OPTIONS"];

    // HTTP version
    public const HTTP_VERSION_1 = CURL_HTTP_VERSION_1_0;
    public const HTTP_VERSION_1_1 = CURL_HTTP_VERSION_1_1;
    public const HTTP_VERSION_2 = CURL_HTTP_VERSION_2_0;

    public const HTTP_VERSIONS = [
        self::HTTP_VERSION_1,
        self::HTTP_VERSION_1_1,
        self::HTTP_VERSION_2
    ];

    // Status Codes Messages
    public const MESSAGES = [
        100 => "Continue",
        200 => "OK",
        201 => "Created",
        202 => "Accepted",
        204 => "No Content",
        304 => "Not Modified",
        400 => "Bad Request",
        401 => "Unauthorized",
        403 => "Forbidden",
        404 => "Not Found",
        409 => "Conflict",
        500 => "Internal Server Error"
    ];

    /**
     * @param string $url
     * @return Request
     * @throws Exception\HttpRequestException
     */
    public static function Get(string $url): Request
    {
        return new Request("GET", $url);
    }

    /**
     * @param string $url
     * @return Request
     * @throws Exception\HttpRequestException
     */
    public static function Post(string $url): Request
    {
        return new Request("POST", $url);
    }

    /**
     * @param string $url
     * @return Request
     * @throws Exception\HttpRequestException
     */
    public static function Put(string $url): Request
    {
        return new Request("PUT", $url);
    }

    /**
     * @param string $url
     * @return Request
     * @throws Exception\HttpRequestException
     */
    public static function Delete(string $url): Request
    {
        return new Request("DELETE", $url);
    }
}
