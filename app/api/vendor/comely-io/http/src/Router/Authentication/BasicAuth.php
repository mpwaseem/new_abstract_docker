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

namespace Comely\Http\Router\Authentication;

use Comely\Http\Exception\RouteAuthException;

/**
 * Class BasicAuth
 * @package Comely\Http\Router\Authentication
 */
class BasicAuth extends AbstractAuth
{
    /**
     * @param string|null $authorization
     * @throws RouteAuthException
     */
    public function authenticate(?string $authorization): void
    {
        try {
            $username = null;
            $password = null;

            if ($authorization) {
                $authorization = explode(" ", $authorization);
                if (strtolower($authorization[0]) !== "basic") {
                    throw new RouteAuthException(
                        sprintf('Realm "%s" requires Basic auth, Invalid authorization header', $this->realm)
                    );
                }

                $credentials = base64_decode($authorization[1]);
                if (!$credentials) {
                    throw new RouteAuthException('Invalid Basic authorization header');
                }

                $credentials = explode(":", $credentials);
                $username = $this->sanitize($credentials[0] ?? null);
                $password = $this->sanitize($credentials[1] ?? null);
                unset($credentials);
            }

            // Sent username?
            if (!$username) {
                throw new RouteAuthException(
                    sprintf('Authentication is required to enter "%s"', $this->realm)
                );
            }

            // Authenticate
            try {
                /** @var null|AuthUser $user */
                $user = $this->users[$username] ?? null;
                if (!$user) {
                    throw new RouteAuthException('No such username was found');
                }

                if ($user->password !== $password) {
                    throw new RouteAuthException('Incorrect password');
                }
            } catch (RouteAuthException $e) {
                throw new RouteAuthException('Incorrect username or password');
            }

        } catch (RouteAuthException $e) {
            header(sprintf('WWW-Authenticate: Basic realm="%s"', $this->realm));
            header('HTTP/1.0 401 Unauthorized');

            // Callback method for unauthorized requests
            if (is_callable($this->unauthorized)) {
                call_user_func($this->unauthorized);
            }

            throw new RouteAuthException($e->getMessage());
        }
    }

    /**
     * @param $in
     * @return string
     */
    private function sanitize($in): string
    {
        if (!is_string($in) || !$in) {
            return "";
        }

        return filter_var($in, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
    }
}