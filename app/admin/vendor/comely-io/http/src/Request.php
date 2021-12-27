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

use Comely\Http\Exception\HttpRequestException;
use Comely\Http\Query\AbstractReqRes;
use Comely\Http\Query\CurlQuery;
use Comely\Http\Query\Headers;
use Comely\Http\Query\Payload;
use Comely\Http\Query\URL;
use Comely\Http\Router\AbstractController;

/**
 * Class Request
 * @package Comely\Http
 */
class Request extends AbstractReqRes
{
    /** @var null|int */
    protected $version;
    /** @var string */
    protected $method;
    /** @var string */
    protected $url;

    /**
     * Request constructor.
     * @param string $method
     * @param string $url
     * @throws HttpRequestException
     */
    public function __construct(string $method, string $url)
    {
        parent::__construct();

        // HTTP method
        $this->method = strtoupper($method);
        if (!in_array($method, Http::METHODS)) {
            throw new HttpRequestException('Invalid HTTP request method');
        }

        $this->url = new URL($url);
    }

    /**
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * @return URL
     */
    public function url(): URL
    {
        return $this->url;
    }

    /**
     * @param mixed ...$props
     */
    public function override(...$props): void
    {
        foreach ($props as $prop) {
            if ($prop instanceof Headers) {
                $this->headers = $prop;
                return;
            }

            if ($prop instanceof Payload) {
                $this->payload = $prop;
                return;
            }

            if ($prop instanceof URL) {
                $this->url = $prop;
                return;
            }
        }
    }

    /**
     * @param Router $router
     * @return AbstractController
     * @throws Exception\RouterException
     * @throws HttpRequestException
     * @throws \ReflectionException
     */
    public function routeToController(Router $router): AbstractController
    {
        if ($this->url->scheme() || $this->url->host()) {
            throw new HttpRequestException('Request has URL scheme/host set; It cannot be routed internally');
        }

        return $router->request($this, true);
    }

    /**
     * @return CurlQuery
     * @throws HttpRequestException
     */
    public function curl(): CurlQuery
    {
        return new CurlQuery($this);
    }
}