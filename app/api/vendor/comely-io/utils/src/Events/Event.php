<?php
/**
 * This file is a part of "comely-io/utils" package.
 * https://github.com/comely-io/utils
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comely-io/utils/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\Utils\Events;

/**
 * Class Event
 * @package Comely\Utils\Events
 */
class Event
{
    /** @var EventsRegister */
    private $register;
    /** @var string */
    private $name;
    /** @var array */
    private $listeners;

    /**
     * Event constructor.
     * @param EventsRegister $register
     * @param string $name
     */
    public function __construct(EventsRegister $register, string $name)
    {
        if (!preg_match('/^[\w\-\.]+$/', $name)) {
            throw new \InvalidArgumentException('Invalid event name');
        }

        $this->register = $register;
        $this->name = $name;
        $this->listeners = [];
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return EventsRegister
     */
    public function register(): EventsRegister
    {
        return $this->register;
    }

    /**
     * @param callable $callback
     * @return Event
     */
    public function listen(callable $callback): self
    {
        $this->listeners[] = $callback;
        return $this;
    }

    /**
     * @param array|null $params
     * @return int
     */
    public function trigger(?array $params = null): int
    {
        if (!$this->listeners) {
            return 0;
        }

        $params = $params ?? [];
        array_push($params, $this);
        $count = 0;
        foreach ($this->listeners as $listener) {
            call_user_func_array($listener, $params);
            $count++;
        }

        return $count;
    }
}