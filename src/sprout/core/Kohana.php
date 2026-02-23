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

use karmabunny\kb\EventInterface;
use karmabunny\kb\Events;
use karmabunny\kb\Uuid;
use Psr\Http\Message\ResponseInterface;
use Sprout\Controllers\BaseController;
use Sprout\Events\DisplayEvent;
use Sprout\Events\NotFoundEvent;
use Sprout\Events\PostControllerConstructorEvent;
use Sprout\Events\PostControllerEvent;
use Sprout\Events\PreControllerEvent;
use Sprout\Events\SendHeadersEvent;
use Sprout\Events\ShutdownEvent;
use Sprout\Exceptions\HttpException;
use Sprout\Helpers\Config;
use Sprout\Helpers\Enc;
use Sprout\Helpers\I18n;
use Sprout\Helpers\Errors;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Router;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Request;
use Sprout\Helpers\Services;
use Sprout\Helpers\Session;
use Sprout\Helpers\SessionStats;

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
final class Kohana {

    // The singleton instance of the controller
    public static $instance;

    // Output buffering level
    public static $buffer_level;

    // Will be set to TRUE when an exception is caught
    public static $has_error = FALSE;

    // Enable or disable the fatal error handler
    public static $enable_fatal_errors = TRUE;

    // The final output that will displayed by Kohana
    public static $output = '';

    // The current user agent
    public static $user_agent;

    // The current locale
    public static $locale;

    /**
     * Sets up the PHP environment. Adds error/exception handling, output
     * buffering, and adds an auto-loading method for loading classes.
     *
     * For security, this function also destroys the $_REQUEST global variable.
     * Using the proper global (GET, POST, COOKIE, etc) is inherently more secure.
     * @see http://www.php.net/globals
     *
     * @return  void
     */
    public static function setup()
    {
        static $run;

        // This function can only be run once
        if ($run === TRUE)
            return;

        // Set the user agent
        self::$user_agent = trim($_SERVER['HTTP_USER_AGENT'] ?? '');

        // Start output buffering
        ob_start(array(__CLASS__, 'outputBuffer'));

        // Save buffering level
        self::$buffer_level = ob_get_level();

        // Send default text/html UTF-8 header
        header('Content-Type: text/html; charset=UTF-8');

        // Check the CLI domain has been set
        if (! Kohana::config('config.cli_domain'))
        {
            throw new Exception('Sprout config parameter "config.cli_domain" has not been set. See the sprout development documentation for more info.');
        }

        $_SERVER['HTTP_HOST'] ??= Kohana::config('config.cli_domain');
        $_SERVER['SERVER_NAME'] ??= $_SERVER['HTTP_HOST'];

        I18n::init();

        // Backwards compat.
        self::$locale = I18n::getLanguage();

        // Enable Kohana 404 pages
        Events::on(Kohana::class, NotFoundEvent::class, [Kohana::class, 'show404']);

        // Enable Kohana output handling
        Events::on(Kohana::class, ShutdownEvent::class, [Kohana::class, 'shutdown']);

        Events::on(Kohana::class, DisplayEvent::class, [Needs::class, 'replacePlaceholders']);

        // Setup is complete, prevent it from being run again
        $run = TRUE;
    }

    /**
     * Run the application.
     *
     * This executes controller instance and runs shutdown events.
     *
     * @return void
     */
    public static function run()
    {
        self::instance();

        $event = new ShutdownEvent();
        Events::trigger(Kohana::class, $event);
    }

    /**
     * Loads the controller and initializes it. Runs the pre_controller,
     * post_controller_constructor, and post_controller events. Triggers
     * a system.404 event when the route cannot be mapped to a controller.
     *
     * @return  object  instance of controller
     */
    public static function & instance()
    {
        if (self::$instance === NULL)
        {
            if (empty(Router::$controller)) {
                $event = new NotFoundEvent();
                Events::trigger(Kohana::class, $event);
                die;
            }

            try {
                // Start validation of the controller
                $class = new ReflectionClass(Router::$controller);
            } catch (ReflectionException $e) {
                // Controller does not exist
                $event = new NotFoundEvent();
                Events::trigger(Kohana::class, $event);
                die;
            }

            if ($class->isAbstract() OR (IN_PRODUCTION AND $class->getConstant('ALLOW_PRODUCTION') == FALSE))
            {
                // Controller is not allowed to run in production
                $event = new NotFoundEvent();
                Events::trigger(Kohana::class, $event);
                die;
            }

            // Initialise any custom non-module code
            if (is_readable(DOCROOT . '/skin/sprout_load.php')) {
                require DOCROOT . '/skin/sprout_load.php';
            }

            // Prevent further service registrations
            Services::lock();

            // Run system.pre_controller
            $event = new PreControllerEvent();
            Events::trigger(Kohana::class, $event);

            // Create a new controller instance
            $controller = $class->newInstance();

            if (!($controller instanceof BaseController)) {
                throw new Exception("Class doesn't extend BaseController: " . get_class($controller));
            }

            // Controller constructor has been executed
            $event = new PostControllerConstructorEvent();
            Events::trigger(Kohana::class, $event);

            $res = $controller->_run(Router::$method, Router::$arguments);

            if ($res instanceof ResponseInterface) {
                Sprout::send($res);
            }

            // Controller method has been executed
            $event = new PostControllerEvent();
            Events::trigger(Kohana::class, $event);
        }

        return self::$instance;
    }


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

