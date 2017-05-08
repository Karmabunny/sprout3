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

use Kohana;

use Sprout\Helpers\Locales\LocaleInfo;


/**
* Formats data according to local conventions.
* This is an easier-to-use helper version of the LocaleInfo set of classes.
*
* To set the locale, set the config param:
*    sprout.locale
*
* The default is AUS - Australia
**/
class I18n
{
    private static $locale;


    /**
    * Set the locale to the default as per the configuration
    **/
    public static function init()
    {
        $l = Kohana::config('sprout.locale');
        if ($l == '') $l = 'AUS';
        self::$locale = LocaleInfo::get($l);
    }


    /**
    * Set the locale to something other than the default
    * Useful for multi-currency shopping carts, for example.
    *
    * @param string $country The country to set the locale to, e.g. 'AUS'.
    **/
    public static function setLocale($country)
    {
        self::$locale = LocaleInfo::get($country);
    }


    /**
     * Return JavaScript code which initialises the I18n javascript library with the locale parameters
     *
     * @return string HTML <script> tag
     */
    public static function initJavaScript()
    {
        Needs::module('i18n');
        $out = '<script>';
        $out .= 'I18n.setLocale(' . json_encode(self::$locale->getParameters()) . ');';
        $out .= '</script>';
        return $out;
    }


    /**
    * Return a formatted number
    *
    * @param float $number The number to format
    * @param int $precision The number of digits to show after the decimal point
    **/
    public static function number($number, $precision)
    {
        return self::$locale->numberFormat($number, $precision);
    }


    /**
    * Return a formatted currency value
    *
    * @param float $number The number to format
    **/
    public static function money($number, $precision = null)
    {
        return self::$locale->moneyFormat($number, $precision);
    }


    /**
    * Return a formatted date in a short format (e.g. 3/1/2003)
    *
    * @param int $timestamp. A unix timestamp. Defaults to the current time
    **/
    public static function shortdate($timestamp = 0)
    {
        if (! $timestamp) $timestamp = time();
        return self::$locale->shortdate($timestamp);
    }


    /**
    * Return a formatted date in a short format (e.g. Mon 3rd Jan 2003)
    *
    * @param int $timestamp. A unix timestamp. Defaults to the current time
    **/
    public static function longdate($timestamp = 0)
    {
        if (! $timestamp) $timestamp = time();
        return self::$locale->longdate($timestamp);
    }


    /**
    * Return a formatted dtime (e.g. 5:30pm)
    *
    * @param int $timestamp. A unix timestamp. Defaults to the current time
    **/
    public static function time($timestamp = 0)
    {
        if (! $timestamp) $timestamp = time();
        return self::$locale->time($timestamp);
    }


    /**
    * Returns HTML for address fields
    *
    * @param bool $required If the fields should be listed as required or not
    **/
    public static function addressFields($required)
    {
        self::$locale->outputAddressFields('', $required);
    }

}
