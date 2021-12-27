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

/**
 * Class AbstractReqRes
 * @package Comely\Http\Query
 */
abstract class AbstractReqRes
{
    /** @var Headers */
    protected $headers;
    /** @var Payload */
    protected $payload;

    /**
     * AbstractReqRes constructor.
     */
    public function __construct()
    {
        $this->headers = new Headers();
        $this->payload = new Payload();
    }

    /**
     * @return Headers
     */
    public function headers(): Headers
    {
        return $this->headers;
    }

    /**
     * @return Payload
     */
    public function payload(): Payload
    {
        return $this->payload;
    }

    /**
     * @return string|null
     */
    public function contentType(): ?string
    {
        if ($this->headers->has("content-type")) {
            $contentType = explode(";", $this->headers->get("content-type"));
            return trim($contentType[0]);
        }

        return null;
    }
}