        return Config::load($name, $required);
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
     * Kohana output handler. Called during ob_clean, ob_flush, and their variants.
     *
     * @param   string  $output  current output buffer
     * @return  string
     */
    public static function outputBuffer($output)
    {
        // Could be flushing, so send headers first
        if (!Events::hasRun(Kohana::class, SendHeadersEvent::class)) {
            $event = new SendHeadersEvent();
            Events::trigger(Kohana::class, $event);
        }

        self::$output = $output;

        // Set and return the final output
        return self::$output;
    }

    /**
     * Closes all open output buffers, either by flushing or cleaning, and stores the Kohana
     * output buffer for display during shutdown.
     *
     * @param   bool  $flush  disable to clear buffers, rather than flushing
     * @return  void
     */
    public static function closeBuffers($flush = TRUE)
    {
        if (ob_get_level() >= self::$buffer_level)
        {
            // Set the close function
            $close = ($flush === TRUE) ? 'ob_end_flush' : 'Kohana::_obEndClean';

            while (ob_get_level() > self::$buffer_level)
            {
                // Flush or clean the buffer
                $close();
            }

            // Store the Kohana output buffer
            Kohana::_obEndClean();
        }
    }

    /**
     * Triggers the shutdown of Kohana by closing the output buffer + running display events.
     *
     * @return  void
     */
    public static function shutdown()
    {
        // Close output buffers
        self::closeBuffers(TRUE);

        // Run the output event
        $event = new DisplayEvent(['output' => self::$output]);
        Events::trigger(Kohana::class, $event);
        self::$output = $event->output;

        // Render the final output
        self::render(self::$output);
    }


    /**
     * Close the connection to the browser.
     *
     * This sends a close header to the browser and disconnects FPM.
     * Theoretically permitting a script to continue running in the background indefinitely.
     *
     * However, once closed it cannot open again.
     *
     * @return void
     */
    public static function closeConnection()
    {
        if (headers_sent() or PHP_SAPI === 'cli') {
            return;
        }

        Session::writeClose();

        self::closeBuffers(false);

        header('Connection: close');

        ignore_user_abort(true);

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }


    /**
     * Inserts global Kohana variables into the generated output and prints it.
     *
     * @param   string  $output  final output that will displayed
     * @return  void
     */
    public static function render($output)
    {
        if ($level = self::config('core.output_compression') AND ini_get('output_handler') !== 'ob_gzhandler' AND (int) ini_get('zlib.output_compression') === 0)
        {
            if ($level < 1 OR $level > 9)
            {
                // Normalize the level to be an integer between 1 and 9. This
                // step must be done to prevent gzencode from triggering an error
                $level = max(1, min($level, 9));
            }

            if (stripos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'gzip') !== FALSE)
            {
                $compress = 'gzip';
            }
            elseif (stripos($_SERVER['HTTP_ACCEPT_ENCODING'] ?? '', 'deflate') !== FALSE)
            {
                $compress = 'deflate';
            }
        }

        if (isset($compress) AND $level > 0)
        {
            switch ($compress)
            {
                case 'gzip':
                    // Compress output using gzip
                    $output = gzencode($output, $level);
                break;
                case 'deflate':
                    // Compress output using zlib (HTTP deflate)
                    $output = gzdeflate($output, $level);
                break;
            }

            // This header must be sent with compressed content to prevent
            // browser caches from breaking
            header('Vary: Accept-Encoding');

            // Send the content encoding header
            header('Content-Encoding: '.$compress);

            // Sending Content-Length in CGI can result in unexpected behavior
            if (stripos(PHP_SAPI, 'cgi') === FALSE)
            {
                header('Content-Length: '.strlen($output));
            }
        }

        if (!IN_PRODUCTION AND PHP_SAPI !== 'cli') {
            header('x-sprout-tag:' . SPROUT_REQUEST_TAG);
        }

        echo $output;
    }

    /**
     * Displays a 404 page.
     *
     * @param   string|false|EventInterface  $page  URI of page
     * @return  void
     * @throws Kohana_404_Exception
     */
    public static function show404($page = FALSE)
    {
        if ($page instanceof EventInterface) {
            $page = false;
        }

        throw new Kohana_404_Exception($page);
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


    /**
     * Ends the current output buffer with callback in mind
     * PHP doesn't pass the output to the callback defined in ob_start() since 5.4
     *
     * @param callable|null $callback
     * @return bool
     */
    protected static function _obEndClean($callback = NULL)
    {
        // Pre-5.4 ob_end_clean() will pass the buffer to the callback anyways
        if (version_compare(PHP_VERSION, '5.4', '<'))
            return ob_end_clean();

        $output = ob_get_contents();

        if ($callback === NULL)
        {
            $hdlrs = ob_list_handlers();
            $callback = $hdlrs[ob_get_level() - 1];
        }

        return is_callable($callback)
             ? ob_end_clean() AND call_user_func($callback, $output)
             : ob_end_clean();
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
