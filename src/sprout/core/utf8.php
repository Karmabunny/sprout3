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

/**
 * A port of phputf8 to a unified file/class. Checks PHP status to ensure that
 * UTF-8 support is available and normalize global variables to UTF-8. It also
 * provides multi-byte aware replacement string functions.
 *
 * This file is licensed differently from the rest of Kohana. As a port of
 * phputf8, which is LGPL software, this file is released under the LGPL.
 *
 * PCRE needs to be compiled with UTF-8 support (--enable-utf8).
 * Support for Unicode properties is highly recommended (--enable-unicode-properties).
 * @see http://php.net/manual/reference.pcre.pattern.modifiers.php
 *
 * UTF-8 conversion will be much more reliable if the iconv extension is loaded.
 * @see http://php.net/iconv
 *
 * The mbstring extension is highly recommended, but must not be overloading
 * string functions.
 * @see http://php.net/mbstring
 *
 * $Id: utf8.php 3769 2008-12-15 00:48:56Z zombor $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */

if (!preg_match('/^.$/u', 'ñ')) {
    throw new Exception('PCRE is missing UTF-8 support');
}

if (!extension_loaded('iconv')) {
    throw new Exception('PHP iconv extension not loaded');
}

// phpcs:disable
if (
    defined('MB_OVERLOAD_STRING')
    and extension_loaded('mbstring')
    and (ini_get('mbstring.func_overload') & constant('MB_OVERLOAD_STRING'))
) {
    throw new Exception('String functions overloaded by mbstring');
}
// phpcs:enable


// Convert all global variables to UTF-8.
$_GET    = utf8::clean($_GET);
$_POST   = utf8::clean($_POST);
$_COOKIE = utf8::clean($_COOKIE);
$_SERVER = utf8::clean($_SERVER);

if (PHP_SAPI == 'cli')
{
    // Convert command line arguments
    $_SERVER['argv'] = utf8::clean($_SERVER['argv']);
}

final class utf8 {

    /**
     * Recursively cleans arrays, objects, and strings. Removes ASCII control
     * codes and converts to UTF-8 while silently discarding incompatible
     * UTF-8 characters.
     *
     * @template T extends string|array|object
     * @param T $str Thing to clean
     * @return T
     */
    public static function clean($str)
    {
        if (is_array($str) or is_object($str)) {
            foreach ($str as $key => $val) {
                // Recursion!
                $str[self::clean($key)] = self::clean($val);
            }
        } elseif (is_string($str) and $str !== '') {
            // Remove control characters
            $str = self::stripAsciiCtrl($str);

            if (!self::isAscii($str)) {
                // Disable notices
                $ER = error_reporting(~E_NOTICE);

                // iconv is expensive, so it is only used when needed
                $str = iconv('UTF-8', 'UTF-8//IGNORE', $str);

                // Turn notices back on
                error_reporting($ER);
            }
        }

        return $str;
    }

    /**
     * Tests whether a string contains only 7bit ASCII bytes. This is used to
     * determine when to use native functions or UTF-8 functions.
     *
     * @param   string  $str  string to check
     * @return  bool
     */
    public static function isAscii($str)
    {
        return ! preg_match('/[^\x00-\x7F]/S', $str);
    }

    /**
     * Strips out device control codes in the ASCII range.
     *
     * @param   string  $str  string to clean
     * @return  string
     */
    public static function stripAsciiCtrl($str)
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $str);
    }

    /**
     * Strips out all non-7bit ASCII bytes.
     *
     * @param   string  $str  string to clean
     * @return  string
     */
    public static function stripNonAscii($str)
    {
        return preg_replace('/[^\x00-\x7F]+/S', '', $str);
    }

} // End utf8
