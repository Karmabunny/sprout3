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


use Sprout\Exceptions\HttpException;
use Sprout\Helpers\Config;
use Sprout\Helpers\Enc;
use Sprout\Helpers\I18n;
use Sprout\Helpers\Errors;
use Sprout\Helpers\Router;
use Sprout\Helpers\Request;
use Sprout\App;


/**
 * Provides Kohana-specific helper functions. This is where the magic happens!
 *
 * $Id: Kohana.php 4372 2009-05-28 17:00:34Z ixmatus $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007-2008 Kohana Team
 * @license    http://kohanaphp.com/license.html
 */
final class Kohana extends App {

    // The singleton instance of the controller
    public static $instance;

    // The current user agent
    public static $user_agent;

    // The current locale
    public static $locale;


    /**
     * Get a config item or group.
     *
     * @param   string   $key       item name
     * @param   bool  $slash     force a forward slash (/) at the end of the item
     * @param   bool  $required  is the item required?
     * @return  mixed
     * @deprecated use Config::get()
     */
    public static function config($key, $slash = FALSE, $required = TRUE)
    {
        if (strpos($key, 'core.') === 0) {
            $key = 'config.' . substr($key, 5);
        }

        $value = Config::get($key, false);

        // Force the value to end with "/"
        if ($slash === TRUE AND is_string($value) AND $value !== '')
        {
            $value = rtrim($value, '/').'/';
        }

        return $value;
    }

    /**
     * Sets a configuration item, if allowed.
     *
     * @param   string   $key    config key string
     * @param   mixed    $value  config value
     * @return  bool
     * @deprecated use Config::set()
     */
    public static function configSet($key, $value)
    {
        Config::set($key, $value);
        return TRUE;
    }


    /**
     * Load a kohana config file.
     *
     * This assumes that the file will _declare_ an array called named
     * 'config' - or defined by the `$name` parameter.
     *
     * @param string $file absolute path to file
     * @param string $name variable name
     * @return array|null
     * @deprecated use Config::include() instead
     */
    public static function configInclude(string $file, string $name = 'config')
    {
        return Config::include($file, $name);
    }


    /**
     * Load a config file.
     *
     * @param   string   $name      config filename, without extension
     * @param   bool  $required  is the file required?
     * @return  array
     * @deprecated use Config::load() instead
     */
    public static function configLoad($name, $required = TRUE)
    {
        if ($name === 'core') {
            $name = 'config';
        }

        return Config::load($name);
    }


    /**
     * Deprecated.
     *
     * This function has been removed, and it's signature has
     * been left for compatibilty only.
     *
     * @deprecated
     */
    public static function log($type, $message)
    {
    }


    /**
     * Log exceptions in the database
     *
     * @param \Throwable $exception Exception or error to log
     * @param bool $caught
     * @return int Record ID
     * @deprecated use Errors::logException()
     */
    public static function logException($exception, bool $caught = true)
    {
        return Errors::logException($exception, $caught);
    }


    /**
     * Fetch an i18n language item.
     *
     * @param   string  $key   language key to fetch
     * @param   mixed   $args  additional information to insert into the line
     * @return  string|array  i18n language string, or the requested key if the i18n item is not found
     * @deprecated use I18n::lang()
     */
    public static function lang($key, ...$args)
    {
        return I18n::lang($key, ...$args);
    }


    /**
     * Retrieves current user agent information:
     * keys:  browser, version, platform, mobile, robot, referrer, languages, charsets
     * tests: is_browser, is_mobile, is_robot, accept_lang, accept_charset
     *
     * @param   string   $key key or test name
     * @param   string   $compare used with "accept" tests: userAgent(accept_lang, en)
     * @return  array|string|bool|null
     *   - array: languages and charsets
     *   - string: all other keys
     *   - boolean: all tests
     *   - null: invalid key or test
     */
    public static function userAgent($key = 'agent', $compare = NULL)
    {
        return Request::userAgent($key, $compare);
    }

    /**
     * Quick debugging of any variable. Any number of parameters can be set.
     *
     * @param mixed $params
     * @return  string
     */
    public static function debug(...$params)
    {
        if (empty($params)) {
            return '';
        }

        $output = array();

        foreach ($params as $var)
        {
            $output[] = '<pre>('.gettype($var).') '.Enc::html(print_r($var, TRUE)).'</pre>';
        }

        return implode("\n", $output);
    }


    /**
     * Displays nice backtrace information.
     * @see http://php.net/debug_backtrace
     *
     * @param   array   $trace  backtrace generated by an exception or debug_backtrace
     * @return  string
     * @deprecated use Errors::backtrace()
     */
    public static function backtrace($trace)
    {
        return Errors::backtrace($trace);
    }

} // End Kohana

/**
 * Creates a generic i18n exception.
 *
 * Soft deprecation: use HttpException instead.
 */
class Kohana_Exception extends HttpException
{

    // Header
    protected $header = FALSE;

    // Error code
    protected $code = E_KOHANA;

    // Translation key + args
    protected $id;

    /**
     * Set exception message.
     *
     * @param  string $error  i18n language key for the message
     * @param  mixed  $args   addition line parameters
     */
    public function __construct($error, ...$args)
    {
        // Fetch the error message
        $message = Kohana::lang($error, $args);

        if ($message === $error OR empty($message))
        {
            // Unable to locate the message for the error
            $message = 'Unknown Exception: '.$error;
        }

        // Sets $this->message the proper way
        parent::__construct(500, $message);
        $this->id = array_merge([$error], $args);
    }

    /**
     * Magic method for converting an object to a string.
     *
     * @return  string  i18n message
     */
    public function __toString()
    {
        return (string) $this->message;
    }

    /**
     * Fetch the translation args.
     *
     * @return  string
     */
    public function getTranslation()
    {
        return $this->id;
    }

    /**
     * Fetch the template name.
     *
     * @return  string
     */
    public function getTemplate()
    {
        return '';
    }
} // End Kohana Exception

/**
 * Creates a custom exception.
 *
 * @deprecated literally no-one uses this.
 */
class Kohana_User_Exception extends Kohana_Exception
{

    /**
     * Set exception title and message.
     *
     * @param   string  $title exception title string
     * @param   string  $message exception message string
     */
    public function __construct($title, $message)
    {
        Exception::__construct($message);

        $this->code = $title;
    }

} // End Kohana PHP Exception

/**
 * Creates a Page Not Found exception.
 *
 * Soft deprecation: use HttpNotFoundException instead.
 */
class Kohana_404_Exception extends Kohana_Exception
{

    protected $code = E_PAGE_NOT_FOUND;

    /**
     * Set internal properties.
     *
     * @param  string|false  $page  URL of page
     */
    public function __construct($page = FALSE)
    {
        if ($page === FALSE)
        {
            // Construct the page URI using Router properties
            $page = Router::$current_uri.Router::$url_suffix.Router::$query_string;
        }

        HttpException::__construct(404, Kohana::lang('core.page_not_found', $page));
    }

} // End Kohana 404 Exception
