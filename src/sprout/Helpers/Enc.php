<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
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



/**
* Encoding of data for various outputs
**/
class Enc
{

    /**
     * Strip funky stuff from a string.
     * Funky stuff is anything in the ASCII control plane (0x00 - 0x1F)
     * Except tab, line feed, carriage return
     * @param mixed $value
     * @return string
     */
    public static function cleanfunky($value)
    {
        if (is_array($value)) return '';
        if (is_object($value)) return '';
        return preg_replace('![\x00-\x08\x0B\x0C\x0E-\x1F]!', '', (string) $value);
    }

    /**
     * Encoding for HTML
     * Existing HTML entities in a string will be double-encoded
     * @param string|int|float|bool $value The text to encode. This should be plain (i.e. non-HTML) text
     * @return string
     * @example $html = Enc::html('A & B'); // returns A &amp; B
     * @example $bad_html = Enc::html('A &amp; B'); // don't do this; returns A &amp;amp; B
     */
    public static function html($value)
    {
        return htmlspecialchars(self::cleanfunky($value), ENT_COMPAT, 'UTF-8');
    }

    /**
     * Encoding for HTML, but safe from double-encoding of existing entities
     * @param string|int|float|bool $value The text to encode. This should be plain (i.e. non-HTML) text
     * @return string
     * @example $html = Enc::html('A & B'); // returns A &amp; B
     * @example $html = Enc::html('A &amp; B'); // this is fine; returns A &amp; B
     */
    public static function htmlNoDup($value)
    {
        return htmlspecialchars(self::cleanfunky($value), ENT_COMPAT, 'UTF-8', false);
    }

    /**
    * Encoding for XML
    *
    * @param string $value The value to encode
    **/
    public static function xml($value)
    {
        $value = self::cleanfunky($value);
        $value = preg_replace('/[\s\r\n][\s\r\n]+/', "\n", $value);
        return htmlspecialchars($value, ENT_COMPAT, 'UTF-8');
    }

    /**
    * Encoding for a part of a URL (i.e. GET parameter value
    *
    * @param string $value The value to encode
    **/
    public static function url($value)
    {
        return urlencode(self::cleanfunky($value));
    }

    /**
    * Encoding for ID or CLASS attributes
    *
    * @param string $value The value to encode
    **/
    public static function id($value)
    {
        $value = self::cleanfunky($value);
        $value = trim($value);
        $value = str_replace(' ', '_', $value);
        $value = preg_replace('/[^a-z0-9_-]/i', '', $value);

        $value = preg_replace('/__/', '_', $value);
        $value = trim($value, '_');

        return $value;
    }

    /**
    * Encoding for going inside a JS string
    *
    * @param string $value The value to encode
    **/
    public static function js($value)
    {
        $value = self::cleanfunky($value);
        $value = addslashes($value);
        $value = str_replace("\n", '\n', $value);
        $value = str_replace("\r", '\r', $value);
        $value = str_replace("\t", '\t', $value);
        return $value;
    }

    /**
    * Encoding for field name in a form element
    *
    * @param string $value The value to encode
    **/
    public static function httpfield($value)
    {
        $value = self::cleanfunky($value);
        $value = str_replace(' ', '_', $value);
        $value = preg_replace('/[^a-zA-Z0-9:_.-]/', '', $value);

        $value = preg_replace('/__/', '_', $value);
        $value = trim($value, '_');

        return $value;
    }

    /**
     * Nice name of something when dealing with friendly URLs.
     * This provides the backend for {@see Slug::create}
     *
     * @param string $value The value to encode
     * @return string
     */
    public static function urlname($value, $delimiter = '-')
    {
        $value = self::cleanfunky($value);
        $value = strtolower(trim($value, "&_- \t\n\r\0\x0B"));

        // Basic replacements
        $value = str_replace('&', 'and', $value);
        $value = str_replace(array(' ', '-', '/', '\\'), '_', $value);

        // Cleanup
        $value = preg_replace('/__/', '_', $value);
        $value = preg_replace('/[^a-z_0-9]/', '', $value);
        $value = trim($value, '_');

        $value = str_replace('_', $delimiter, $value);

        return $value;
    }

    /**
    * Returns the JS code needed to initialise a Date() object with the specified date.
    * This should only be used for dates. For times or date-times, use a different function.
    *
    * From types:
    *   'mysql' - a mysql-formatted date in the form YYYY-MM-DD
    *   'array' - an array [ <day> , <month> , <year> ]
    *
    * @param string|array $value The value to convert.
    * @param string $from What format the date was in origanally.
    * @return string|null JavaScript snippet for the given date or NULL if the input value is invalid
    **/
    public static function jsdate($value, $from = 'mysql')
    {
        $day = null;
        $month = null;
        $year = null;

        switch ($from) {
            case 'mysql':
                $value = explode('-', self::cleanfunky($value));
                if (count($value) == 3) list ($year, $month, $day) = $value;
                break;

            case 'array':
                if (is_array($value) and count($value) == 3) list ($day, $month, $year) = $value;
                break;

            default:
                return null;
        }

        if (!$day or !$month or !$year) return null;

        // Convert a 2-digit year to a 4-digit year (just in case...)
        if (strlen($year) == 2) {
            if ($year >= 50) {
                $year = '19' . $year;
            } else {
                $year = '20' . $year;
            }
        }

        $year = (int) $year;
        $month = (int) $month;
        $day = (int) $day;

        if ($day == 0 or $month == 0 or $year == 0) return null;

        return "new Date({$year}, {$month} - 1, {$day})";
    }
}
