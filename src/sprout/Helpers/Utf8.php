<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
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

use Exception;
use HTMLPurifier_Encoder;

/**
 * UTF-8 helper class.
 */
class Utf8
{

    /**
     * Normalizes global input variables to the UTF-8 charset.
     *
     * - GET
     * - POST
     * - COOKIE
     * - SERVER
     *
     * @return void
     * @throws Exception
     */
    public static function setup()
    {
        static $setup = false;
        if ($setup) return;

        if (!preg_match('/^.$/u', 'ñ')) {
            throw new Exception('PCRE is missing UTF-8 support');
        }

        // Convert all global variables to UTF-8.
        $_GET    = self::clean($_GET);
        $_POST   = self::clean($_POST);
        $_COOKIE = self::clean($_COOKIE);
        $_SERVER = self::clean($_SERVER);

        if (PHP_SAPI == 'cli') {
            // Convert command line arguments
            $_SERVER['argv'] = Utf8::clean($_SERVER['argv']);
        }

        $setup = true;
    }


    /**
     * Recursively cleans arrays, objects, and strings. Removes ASCII control
     * codes and converts to UTF-8 while silently discarding incompatible
     * UTF-8 characters.
     *
     * @template T extends string|array|object
     * @param T $value Thing to clean
     * @return T
     */
    public static function clean($value)
    {
        if (is_array($value) or is_object($value)) {
            foreach ($value as $key => $val) {
                // Recursion!
                $value[self::clean($key)] = self::clean($val);
            }
        } elseif (is_string($value) and $value !== '') {
            $value = HTMLPurifier_Encoder::cleanUTF8($value);
        }

        return $value;
    }


     /**
     * Tests whether a string contains only 7bit ASCII bytes.
     *
     * @param string $str string to check
     * @return bool
     */
    public static function isAscii($str)
    {
        return ! preg_match('/[^\x00-\x7F]/S', $str);
    }


    /**
     * Strips out device control codes in the ASCII range.
     *
     * @param string $str string to clean
     * @return string
     */
    public static function stripAsciiCtrl($str)
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $str);
    }


    /**
     * Strips out all non-7bit ASCII bytes.
     *
     * @param string $str string to clean
     * @return string
     */
    public static function stripNonAscii($str)
    {
        return preg_replace('/[^\x00-\x7F]+/S', '', $str);
    }
}
