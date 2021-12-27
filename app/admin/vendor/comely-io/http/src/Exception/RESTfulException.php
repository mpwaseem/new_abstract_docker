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

namespace Comely\Http\Exception;

/**
 * Class RESTfulException
 * @package Comely\Http\Exception
 */
class RESTfulException extends RouterException
{
    /**
     * @param string $method
     * @param string $contentType
     * @return RESTfulException
     */
    public static function payloadMethodTypeError(string $method, string $contentType): self
    {
        return new self(sprintf('HTTP method "%s" cannot accept input content type "%s"', $method, $contentType));
    }

    /**
     * @param string $method
     * @param string $contentType
     * @return RESTfulException
     */
    public static function payloadStreamError(string $method, string $contentType): self
    {
        return new self(sprintf('Failed to parse input stream "%s" sent using "%s" method', $contentType, $method));
    }
}