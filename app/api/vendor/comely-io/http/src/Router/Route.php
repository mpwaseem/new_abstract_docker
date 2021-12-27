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

namespace Comely\Http\Router;

use Comely\Http\Exception\RouteException;
use Comely\Http\Request;
use Comely\Http\Router;
use Comely\Utils\OOP\OOP;

/**
 * Class Route
 * @package Comely\Http\Router
 */
class Route
{
    /** @var int */
    private $id;
    /** @var Router */
    private $router;
    /** @var string */
    private $url;
    /** @var string */
    private $matchPattern;
    /** @var string */
    private $controller;
    /** @var bool */
    private $isNamespace;
    /** @var array */
    private $ignorePathIndexes;
    /** @var null|string */
    private $fallbackController;
    /** @var null|Router\Authentication\AbstractAuth */
    private $auth;

    /**
     * Route constructor.
     * @param Router $router
     * @param string $url
     * @param string $namespaceOrClass
     * @throws RouteException
     */
    public function __construct(Router $router, string $url, string $namespaceOrClass)
    {
        $this->router = $router;
        $this->id = $this->router->count() + 1;

        // URL
        $url = "/" . trim(strtolower($url), "/"); // Case-insensitivity
        if (!preg_match('/^((\/?[\w\-\.]+)|(\/\*))*(\/\*)?$/', $url)) {
            throw new RouteException('Route URL argument contain an illegal character', $this->id);
        }

        // Controller or Namespace
        if (!preg_match('/^\w+(\\\\\w+)*(\\\\\*){0,1}$/i', $namespaceOrClass)) {
            throw new RouteException('Class or namespace contains an illegal character', $this->id);
        }

        $urlIsWildcard = substr($url, -2) === '/*' ? true : false;
        $controllerIsWildcard = substr($namespaceOrClass, -2) === '\*' ? true : false;
        if ($controllerIsWildcard && !$urlIsWildcard) {
            throw new RouteException('Route URL must end with "/*"', $this->id);
        }

        $this->url = $url;
        $this->matchPattern = $this->pattern();
        $this->controller = $namespaceOrClass;
        $this->isNamespace = $controllerIsWildcard ? true : false;
        $this->ignorePathIndexes = [];
    }

    /**
     * @return array
     */
    public function __debugInfo(): array
    {
        return [
            "id" => $this->id,
            "url" => $this->url,
            "matchPattern" => $this->matchPattern,
            "controller" => $this->controller
        ];
    }

    /**
     * @param string $controller
     * @return Route
     * @throws RouteException
     */
    public function fallbackController(string $controller): self
    {
        if (!OOP::isValidClass($controller)) {
            throw new RouteException('Fallback controller class is invalid or does not exist', $this->id);
        }

        $this->fallbackController = $controller;
        return $this;
    }

    /**
     * @return string
     */
    private function pattern(): string
    {
        // Init pattern from URL prop
        $pattern = "/^" . preg_quote($this->url, "/");

        // Last wildcard
        if (substr($pattern, -4) === "\/\*") {
            $pattern = substr($pattern, 0, -4) . '(\/[\w\-\.]+)*';
        }

        // Optional trailing "/"
        $pattern .= "\/?";

        // Middle wildcards
        $pattern = str_replace('\*', '[^\/]?[\w\-\.]+', $pattern);

        // Finalise and return
        return $pattern . "$/";
    }

    /**
     * @param int ...$indexes
     * @return Route
     */
    public function ignorePathIndexes(int ...$indexes): self
    {
        $this->ignorePathIndexes = $indexes;
        return $this;
    }

    /**
     * @param Authentication\AbstractAuth $auth
     * @return Route
     */
    public function auth(Router\Authentication\AbstractAuth $auth): self
    {
        $this->auth = $auth;
        return $this;
    }

    /**
     * @param Request $req
     * @param bool $bypassHttpAuth
     * @return string|null
     */
    public function request(Request $req, bool $bypassHttpAuth = false): ?string
    {
        $url = $req->url()->path();

        // RegEx match URL pattern
        if (!is_string($url) || !preg_match($this->matchPattern, $url)) {
            return null;
        }

        // Route Authentication
        if ($this->auth && !$bypassHttpAuth) {
            $this->auth->authenticate(
                $req->headers()->get("authorization") // HTTP header "Authorization"
            );
        }

        // Find HTTP Controller
        $controllerClass = null;
        if ($this->isNamespace) {
            $pathIndex = -1;
            $controllerClass = array_map(function ($part) use (&$pathIndex) {
                $pathIndex++;
                if ($part && !in_array($pathIndex, $this->ignorePathIndexes)) {
                    return OOP::PascalCase($part);
                }

                return null;
            }, explode("/", trim($url, "/")));

            $namespace = substr($this->controller, 0, -2);
            $controllerClass = sprintf('%s\%s', $namespace, implode('\\', $controllerClass));
            $controllerClass = preg_replace('/\\\{2,}/', '\\', $controllerClass);
            $controllerClass = rtrim($controllerClass, '\\');
        } else {
            $controllerClass = $this->controller;
        }

        return $controllerClass && class_exists($controllerClass) ? $controllerClass : $this->fallbackController;
    }
}