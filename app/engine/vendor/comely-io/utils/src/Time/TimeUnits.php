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

namespace Comely\Utils\Time;

use Comely\Utils\Time\TimeUnits\TimeInUnits;
use Comely\Utils\Time\TimeUnits\UnitsLabels;

/**
 * Class TimeUnits
 * @package Comely\Utils\Time
 */
class TimeUnits
{
    /** @var UnitsLabels */
    private $labels;
    /** @var bool */
    private $rtl;

    /**
     * TimeUnits constructor.
     */
    public function __construct()
    {
        $this->labels = new UnitsLabels();
        $this->rtl = false;
    }

    /**
     * @return UnitsLabels
     */
    public function labels(): UnitsLabels
    {
        return $this->labels;
    }

    /**
     * @return TimeUnits
     */
    public function rtl(): self
    {
        $this->rtl = true;
        return $this;
    }

    /**
     * @param int $timeInSeconds
     * @return TimeInUnits
     */
    public function timeToUnits(int $timeInSeconds): TimeInUnits
    {
        $timeInUnits = new TimeInUnits();
        $timeInUnits->seconds = $timeInSeconds;

        // Days
        while ($timeInUnits->seconds >= 86400) {
            $timeInUnits->days++;
            $timeInUnits->seconds -= 86400;
        }

        // Hours
        while ($timeInUnits->seconds >= 3600) {
            $timeInUnits->hours++;
            $timeInUnits->seconds -= 3600;
        }

        // Minutes
        while ($timeInUnits->seconds >= 60) {
            $timeInUnits->minutes++;
            $timeInUnits->seconds -= 60;
        }

        return $timeInUnits;
    }

    /**
     * @param int $timeInSeconds
     * @param string $sep
     * @param string $unitsSep
     * @param string|null $finalSep
     * @return string
     */
    public function timeToString(int $timeInSeconds, string $sep = "", string $unitsSep = " ", ?string $finalSep = null): string
    {
        $timeInUnits = $this->timeToUnits($timeInSeconds);
        $timeString = [];

        foreach (["days", "hours", "minutes", "seconds"] as $unit) {
            if ($timeInUnits->$unit) {
                $timeString[] = ($unit === "seconds" && $finalSep) ? $finalSep : $unitsSep;

                $unitString = [];
                $unitString[] = $timeInUnits->$unit;
                $unitString[] = $this->labels->get($unit, $timeInUnits->$unit);

                if ($this->rtl) {
                    $unitString = array_reverse($unitString);
                }

                $timeString[] = implode($sep, $unitString);
                unset($unitString);
            }
        }

        if ($this->rtl) {
            $timeString = array_reverse($timeString);
        }

        return trim(implode("", $timeString), $unitsSep);
    }

    /**
     * @param string $str
     * @param bool $strict
     * @return TimeInUnits
     */
    public function stringToUnits(string $str, bool $strict = true): TimeInUnits
    {
        $timeInUnits = new TimeInUnits();
        $units = preg_replace('/\s/', "", trim(mb_strtolower($str)));
        $units = preg_replace('/\(\)/', "", $units); // Remove parentheses
        $units = preg_split('/([0-9]+[^0-9]+)/', $units, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        foreach ($units as $unit) {
            $unitNum = intval(preg_replace('/[^0-9]/', "", $unit));
            $unitLabel = preg_replace('/[0-9]/', "", $unit);

            if ($unitNum > 0 && $unitLabel) {
                $unitId = $this->labels->find($unitLabel);
                if ($unitId && property_exists($timeInUnits, $unitId)) {
                    $timeInUnits->$unitId = $unitNum;
                } else {
                    if ($strict) {
                        throw new \InvalidArgumentException('Serialized time string contains an invalid unit');
                    }
                }

                continue;
            }

            if ($strict) {
                throw new \InvalidArgumentException('Invalid serialized time string');
            }
        }

        return $timeInUnits;
    }

    /**
     * @param string $str
     * @param bool $strict
     * @return int
     */
    public function stringToTime(string $str, bool $strict = true): int
    {
        $timeInUnits = $this->stringToUnits($str, $strict);

        $timeInSeconds = $timeInUnits->seconds;
        $timeInSeconds += $timeInUnits->days * 86400;
        $timeInSeconds += $timeInUnits->hours * 3600;
        $timeInSeconds += $timeInUnits->minutes * 60;
        return $timeInSeconds;
    }
}