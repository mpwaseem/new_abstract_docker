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

use Comely\Http\Exception\RESTfulException;
use Comely\Http\Router\AbstractController;

/**
 * Class RESTful
 * @package Comely\Http
 */
class RESTful
{
    /**
     * @param Router $router
     * @param \Closure $closure
     * @return AbstractController
     * @throws Exception\HttpRequestException
     * @throws Exception\RouterException
     * @throws RESTfulException
     * @throws \ReflectionException
     */
    public static function Request(Router $router, \Closure $closure): AbstractController
    {
        $method = $_SERVER["REQUEST_METHOD"] ?? "";
        $url = $_SERVER["REQUEST_URI"] ?? "";

        // Check if URL not rewritten properly (i.e. called /index.php/some/controller)
        if (preg_match('/^\/?[\w\-\.]+\.php\//', $url)) {
            $url = explode("/", $url);
            unset($url[1]);
            $url = implode("/", $url);
        }

        $req = new Request($method, $url);

        // Headers
        foreach ($_SERVER as $key => $value) {
            $value = filter_var(strval($value), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
            $key = explode("_", $key);
            if ($key[0] === "HTTP") {
                unset($key[0]);
                $key = array_map(function ($part) {
                    return ucfirst(strtolower($part));
                }, $key);

                $req->headers()->set(implode("-", $key), $value);
            }
        }

        // Payload
        $req->payload()->use(self::Sanitize(self::Payload($req->method())));

        // Bypass HTTP auth.
        $bypassAuth = false;
        if ($req->method() === "OPTIONS") {
            $bypassAuth = true;
        }

        // Get Controller
        $controller = $router->request($req, $bypassAuth);

        // Callback Close
        if ($closure) {
            call_user_func($closure, $controller);
        }

        return $controller;
    }

    /**
     * @param string $method
     * @return array
     * @throws RESTfulException
     */
    public static function Payload(string $method): array
    {
        $payload = []; // Initiate payload
        $contentType = strtolower(trim(explode(";", $_SERVER["CONTENT_TYPE"] ?? "")[0]));

        // Ready query string
        if (isset($_SERVER["QUERY_STRING"])) {
            parse_str($_SERVER["QUERY_STRING"], $payload);
        }

        // Get input body from stream
        $body = null;
        $stream = file_get_contents("php://input");
        if ($stream) {
            switch ($contentType) {
                case "application/json":
                    $body = json_decode($stream, true);
                    break;
                case "application/x-www-form-urlencoded":
                    $body = [];
                    parse_str($stream, $body);
                    break;
                case "multipart/form-data":
                    if ($method !== "POST") {
                        throw RESTfulException::payloadMethodTypeError($method, $contentType);
                    }

                    $body = $_POST; // Simply use $_POST var;
                    break;
            }

            if (!is_array($body)) {
                throw RESTfulException::payloadStreamError($method, $contentType);
            }
        }


        return is_array($body) ? array_merge($body, $payload) : $payload;
    }

    /**
     * @param array $data
     * @return array
     */
    public static function Sanitize(array $data): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            $key = strval($key);
            if (!preg_match('/^[\w\-\.]+$/i', $key)) {
                continue; // Invalid key; Skip
            }

            if (is_scalar($value)) {
                if (is_string($value)) {
                    $value = filter_var($value, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
                }

                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = self::Sanitize($value);
            }
        }

        return $sanitized;
    }
}