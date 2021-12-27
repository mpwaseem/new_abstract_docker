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
use Comely\Http\Exception\HttpResponseException;
use Comely\Http\Http;
use Comely\Http\Request;
use Comely\Http\Response\CurlResponse;

/**
 * Class CurlQuery
 * @package Comely\Http\Query
 */
class CurlQuery
{
    /** @var Request */
    private $req;
    /** @var null|int */
    private $httpVersion;
    /** @var null|string */
    private $userAgent;
    /** @var null|Authentication */
    private $auth;
    /** @var null|SSL */
    private $ssl;
    /** @var bool Send payload as application/json regardless of content-type */
    private $contentTypeJSON;
    /** @var bool Expect JSON body in response */
    private $expectJSON;
    /** @var bool If expectJSON is true, use this prop to ignore received content-type */
    private $expectJSON_ignoreResContentType;
    /** @var bool */
    private $debug;

    /**
     * CurlQuery constructor.
     * @param Request $req
     * @throws HttpRequestException
     */
    public function __construct(Request $req)
    {
        if (!$req->url()->scheme() || !$req->url()->host()) {
            throw new HttpRequestException('Cannot use local request with cURL lib');
        }

        $this->req = $req;
        $this->contentTypeJSON = false;
        $this->expectJSON = false;
        $this->expectJSON_ignoreResContentType = false;
        $this->debug = true;
    }

    /**
     * @param bool $trigger
     * @return CurlQuery
     */
    public function debug(bool $trigger): self
    {
        $this->debug = $trigger;
        return $this;
    }

    /**
     * @return Authentication
     */
    public function auth(): Authentication
    {
        if (!$this->auth) {
            $this->auth = new Authentication();
        }

        return $this->auth;
    }

    /**
     * @return SSL
     * @throws \Comely\Http\Exception\SSL_Exception
     */
    public function ssl(): SSL
    {
        if (!$this->ssl) {
            $this->ssl = new SSL();
        }

        return $this->ssl;
    }

    /**
     * @param int $version
     * @return CurlQuery
     */
    public function useHttpVersion(int $version): self
    {
        if (!in_array($version, Http::HTTP_VERSIONS)) {
            throw new \OutOfBoundsException('Invalid query Http version');
        }

        $this->httpVersion = $version;
        return $this;
    }

    /**
     * @param string|null $agent
     * @return CurlQuery
     */
    public function userAgent(?string $agent = null): self
    {
        $this->userAgent = $agent;
        return $this;
    }

    /**
     * @return CurlQuery
     */
    public function contentTypeJSON(): self
    {
        $this->contentTypeJSON = true;
        return $this;
    }

    /**
     * @param bool $ignoreReceivedContentType
     * @return CurlQuery
     */
    public function expectJSON(bool $ignoreReceivedContentType = false): self
    {
        $this->expectJSON = true;
        $this->expectJSON_ignoreResContentType = $ignoreReceivedContentType;
        return $this;
    }

    /**
     * @return CurlResponse
     * @throws HttpRequestException
     * @throws HttpResponseException
     * @throws \Comely\Http\Exception\SSL_Exception
     */
    public function send(): CurlResponse
    {
        $ch = curl_init(); // Init cURL handler
        curl_setopt($ch, CURLOPT_URL, $this->req->url()->full()); // Set URL
        if ($this->httpVersion) {
            curl_setopt($ch, CURLOPT_HTTP_VERSION, $this->httpVersion);
        }

        // SSL?
        if (strtolower($this->req->url()->scheme()) === "https") {
            call_user_func([$this->ssl(), "register"], $ch); // Register SSL options
        }

        // Payload
        switch ($this->req->method()) {
            case "GET":
                curl_setopt($ch, CURLOPT_HTTPGET, 1);
                if ($this->req->payload()->count()) {
                    $sep = $this->req->url()->query() ? "&" : "?"; // Override URL
                    curl_setopt($ch, CURLOPT_URL, $this->req->url()->full() . $sep . http_build_query($this->req->payload()->array()));
                }

                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->req->method());
                if ($this->req->payload()->count()) {
                    if ($this->contentTypeJSON || $this->req->contentType() === "application/json") {
                        $payload = json_encode($this->req->payload()->array());

                        // Content-type header
                        if (!$this->req->headers()->has("content-type")) {
                            $this->req->headers()->set("Content-type", "application/json; charset=utf-8");
                        }

                        // Content-length header
                        if (!$this->req->headers()->has("content-length")) {
                            $this->req->headers()->set("Content-length", strval(strlen($payload)));
                        }
                    } else {
                        $payload = http_build_query($this->req->payload()->array());
                    }

                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                }
                break;
        }

        // Headers
        if ($this->req->headers()->count()) {
            $httpHeaders = [];
            foreach ($this->req->headers()->array() as $hn => $hv) {
                $httpHeaders[] = $hn . ": " . $hv;
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
        }

        // Authentication
        if ($this->auth) {
            call_user_func([$this->auth, "register"], $ch);
        }

        // User agent
        if ($this->userAgent) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
        }

        // Response Headers
        $responseHeaders = new Headers();

        // Finalise request
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function (/** @noinspection PhpUnusedParameterInspection */
            $ch, $line) use ($responseHeaders) {
            if (preg_match('/^[\w\-]+:/', $line)) {
                $header = preg_split('/:/', $line, 2);
                $name = trim(strval($header[0] ?? null));
                $value = trim(strval($header[1] ?? null));
                if ($name && $value) {
                    $responseHeaders->set($name, $value);
                }
            }

            return strlen($line);
        });

        // Execute cURL request
        $body = curl_exec($ch);
        if ($body === false) {
            throw new HttpRequestException(
                sprintf('cURL error [%d]: %s', curl_error($ch), curl_error($ch))
            );
        }

        // Response code
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $responseType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        if (is_string($responseCode) && preg_match('/[0-9]+/', $responseCode)) {
            $responseCode = intval($responseCode); // In case HTTP response code is returned as string
        }

        if (!is_int($responseCode)) {
            throw new HttpResponseException('Could not retrieve HTTP response code');
        }

        // Prepare Response
        $response = new CurlResponse($responseCode);
        foreach ($responseHeaders as $name => $val) {
            $response->headers()->set($name, $val);
        }

        // Close cURL resource
        curl_close($ch);

        // Update Response object
        $responseBody = new ResponseBody($body);
        $responseBody->readOnly(true);
        $response->override($responseBody, $responseHeaders); // Set Response raw body and headers

        // Response Body
        $responseIsJSON = is_string($responseType) && preg_match('/json/', $responseType) ? true : $this->expectJSON;
        if ($responseIsJSON) {
            if (!$this->expectJSON_ignoreResContentType) {
                if (!is_string($responseType)) {
                    throw new HttpResponseException('Invalid "Content-type" header received, expecting JSON', $responseCode);
                }

                if (strtolower(trim(explode(";", $responseType)[0])) !== "application/json") {
                    throw new HttpResponseException(
                        sprintf('Expected "application/json", got "%s"', $responseType),
                        $responseCode
                    );
                }
            }

            // Decode JSON body
            $json = json_decode($body, true);
            if (!$json) {
                if ($this->debug) {
                    $jsonLastErrorMsg = json_last_error_msg();
                    if ($jsonLastErrorMsg) {
                        trigger_error($jsonLastErrorMsg, E_USER_WARNING);
                    }
                }

                throw new HttpResponseException('An error occurred while decoding JSON body');
            }

            $response->payload()->use($json);
        }

        return $response;
    }
}
