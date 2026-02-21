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
    /**
     * The current locale object.
     *
     * @var LocaleInfo
     */
    private static $locale;


    /**
     * The language code of the current locale.
     *
     * @var string
     */
    private static $language = 'en';


    /**
    * Set the locale to the default as per the configuration
    **/
    public static function init()
    {
        $locales = Kohana::config('locale.language');

        // Make first locale UTF-8.
        if (!str_ends_with($locales[0], '.UTF-8')) {
            $locales[0] .= '.UTF-8';
        }

        [$fallback] = explode('.', $locales[0], 2);
        self::$language = setlocale(LC_ALL, $locales) ?: $fallback;

        $l = Kohana::config('sprout.locale', false, false);
        if ($l == '') $l = Kohana::config('config.default_country_code');
        self::$locale = LocaleInfo::get($l);
    }


    /**
     * Get the language code of the current locale.
     *
     * @return string
     */
    public static function getLanguage(): string
    {
        return self::$language;
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
        Needs::fileGroup('i18n');
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
     * Return currency ISO code
     *
     * @return string Eg. AUD
     */
    public static function currencyISO()
    {
        return self::$locale->getCurrencyISO();
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


    /**
     * Translate a string.
     *
     * @param string $key
     * @param array $args
     * @return string
     */
    public static function t(string $key, array $args = []): string
    {
        // TODO use php-intl for proper ICU translations.
        return self::lang($key, ...$args);
    }


    /**
     * Fetch an i18n language item.
     *
     * @param   string  $key   language key to fetch
     * @param   mixed   $args  additional information to insert into the line
     * @return  string|array  i18n language string, or the requested key if the i18n item is not found
     */
    public static function lang(string $key, ...$args)
    {
        static $cache = [];

        // Extract the main group from the key
        [$group] = explode('.', $key, 2);

        $locale = self::$language;

        if (!isset($cache[$locale][$group])) {
            $path = APPPATH . "i18n/{$locale}/{$group}.php";
            $messages = Kohana::configInclude($path, 'lang');

            if (!is_array($messages)) {
                $messages = [];
            }

            $cache[$locale][$group] = $messages;
        }

        // Get the line from cache
        $line = Kohana::keyString($cache[$locale], $key);

        // Return the key string as fallback
        if ($line === NULL) {
            return $key;
        }

        // Add the arguments into the line
        if (is_string($line) AND !empty($args)) {
            $line = vsprintf($line, is_array($args[0]) ? $args[0] : $args);
        }

        return $line;
    }
}
