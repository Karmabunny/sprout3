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

use Exception;

use Kohana;


/**
 * Means to generate and process CAPTCHAs of various implementations
 */
class Captcha
{
    protected static $class;


    /**
     * Loads the default captcha class from config
     */
    public static function init()
    {
        $class = Kohana::config('sprout.captcha');
        if (!$class) $class = 'default_captcha';
        self::useClass($class);
    }


    /**
     * Sets which library to use for CAPTCHA implementation
     * @param string $class_name e.g. DefaultCaptcha or Recaptcha
     */
    public static function useClass($class_name)
    {
        $class_name = (string) $class_name;

        if (strpos($class_name, '\\') === false) {
            $class_name = 'Sprout\\Helpers\\' . $class_name;
        }

        if (!class_exists($class_name)) throw new Exception('Invalid class');
        self::$class = $class_name;
    }


    /**
    * Shows a captcha field
    * @return void Calls echo directly
    **/
    public static function field()
    {
        if (self::$class == '') self::init();
        $class = self::$class;
        $class::field();
    }

    /**
    * Checks the captcha field against the submitted text
    * @return bool
    **/
    public static function check()
    {
        if (self::$class == '') self::init();
        $class = self::$class;
        return $class::check();
    }

}


