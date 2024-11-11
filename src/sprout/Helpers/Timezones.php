<?php
/*
 * Copyright (C) 2024 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout\Helpers;

use DateTime;
use DateTimeZone;

/**
* @package Admin
**/

/**
* Tooling to handle conversions between given timezone and UTC
**/
class Timezones
{
    /**
     * Convert a timestamp to a date string in the given timezone
     *
     * @param string $timezone
     * @param int $timestamp
     * @param string $format
     * @return string The date string in the requested format
     */
    public static function utcTimeToDate(string $timezone, int $timestamp, $format = 'Y-m-d H:i:s')
    {
        $timezone_dt = new DateTimeZone($timezone);
        $date = new DateTime('@'.$timestamp);
        $date->setTimezone($timezone_dt);

        return $date->format($format);
    }


    /**
     * Convert a date string to a timestamp in the given timezone
     *
     * @param string $timezone
     * @param string $date
     * @return int The timestamp
     */
    public static function utcDateToTime(string $timezone, string $date)
    {
        $timezone_dt = new DateTimeZone($timezone);
        $date = new DateTime($date, $timezone_dt);

        return $date->getTimestamp();
    }


    /**
     * Convert a UTC date string to a date string in the given timezone
     *
     * @param string $timezone
     * @param string $date
     * @param string $format
     *
     * @return string The date string in the requested format
     */
    public static function utcDateToLocal(string $timezone, string $date, $format = 'Y-m-d H:i:s')
    {
        $timezone_dt = new DateTimeZone('UTC');
        $date = new DateTime($date, $timezone_dt);
        $date->setTimezone(new DateTimeZone($timezone));

        return $date->format($format);
    }


    /**
     * Convert a local date string to a date string in UTC
     *
     * @param string $timezone
     * @param string $date
     * @param string $format
     *
     * @return string The date string in the requested format
     */
    public static function localDateToUtc(string $timezone, string $date, $format = 'Y-m-d H:i:s')
    {
        $timezone_dt = new DateTimeZone($timezone);
        $date = new DateTime($date, $timezone_dt);
        $date->setTimezone(new DateTimeZone('UTC'));

        return $date->format($format);
    }


    /**
     * Get the current datetime in the given timezone
     *
     * @param string $timezone
     * @param string $format
     * @return string The date string in the requested format
     */
    public static function dateLocalNow(string $timezone, $format = 'Y-m-d H:i:s')
    {
        return self::utcTimeToDate($timezone, time(), $format);
    }

}


