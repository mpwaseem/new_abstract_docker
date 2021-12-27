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

namespace Comely\Http\Response;

use Comely\Http\Query\AbstractReqRes;
use Comely\Http\Query\Headers;
use Comely\Http\Query\Payload;
use Comely\Http\Query\ResponseBody;

/**
 * Class AbstractResponse
 * @package Comely\Http\Response
 */
abstract class AbstractResponse extends AbstractReqRes
{
    /** @var null|int */
    protected $code;
    /** @var null|ResponseBody */
    protected $body;

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

            if ($prop instanceof ResponseBody) {
                $this->body = $prop;
                return;
            }
        }
    }
}