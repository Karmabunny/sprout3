<?php
/**
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 *
 * This class was originally from Kohana 2.3.4
 * Copyright 2007-2008 Kohana Team
 */
namespace Sprout\Helpers;

use Kohana;


/**
 * Language inflection such as pluralisation.
 */
class Inflector
{

    // Cached inflections
    protected static $cache = array();

    // Uncountable and irregular words
    protected static $uncountable;
    protected static $irregular;

    /**
     * Checks if a word is defined as uncountable.
     *
     * @param   string $str Word to check
     * @return  bool
     */
    public static function uncountable($str)
    {
        if (Inflector::$uncountable === NULL)
        {
            // Cache uncountables
            Inflector::$uncountable = Kohana::config('inflector.uncountable');

            // Make uncountables mirroed
            Inflector::$uncountable = array_combine(Inflector::$uncountable, Inflector::$uncountable);
        }

        return isset(Inflector::$uncountable[strtolower($str)]);
    }

    /**
     * Makes a plural word singular.
     *
     * @param   string $str Word to singularize
     * @param   int|null $count Number of things
     * @return  string
     */
    public static function singular($str, $count = NULL)
    {
        // Remove garbage
        $str = strtolower(trim($str));

        // Convert to integer when using a digit string
        $count = $count ? (int) $count : null;

        // Do nothing with a single count
        if ($count !== null and $count !== 1) {
            return $str;
        }

        // Cache key name
        $key = 'singular_'.$str.$count;

        if (isset(Inflector::$cache[$key]))
            return Inflector::$cache[$key];

        if (Inflector::uncountable($str))
            return Inflector::$cache[$key] = $str;

        if (empty(Inflector::$irregular))
        {
            // Cache irregular words
            Inflector::$irregular = Kohana::config('inflector.irregular');
        }

        if ($irregular = array_search($str, Inflector::$irregular))
        {
            $str = $irregular;
        }
        elseif (preg_match('/[sxz]es$/', $str) OR preg_match('/[^aeioudgkprt]hes$/', $str))
        {
            // Remove "es"
            $str = substr($str, 0, -2);
        }
        elseif (preg_match('/[^aeiou]ies$/', $str))
        {
            $str = substr($str, 0, -3).'y';
        }
        elseif (substr($str, -1) === 's' AND substr($str, -2) !== 'ss')
        {
            $str = substr($str, 0, -1);
        }

        return Inflector::$cache[$key] = $str;
    }

    /**
     * Makes a singular word plural.
     *
     * @param   string $str Word to pluralize
     * @param   int|null $count
     * @return  string
     */
    public static function plural($str, $count = NULL)
    {
        // Remove garbage
        $str = strtolower(trim($str));

        // Convert to integer when using a digit string
        $count = $count ? (int) $count : null;

        // Do nothing with singular
        if ($count === 1) {
            return $str;
        }

        // Cache key name
        $key = 'plural_'.$str.$count;

        if (isset(Inflector::$cache[$key]))
            return Inflector::$cache[$key];

        if (Inflector::uncountable($str))
            return Inflector::$cache[$key] = $str;

        if (empty(Inflector::$irregular))
        {
            // Cache irregular words
            Inflector::$irregular = Kohana::config('inflector.irregular');
        }

        if (isset(Inflector::$irregular[$str]))
        {
            $str = Inflector::$irregular[$str];
        }
        elseif (preg_match('/[sxz]$/', $str) OR preg_match('/[^aeioudgkprt]h$/', $str))
        {
            $str .= 'es';
        }
        elseif (preg_match('/[^aeiou]y$/', $str))
        {
            // Change "y" to "ies"
            $str = substr_replace($str, 'ies', -1);
        }
        else
        {
            $str .= 's';
        }

        // Set the cache and return
        return Inflector::$cache[$key] = $str;
    }


    /**
     * Pluralises a number and word combination, e.g. [1, 'pig'] => '1 pig'; [0, 'pig'] => '0 pigs'
     * @param int $num The number in question
     * @param string $word The word to pluralise if $num is not 1
     */
    public static function numPlural($num, $word)
    {
        if ($num == 1) return "1 {$word}";
        return $num . ' ' . self::plural($word);
    }



    /**
     * Makes a phrase camel case.
     *
     * @param   string $str Phrase to camelize
     * @return  string
     */
    public static function camelize($str)
    {
        $str = 'x'.strtolower(trim($str));
        $str = ucwords(preg_replace('/[\s_]+/', ' ', $str));

        return substr(str_replace(' ', '', $str), 1);
    }

    /**
     * Makes a phrase underscored instead of spaced.
     *
     * @param   string $str Phrase to underscore
     * @return  string
     */
    public static function underscore($str)
    {
        return preg_replace('/\s+/', '_', trim($str));
    }


    /**
    * Convert a CamelCaps or camelCase string into a sentence case string
    *
    * @param string $str

    * @return string
    **/
    public static function decamelize($str)
    {
        $str = preg_replace_callback(
            '/[A-Z0-9]/',
            function($matches) {
                return ' ' . strtolower($matches[0]);
            },
            $str
        );

        $str = ltrim($str, ' ');

        return $str;
    }


    /**
     * Makes an underscored or dashed phrase human-reable.
     *
     * @param string $str phrase to make human-reable
     *
     * @return  string
     */
    public static function humanize($str)
    {
        $str = Inflector::decamelize($str);
        return preg_replace('/[_-]+/', ' ', trim($str));
    }

    /**
     * Makes an underscored or dashed or CamelCase phrase human-reable.
     *
     * @param string $str phrase to make human-reable
     *
     * @return string
     */
    public static function title($str)
    {
        $str = Inflector::decamelize($str);
        $str = Inflector::humanize($str);

        return ucwords(trim($str));
    }

} // End inflector
