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

use Comely\Http\Exception\SSL_Exception;

/**
 * Class SSL
 * @package Comely\Http\Query
 */
class SSL
{
    /** @var bool */
    private $verify;
    /** @var null|string */
    private $certPath;
    /** @var null|string */
    private $certPassword;
    /** @var null|string */
    private $privateKeyPath;
    /** @var null|string */
    private $privateKeyPassword;
    /** @var null|string */
    private $certAuthorityPath;

    /**
     * SSL constructor.
     * @throws SSL_Exception
     */
    public function __construct()
    {
        $this->verify = true;

        // Make sure cUrl can work with SSL
        if (!(curl_version()["features"] & CURL_VERSION_SSL)) {
            throw new SSL_Exception('SSL support is unavailable in your cURL build');
        }
    }

    /**
     * @param bool $bool
     * @return SSL
     */
    public function verify(bool $bool): self
    {
        $this->verify = $bool;
        return $this;
    }

    /**
     * @param string $file
     * @param null|string $password
     * @return SSL
     * @throws SSL_Exception
     */
    public function certificate(string $file, ?string $password = null): self
    {
        $path = realpath($file);
        if (!$path || !is_readable($path) || !is_file($path)) {
            throw new SSL_Exception(sprintf('SSL certificate "%s" not found or not readable', basename($file)));
        }

        $this->certPath = $path;
        $this->certPassword = $password;
        return $this;
    }

    /**
     * @param string $file
     * @param null|string $password
     * @return SSL
     * @throws SSL_Exception
     */
    public function privateKey(string $file, ?string $password = null): self
    {
        $path = realpath($file);
        if (!$path || !is_readable($path) || !is_file($path)) {
            throw new SSL_Exception(sprintf('SSL private key "%s" not found or not readable', basename($file)));
        }

        $this->privateKeyPath = $file;
        $this->privateKeyPassword = $password;
        return $this;
    }

    /**
     * @param string $path
     * @return SSL
     * @throws SSL_Exception
     */
    public function ca(string $path): self
    {
        $path = realpath($path);
        if (!$path || !is_readable($path) || !is_file($path)) {
            throw new SSL_Exception('Path to CA certificate(s) is invalid or not readable');
        }

        $this->certAuthorityPath = $path;
        return $this;
    }

    /**
     * @param string $path
     * @return SSL
     * @throws SSL_Exception
     */
    public function certificateAuthority(string $path): self
    {
        return $this->ca($path);
    }

    /**
     * @param $method
     * @param $args
     * @throws SSL_Exception
     */
    public function __call($method, $args)
    {
        switch ($method) {
            case "register":
                $this->register($args[0] ?? null);
                return;
        }

        throw new SSL_Exception(sprintf('Cannot call inaccessible method "%s"', $method));
    }

    /**
     * @param $ch
     * @throws SSL_Exception
     */
    private function register($ch): void
    {
        // Verify param is a resource
        if (!is_resource($ch)) {
            throw new SSL_Exception('Cannot register SSL opts to a non-resource');
        }

        // Bypass SSL check?
        if (!$this->verify) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            return; // Return
        }

        // Work with SSL
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // CA Bundle
        if ($this->certAuthorityPath) {
            if (is_file($this->certAuthorityPath)) {
                curl_setopt($ch, CURLOPT_CAINFO, $this->certAuthorityPath);
            } elseif (is_dir($this->certAuthorityPath)) {
                curl_setopt($ch, CURLOPT_CAPATH, $this->certAuthorityPath);
            }
        }

        if ($this->certPath) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->certPath);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, "PEM");
            if ($this->certPassword) {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certPassword);
            }
        }

        if ($this->privateKeyPath) {
            curl_setopt($ch, CURLOPT_SSLKEY, $this->privateKeyPath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, "PEM");
            if ($this->privateKeyPassword) {
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $this->privateKeyPassword);
            }
        }
    }
}