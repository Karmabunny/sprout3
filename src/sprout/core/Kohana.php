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
use karmabunny\pdb\Exceptions\QueryException;
use karmabunny\pdb\Exceptions\RowMissingException;
use Psr\Http\Message\ResponseInterface;
use Sprout\Controllers\BaseController;
use Sprout\Events\DisplayEvent;
use Sprout\Events\ErrorEvent;
use Sprout\Events\NotFoundEvent;
use Sprout\Events\PostControllerConstructorEvent;
use Sprout\Events\PostControllerEvent;
use Sprout\Events\PreControllerEvent;
use Sprout\Events\SendHeadersEvent;
use Sprout\Events\ShutdownEvent;
use Sprout\Exceptions\HttpExceptionInterface;
use Sprout\Helpers\Enc;
use Sprout\Helpers\BaseView;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Modules;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Router;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\SubsiteSelector;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\Request;
use Sprout\Helpers\Security;
use Sprout\Helpers\Services;
use Sprout\Helpers\Session;
use Sprout\Helpers\SessionStats;
use Sprout\Helpers\TwigView;
use Twig\Error\Error as TwigError;
use Twig\Error\RuntimeError as TwigRuntimeError;

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
    private static $buffer_level;

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

    // Configuration
    private static $configuration;

    // Include paths
    private static $include_paths;

    // Cache lifetime
    private static $cache_lifetime;

    // Internal caches and write status
    private static $internal_cache = array();
    private static $write_cache;
    private static $internal_cache_path;

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

        // Define Kohana error constant
        define('E_KOHANA', 42);

        // Define 404 error constant
        define('E_PAGE_NOT_FOUND', 43);

        // Define database error constant
        define('E_DATABASE_ERROR', 44);

        // Define application start time.
        define('SPROUT_REQUEST_TIME', microtime(TRUE));

        // Set the directory to be used for the internal cache
        self::$internal_cache_path = STORAGE_PATH . 'cache/';

        // How long to save the internal cache, in seconds
        self::$cache_lifetime = 60;

        // Load cached configuration and file paths
        // First check defined() so we don't break migrations.
        if (!defined('BootstrapConfig::ENABLE_KOHANA_CACHE') or constant('BootstrapConfig::ENABLE_KOHANA_CACHE')) {
            self::$internal_cache['configuration'] = self::cache('configuration');
            self::$internal_cache['find_file_paths'] = self::cache('find_file_paths');

            // Enable cache saving
            Events::on(Kohana::class, ShutdownEvent::class, [Kohana::class, 'internalCacheSave']);
        }
        else {
            self::disableCache();
        }

        // Set the user agent
        self::$user_agent = trim($_SERVER['HTTP_USER_AGENT'] ?? '');

        // Start output buffering
        ob_start(array(__CLASS__, 'outputBuffer'));

        // Save buffering level
        self::$buffer_level = ob_get_level();

        // Define a global request tag.
        define('SPROUT_REQUEST_TAG', Uuid::uuid4());

        self::$enable_fatal_errors = (
            defined('BootstrapConfig::ENABLE_FATAL_ERRORS')
            and constant('BootstrapConfig::ENABLE_FATAL_ERRORS')
        );

        // Auto-convert errors into exceptions
        set_error_handler(array('Kohana', 'errorHandler'));

        // Set exception handler
        set_exception_handler(array('Kohana', 'exceptionHandler'));

        if (self::$enable_fatal_errors) {
            // Catch fatal errors (compiler, memory, etc)
            register_shutdown_function(array('Kohana', 'handleFatalErrors'));

            // Now switch off native errors because we've got our own now.
            ini_set('display_errors', 0);
        }

        // Send default text/html UTF-8 header
        header('Content-Type: text/html; charset=UTF-8');

        // Check the CLI domain has been set
        if (! Kohana::config('config.cli_domain'))
        {
            throw new Exception('Sprout config parameter "config.cli_domain" has not been set. See the sprout development documentation for more info.');
        }

        // Set HTTP_HOST for CLI scripts
        if (! isset($_SERVER['HTTP_HOST']))
        {
            if (!empty($_SERVER['PHP_S_HTTP_HOST']))
            {
                $_SERVER['HTTP_HOST'] = $_SERVER['PHP_S_HTTP_HOST'];
            }
            else
            {
                $_SERVER['HTTP_HOST'] = Kohana::config('config.cli_domain');
            }
        }

        // Set SERVER_NAME if it's not set
        if (! isset($_SERVER['SERVER_NAME']))
        {
            $_SERVER['SERVER_NAME'] = $_SERVER['HTTP_HOST'];
        }

        // Load locales
        $locales = self::config('locale.language');

        // Make first locale UTF-8
        $locales[0] .= '.UTF-8';

        // Set locale information
        self::$locale = setlocale(LC_ALL, $locales);

        // Enable Kohana 404 pages
        Events::on(Kohana::class, NotFoundEvent::class, [Kohana::class, 'show404']);

        // Enable Kohana output handling
        Events::on(Kohana::class, ShutdownEvent::class, [Kohana::class, 'shutdown']);

        Events::on(Kohana::class, DisplayEvent::class, [Needs::class, 'replacePlaceholders']);
        Events::on(Kohana::class, DisplayEvent::class, [SessionStats::class, 'trackPageView']);

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

            // Initialise Sprout modules, if required
            $modules = Modules::getModules();
            foreach ($modules as $mod) {
                if ($mod->isLoaded()) continue;
                $mod->loadSprout();
            }

            // Prevent further service registrations
            Services::lock();

            // Initialise any custom non-module code
            if (is_readable(DOCROOT . '/skin/sprout_load.php')) {
                require DOCROOT . '/skin/sprout_load.php';
            }

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
     * Get all include paths. APPPATH is the first path, followed by module
     * paths in the order they are configured.
     *
     * @param   boolean  $process  re-process the include paths
     * @return  array
     */
    public static function includePaths($process = FALSE)
    {
        if ($process === TRUE)
        {
            self::$include_paths = array();

            // Sprout modules first
            foreach (Modules::getModules() as $module)
            {
                if ($path = str_replace('\\', '/', $module->getPath()))
                {
                    // Add a valid path
                    self::$include_paths[] = $path;
                }
            }

            // Add Sprout core next
            self::$include_paths[] = APPPATH;
        }

        return self::$include_paths;
    }

    /**
     * Get a config item or group.
     *
     * @param   string   $key       item name
     * @param   boolean  $slash     force a forward slash (/) at the end of the item
     * @param   boolean  $required  is the item required?
     * @return  mixed
     */
    public static function config($key, $slash = FALSE, $required = TRUE)
    {
        if (self::$configuration === NULL)
        {
            // Load core configuration
            self::$configuration = array();
            self::$configuration['core'] = self::configLoad('core');

            // Re-parse the include paths
            self::includePaths(TRUE);
        }

        // Get the group name from the key
        $group = explode('.', $key, 2);
        $group = $group[0];

        $configuration = self::$configuration;
        $sub_config = self::$configuration[$group] ?? null;

        if ($sub_config === null) {
            // Load the configuration group
            $sub_config = self::configLoad($group, $required);
            $configuration[$group] = $sub_config;

            // Store it if we're happy about the subsites.
            if (
                $group !== 'sprout'
                or !empty(SubsiteSelector::$subsite_code)
            ) {
                self::$configuration[$group] = $sub_config;
            }
        }

        // Get the value of the key string
        $value = self::keyString($configuration, $key);

        if ($slash === TRUE AND is_string($value) AND $value !== '')
        {
            // Force the value to end with "/"
            $value = rtrim($value, '/').'/';
        }

        return $value;
    }

    /**
     * Sets a configuration item, if allowed.
     *
     * @param   string   $key    config key string
     * @param   string   $value  config value
     * @return  boolean
     */
    public static function configSet($key, $value)
    {
        // Do this to make sure that the config array is already loaded
        self::config($key);

        if (substr($key, 0, 7) === 'routes.')
        {
            // Routes cannot contain sub keys due to possible dots in regex
            $keys = explode('.', $key, 2);
        }
        else
        {
            // Convert dot-noted key string to an array
            $keys = explode('.', $key);
        }

        // Used for recursion
        $conf =& self::$configuration;
        $last = count($keys) - 1;

        foreach ($keys as $i => $k)
        {
            if ($i === $last)
            {
                $conf[$k] = $value;
            }
            else
            {
                $conf =& $conf[$k];
            }
        }

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
     */
    public static function configInclude(string $file, string $name = 'config')
    {
        static $load;

        if (!$load) {
            $load = function($__file, $__name) {
                include $__file;

                if (isset($$__name) and is_array($$__name)) {
                    return $$__name;
                }

                return null;
            };
        }

        // TODO should we throw if the file doesn't exist?

        return $load($file, $name);
    }


    /**
     * Load a config file.
     *
     * @param   string   $name      config filename, without extension
     * @param   boolean  $required  is the file required?
     * @return  array
     */
    public static function configLoad($name, $required = TRUE)
    {
        if ($name === 'core')
        {
            // Load the application configuration file
            $config = self::configInclude(APPPATH . 'config/config.php', 'config');

            if ( ! isset($config['site_domain']))
            {
                // Invalid config file
                die('Your Kohana application configuration file is not valid.');
            }

            return $config;
        }

        $is_sprout = $name === 'sprout';

        if (
            !$is_sprout
            and self::$cache_lifetime > 0
            and isset(self::$internal_cache['configuration'][$name])
        ) {
            return self::$internal_cache['configuration'][$name];
        }

        // Load matching configs
        $configuration = array();

        if ($files = self::findFile('config', $name, $required))
        {
            foreach ($files as $file)
            {
                $config = self::configInclude($file, 'config');

                if (isset($config))
                {
                    // Merge in configuration
                    $configuration = array_merge($configuration, $config);
                }
            }
        }

        if (!$is_sprout) {
            // Cache has changed
            if ( ! isset(self::$write_cache['configuration'])) {
                self::$write_cache['configuration'] = TRUE;
            }

            self::$internal_cache['configuration'][$name] = $configuration;
        }

        return $configuration;
    }

    /**
     * Clears a config group from the cached configuration.
     *
     * @param   string  $group  config group
     * @return  void
     */
    public static function configClear($group)
    {
        // Remove the group from config
        unset(self::$configuration[$group], self::$internal_cache['configuration'][$group]);

        if ( ! isset(self::$write_cache['configuration']))
        {
            // Cache has changed
            self::$write_cache['configuration'] = TRUE;
        }
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
     * Disable the find-files and configuration caches
     * This may be required if the caching is causing problems
     *
     * @return void
     */
    public static function disableCache()
    {
        self::$cache_lifetime = 0;
        self::$internal_cache = [];
    }

    /**
     * Load data from a simple cache file. This should only be used internally,
     * and is NOT a replacement for the Cache library.
     *
     * @param   string   $name  unique name of cache
     * @return  mixed
     */
    public static function cache($name)
    {
        if (self::$cache_lifetime > 0)
        {
            $path = self::$internal_cache_path.'kohana_'.$name;

            if (is_file($path))
            {
                // Check the file modification time
                if ((time() - filemtime($path)) < self::$cache_lifetime)
                {
                    return json_decode(file_get_contents($path), true);
                }
                else
                {
                    // Cache is invalid, delete it
                    unlink($path);
                }
            }
        }

        // No cache found
        return NULL;
    }

    /**
     * Save data to a simple cache file. This should only be used internally, and
     * is NOT a replacement for the Cache library.
     *
     * @param   string   $name  cache name
     * @param   mixed    $data  data to cache
     * @return  boolean
     */
    public static function cacheSave($name, $data)
    {
        $path = self::$internal_cache_path.'kohana_'.$name;

        if ($data === NULL)
        {
            // Delete cache
            return (is_file($path) and unlink($path));
        }
        else
        {
            return (bool) @file_put_contents($path, json_encode($data));
        }
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
     * @param   boolean  $flush  disable to clear buffers, rather than flushing
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

        if (!IN_PRODUCTION) {
            header('x-sprout-tag:' . SPROUT_REQUEST_TAG);
        }

        echo $output;
    }

    /**
     * Displays a 404 page.
     *
     * @param   string|false  $page  URI of page
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
     * Converts PHP errors into {@see ErrorException}s
     *
     * @param int $errno Error code
     * @param string $errmsg Error message
     * @param string $file
     * @param int $line
     * @return boolean
     * @throws ErrorException
     */
    public static function errorHandler($errno, $errmsg, $file, $line)
    {
        // Ignore statments prepended by @
        if ((error_reporting() & $errno) === 0) return false;

        if (self::$enable_fatal_errors) {
            error_clear_last();
        }

        throw new ErrorException($errmsg, 0, $errno, $file, $line);
    }


    /**
     * Convert fatal errors to error exceptions.
     *
     * This is much like the error->exception helper above but fatal errors
     * are not reported by the error handler. Instead they are caught during
     * the shutdown event.
     *
     * Enable/disable this with the `$enable_fatal_errors` static prop.
     *
     * @return void
     * @throws ErrorException
     */
    public static function handleFatalErrors()
    {
        if (!self::$enable_fatal_errors) return;

        $error = error_get_last();

        if (!$error) {
            return;
        }

        // Only report those we've enabled.
        if ($error['type'] & error_reporting() !== 0) {
            return;
        }

        // Only report 'fatal' types.
        // Anything else can/should be caught by the regular error/exception handlers.
        if (!in_array($error['type'], [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_CORE_WARNING,
            E_COMPILE_ERROR,
            E_COMPILE_WARNING,
        ])) {
            return;
        }

        $exception = new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        $handler = set_exception_handler(null);

        if ($handler) {
            $handler($exception);
        }
        else {
            throw $exception;
        }
    }


    /**
     * Log exceptions in the database
     *
     * @param \Throwable $exception Exception or error to log
     * @param bool $caught
     * @return int Record ID
     */
    public static function logException($exception, bool $caught = true)
    {
        static $insert; // PDOStatement
        static $delete; // PDOStatement

        $secrets = Security::getSecretSanitizer();
        $conn = Pdb::getConnection();

        if (!$insert) {
            // This table is MyISAM so this shouldn't affect transactions
            $table = Pdb::prefix() . 'exception_log';

            $insert_q = "INSERT INTO {$table}
                (date_generated, class_name, message,
                exception_object, exception_trace, server, get_data, session,
                caught, type, ip_address, session_id)
                VALUES
                (:date, :class, :message,
                :exception, :trace, :server, :get, :session,
                :caught, :type, :ip_address, :session_id)";
            $insert = $conn->prepare($insert_q);

            $delete_q = "DELETE FROM {$table} WHERE date_generated < DATE_SUB(?, INTERVAL 10 DAY)";
            $delete = $conn->prepare($delete_q);
        }

        // Extract private attributes from Exception which serialize() would provide
        $reflect = new ReflectionClass($exception);
        $ex_data = [];
        $ignore_props = ['message', 'trace', 'string', 'previous'];
        $props = $reflect->getProperties();
        foreach ($props as $prop) {
            $prop_name = $prop->name;
            if (in_array($prop_name, $ignore_props)) continue;

            $prop->setAccessible(true);
            $ex_data[$prop_name] = $prop->getValue($exception);
        }

        $insert->execute([
            'date' => Pdb::now(),
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'exception' => json_encode($secrets->mask($ex_data)),
            'trace' => json_encode($exception->getTraceAsString()),
            'server' => json_encode($secrets->mask($_SERVER)),
            'get' => json_encode($secrets->mask($_GET)),
            'session' => json_encode($secrets->mask($_SESSION)),
            'caught' => (int) $caught,
            'type' => 'php',
            'ip_address' => bin2hex(inet_pton(Request::userIp())),
            'session_id' => Session::id(),
        ]);

        $log_id = $conn->lastInsertId();
        $insert->closeCursor();

        $delete->execute([Pdb::now()]);
        $delete->closeCursor();

        return (int) $log_id;
    }


    /**
     * Log exceptions to a remote server.
     *
     * @param \Throwable $exception
     * @param int $log_id
     * @param null|string $category
     * @return bool
     */
    public static function logRemoteException($exception, $log_id = 0, $category = null)
    {
        $trace = Services::getTrace();

        if ($trace) {
            return $trace::logException($exception, [
                'log_id' => $log_id,
                'category' => $category,
            ]);
        }

        return true;
    }


    /**
     * Exception handler.
     *
     * @param   object  $exception exception object
     * @return  void
     */
    public static function exceptionHandler($exception)
    {
        // If either of these are empty then we can't do config loading and the
        // exception handler will just throw more exceptions.
        if (self::$configuration === null or self::$include_paths === null) {
            if ($exception instanceof \Exception) {
                die($exception->getMessage());
            }
            else {
                die('Fatal Kohana error.');
            }
        }

        if (!$exception instanceof \Exception and !$exception instanceof \Throwable) {
            throw new Exception('PHP7 - Exception handler was invoked with an invalid exception object type: ' . get_class($exception));
        }

        $event = new ErrorEvent(['error' => $exception]);
        Events::trigger(Kohana::class, $event);

        try {
            $log_id = self::logException($exception, false);
        } catch (Exception $junk) {
            $log_id = 0;
        }

        try {
            self::logRemoteException($exception, $log_id);
        } catch (Throwable $junk) {}

        try
        {
            // This is useful for hooks to determine if a page has an error
            self::$has_error = TRUE;

            // Our twig traces can get quite messy with all the compiled
            // component bits so here we're cleaning up traces.
            $clean_twig = ($exception instanceof TwigError);

            // Unwrap twig runtime errors. When a 'previous' exception is
            // attached, this is wrapping the true exception. This works well
            // in tandem with the trace cleaning above.
            if (
                ($exception instanceof TwigRuntimeError)
                and ($previous = $exception->getPrevious())
            ) {
                $exception = $previous;
            }

            $code     = $exception->getCode();
            $type     = get_class($exception);
            $message  = $exception->getMessage();
            $file     = $exception->getFile();
            $line     = $exception->getLine();

            if (is_numeric($code))
            {
                $codes = self::lang('errors');

                if ( ! empty($codes[$code]))
                {
                    list($level, $error, $description) = $codes[$code];
                }
                else
                {
                    $level = 1;
                    $error = get_class($exception);
                    $description = '';
                }
            }
            else
            {
                // Custom error message, this will never be logged
                $level = 5;
                $error = $code;
                $description = '';
            }

            // For PHP errors, look up name and description from the i18n system
            if ($exception instanceof ErrorException) {
                $severify_info = self::lang('errors.' . $exception->getSeverity());
                if (is_array($severify_info)) {
                    $error = $severify_info[1];
                    $description = $severify_info[2];
                }
            }

            // Remove the DOCROOT from the path, as a security precaution
            $file = str_replace('\\', '/', realpath($file));
            if (IN_PRODUCTION) {
                $file = preg_replace('|^'.preg_quote(DOCROOT).'|', '', $file);
            }

            if (method_exists($exception, 'sendHeaders') AND ! headers_sent())
            {
                // Send the headers if they have not already been sent
                $exception->sendHeaders();
            }

            // Scrub details for database errors on production sites.
            if (IN_PRODUCTION and $exception instanceof QueryException) {
                $file = $message = '';
                $line = 0;
                $description = 'A database error occurred while performing the requested procedure.';
            }

            // Close all output buffers except for Kohana
            while (ob_get_level() > self::$buffer_level)
            {
                ob_end_clean();
            }

            // 404 errors are a special case - we load content from the database and put into a nice skin
            if ($exception instanceof Kohana_404_Exception
                or
                ($exception instanceof RowMissingException and IN_PRODUCTION)
                or
                ($exception instanceof HttpExceptionInterface and $exception->getStatusCode() == 404)
            ) {
                header("HTTP/1.0 404 File Not Found");

                if ($exception instanceof RowMissingException) {
                    $message = 'One of the database records for the page you requested could not be found.';
                }

                if (PHP_SAPI == 'cli') {
                    echo $message, PHP_EOL;
                } else {
                    $page = new PhpView('sprout/404_error');
                    $page->message = $message;
                    $page = $page->render();

                    if (BaseView::exists('skin/404')) {
                        $view = BaseView::create('skin/404');
                    } else {
                        $view = BaseView::create('skin/inner');
                    }

                    $view->page_title = '404 File Not Found';
                    $view->main_content = $page;
                    $view->controller = '404-error';
                    echo $view->render();
                }
            }
            else
            {
                if (PHP_SAPI != 'cli') {
                    $status = 500;

                    if ($exception instanceof HttpExceptionInterface) {
                        $status = $exception->getStatusCode();
                    }

                    header("HTTP/1.0 {$status} Internal Server Error");
                }

                if ( ! IN_PRODUCTION )
                {
                    $trace = $exception->getTrace();
                    $trace = Sprout::simpleBacktrace($trace);
                    $trace = TwigView::processBacktrace($trace, $clean_twig);
                    $trace = self::backtrace($trace);
                }

                // Decode the twig frame, if available.
                if (
                    !empty($file)
                    and !empty($line = (int) $line)
                    and ($twig_frame = TwigView::decodeErrorFrame($file, $line))
                ) {
                    [$file, $line] = $twig_frame;
                }

                // Load the error
                if (PHP_SAPI == 'cli') {
                    require APPPATH . 'views/system/error_cli.php';
                } elseif (!IN_PRODUCTION) {
                    require APPPATH . 'views/system/error_development.php';
                } else {
                    // No skin defined yet? Use the default one
                    if (! SubsiteSelector::$subsite_code) {
                        SubsiteSelector::$subsite_code = 'default';
                    }

                    // Use the skin template or fall back to a sensible default
                    $file = DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/exception.php';
                    if (! file_exists($file)) {
                        $file = APPPATH . 'views/system/error_production.php';
                    }

                    require $file;
                }
            }

            // Run the shutdown even to ensure a clean exit
            if (!Events::hasRun(Kohana::class, ShutdownEvent::class)) {
                $event = new ShutdownEvent();
                Events::trigger(Kohana::class, $event);
            }

            // Turn off error reporting
            error_reporting(0);
            exit;
        }
        catch (Exception $e)
        {
            if (IN_PRODUCTION) {
                die('Fatal Error');
            } else {
                echo "<pre>";
                echo "<b>Failed to handle ", get_class($e), "</b> in ";
                echo $e->getFile(), ' on line ', $e->getLine(), ":\n";
                echo $e->getMessage(), "\n\n";

                echo "<b>Route:</b> ", Router::$controller, '::';
                echo Router::$method;
                if (!empty(Router::$arguments)) {
                    echo '(', implode(', ', Router::$arguments), ')';
                }
                echo "\n\n";

                echo "<b>Trace:</b>\n";
                $trace = print_r($e->getTrace(), true);
                echo preg_replace('/\n{2,}/', "\n", $trace);
            }
        }
    }


    /**
     * Find a resource file in a given directory. Files will be located according
     * to the order of the include paths. Configuration and i18n files will be
     * returned in reverse order.
     *
     * @throws  Kohana_Exception  if file is required and not found
     * @param   string   $directory  directory to search in
     * @param   string   $filename   filename to look for (without extension)
     * @param   boolean|false  $required   file required
     * @param   string|false   $ext        file extension
     * @return  array|string|false
     *    - array:   if the type is config, i18n or l10n
     *    - string:  if the file is found
     *    - false:   if the file is not found
     */
    public static function findFile($directory, $filename, $required = FALSE, $ext = FALSE)
    {
        // NOTE: This test MUST be not be a strict comparison (===), or empty
        // extensions will be allowed!
        if ($ext == '')
        {
            // Use the default extension
            $ext = '.php';
        }
        else
        {
            // Add a period before the extension
            $ext = '.'.$ext;
        }

        // Search path
        $search = $directory.'/'.$filename.$ext;
        $is_sprout = strpos($search, 'config/sprout') === 0;

        if (
            !$is_sprout
            and self::$cache_lifetime > 0
            and isset(self::$internal_cache['find_file_paths'][$search])
        ) {
            return self::$internal_cache['find_file_paths'][$search];
        }

        // Load include paths
        $paths = self::$include_paths;

        // Nothing found, yet
        $found = NULL;

        if ($directory === 'config')
        {
            array_unshift($paths, DOCROOT);
            array_unshift($paths, DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/');
        }
        else if ($directory === 'views')
        {
            array_unshift($paths, DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/');
        }

        if ($directory === 'config' OR $directory === 'i18n')
        {
            // Search in reverse, for merging
            $paths = array_reverse($paths);

            foreach ($paths as $path)
            {
                if (is_file($path.$search))
                {
                    // A matching file has been found
                    $found[] = $path.$search;
                }
            }
        }
        else
        {
            foreach ($paths as $path)
            {
                if (is_file($path.$search))
                {
                    // A matching file has been found
                    $found = $path.$search;

                    // Stop searching
                    break;
                }
            }
        }

        if ($found === NULL)
        {
            if ($required === TRUE)
            {
                // Directory i18n key
                $directory = 'core.'.Inflector::singular($directory);

                // If the file is required, throw an exception
                throw new Kohana_Exception('core.resource_not_found', self::lang($directory), $filename);
            }
            else
            {
                // Nothing was found, return FALSE
                $found = FALSE;
            }
        }

        if (!$is_sprout) {
            // Write cache at shutdown
            if ( ! isset(self::$write_cache['find_file_paths'])) {
                self::$write_cache['find_file_paths'] = TRUE;
            }

            self::$internal_cache['find_file_paths'][$search] = $found;
        }

        return $found;
    }

    /**
     * Lists all files and directories in a resource path.
     *
     * @param   string   $directory  directory to search
     * @param   boolean  $recursive  list all files to the maximum depth?
     * @param   string|false $path   full path to search (used for recursion, *never* set this manually)
     * @return  array  filenames and directories
     */
    public static function listFiles($directory, $recursive = FALSE, $path = FALSE)
    {
        $files = array();

        if ($path === FALSE)
        {
            $paths = array_reverse(self::includePaths());

            foreach ($paths as $path)
            {
                // Recursively get and merge all files
                $files = array_merge($files, self::listFiles($directory, $recursive, $path.$directory));
            }
        }
        else
        {
            $path = rtrim($path, '/').'/';

            if (is_readable($path))
            {
                $items = (array) glob($path.'*');

                if ( ! empty($items))
                {
                    foreach ($items as $index => $item)
                    {
                        $files[] = $item = str_replace('\\', '/', $item);

                        // Handle recursion
                        if (is_dir($item) AND $recursive == TRUE)
                        {
                            // Filename should only be the basename
                            $item = pathinfo($item, PATHINFO_BASENAME);

                            // Append sub-directory search
                            $files = array_merge($files, self::listFiles($directory, TRUE, $path.$item));
                        }
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Fetch an i18n language item.
     *
     * @param   string  $key   language key to fetch
     * @param   mixed   $args  additional information to insert into the line
     * @return  string|array  i18n language string, or the requested key if the i18n item is not found
     */
    public static function lang($key, ...$args)
    {
        // Extract the main group from the key
        $group = explode('.', $key, 2);
        $group = $group[0];

        // Get locale name
        $locale = self::config('locale.language.0');

        if ( ! isset(self::$internal_cache['language'][$locale][$group]))
        {
            // Messages for this group
            $messages = array();

            $path = APPPATH . "i18n/{$locale}/{$group}.php";
            $lang = self::configInclude($path, 'lang');

            // Merge in configuration
            if (!empty($lang) AND is_array($lang)) {
                foreach ($lang as $k => $v) {
                    $messages[$k] = $v;
                }
            }

            if ( ! isset(self::$write_cache['language']))
            {
                // Write language cache
                self::$write_cache['language'] = TRUE;
            }

            self::$internal_cache['language'][$locale][$group] = $messages;
        }

        // Get the line from cache
        $line = self::keyString(self::$internal_cache['language'][$locale], $key);

        if ($line === NULL)
        {
            // Return the key string as fallback
            return $key;
        }

        if (is_string($line) AND !empty($args))
        {
            // Add the arguments into the line
            $line = vsprintf($line, is_array($args[0]) ? $args[0] : $args);
        }

        return $line;
    }

    /**
     * Returns the value of a key, defined by a 'dot-noted' string, from an array.
     *
     * @param   array   $array  array to search
     * @param   string  $keys   dot-noted string: foo.bar.baz
     * @return  string|array|null
     */
    public static function keyString($array, $keys)
    {
        if (empty($array))
            return NULL;

        // Prepare for loop
        $keys = explode('.', $keys);

        if (count($keys) == 2)
        {
            return @$array[$keys[0]][$keys[1]];
        }

        do
        {
            // Get the next key
            $key = array_shift($keys);

            if (isset($array[$key]))
            {
                if (is_array($array[$key]) AND ! empty($keys))
                {
                    // Dig down to prepare the next loop
                    $array = $array[$key];
                }
                else
                {
                    // Requested key was found
                    return $array[$key];
                }
            }
            else
            {
                // Requested key is not set
                break;
            }
        }
        // @phpstan-ignore-next-line: array_shift() will eventually empty the array.
        while ( ! empty($keys));

        return NULL;
    }

    /**
     * Sets values in an array by using a 'dot-noted' string.
     *
     * @param   array|object   $array  array to set keys in (reference)
     * @param   string  $keys   dot-noted string: foo.bar.baz
     * @param   mixed   $fill   fill value for the key
     * @return  void
     */
    public static function keyStringSet( & $array, $keys, $fill = NULL)
    {
        if (is_object($array) AND ($array instanceof ArrayObject))
        {
            // Copy the array
            $array_copy = $array->getArrayCopy();

            // Is an object
            $array_object = TRUE;
        }
        else
        {
            if ( ! is_array($array))
            {
                // Must always be an array
                $array = (array) $array;
            }

            // Copy is a reference to the array
            $array_copy =& $array;
        }

        if (empty($keys))
            return;

        // Create keys
        $keys = explode('.', $keys);

        // Create reference to the array
        $row =& $array_copy;

        for ($i = 0, $end = count($keys) - 1; $i <= $end; $i++)
        {
            // Get the current key
            $key = $keys[$i];

            if ( ! isset($row[$key]))
            {
                if (isset($keys[$i + 1]))
                {
                    // Make the value an array
                    $row[$key] = array();
                }
                else
                {
                    // Add the fill key
                    $row[$key] = $fill;
                }
            }
            elseif (isset($keys[$i + 1]))
            {
                // Make the value an array
                $row[$key] = (array) $row[$key];
            }

            // Go down a level, creating a new row reference
            $row =& $row[$key];
        }

        if (isset($array_object))
        {
            // Swap the array back in
            $array->exchangeArray($array_copy);
        }
    }

    /**
     * Retrieves current user agent information:
     * keys:  browser, version, platform, mobile, robot, referrer, languages, charsets
     * tests: is_browser, is_mobile, is_robot, accept_lang, accept_charset
     *
     * @param   string   $key key or test name
     * @param   string   $compare used with "accept" tests: userAgent(accept_lang, en)
     * @return  array|string|boolean|null
     *   - array: languages and charsets
     *   - string: all other keys
     *   - boolean: all tests
     *   - null: invalid key or test
     */
    public static function userAgent($key = 'agent', $compare = NULL)
    {
        static $info;

        // Return the raw string
        if ($key === 'agent')
            return self::$user_agent;

        if ($info === NULL)
        {
            // Parse the user agent and extract basic information
            $agents = self::config('user_agents');

            foreach ($agents as $type => $data)
            {
                foreach ($data as $agent => $name)
                {
                    if (stripos(self::$user_agent, $agent) !== FALSE)
                    {
                        if ($type === 'browser' AND preg_match('|'.preg_quote($agent).'[^0-9.]*+([0-9.][0-9.a-z]*)|i', self::$user_agent, $match))
                        {
                            // Set the browser version
                            $info['version'] = $match[1];
                        }

                        // Set the agent name
                        $info[$type] = $name;
                        break;
                    }
                }
            }
        }

        if (empty($info[$key]))
        {
            switch ($key)
            {
                case 'is_robot':
                case 'is_browser':
                case 'is_mobile':
                    // A boolean result
                    $return = ! empty($info[substr($key, 3)]);
                break;
                case 'languages':
                    $return = array();
                    if ( ! empty($_SERVER['HTTP_ACCEPT_LANGUAGE']))
                    {
                        if (preg_match_all('/[-a-z]{2,}/', strtolower(trim($_SERVER['HTTP_ACCEPT_LANGUAGE'])), $matches))
                        {
                            // Found a result
                            $return = $matches[0];
                        }
                    }
                break;
                case 'charsets':
                    $return = array();
                    if ( ! empty($_SERVER['HTTP_ACCEPT_CHARSET']))
                    {
                        if (preg_match_all('/[-a-z0-9]{2,}/', strtolower(trim($_SERVER['HTTP_ACCEPT_CHARSET'])), $matches))
                        {
                            // Found a result
                            $return = $matches[0];
                        }
                    }
                break;
                case 'referrer':
                    if ( ! empty($_SERVER['HTTP_REFERER']))
                    {
                        // Found a result
                        $return = trim($_SERVER['HTTP_REFERER']);
                    }
                break;
            }

            // Cache the return value
            isset($return) and $info[$key] = $return;
        }

        if ( ! empty($compare))
        {
            // The comparison must always be lowercase
            $compare = strtolower($compare);

            switch ($key)
            {
                case 'accept_lang':
                    // Check if the lange is accepted
                    return in_array($compare, self::userAgent('languages'));

                case 'accept_charset':
                    // Check if the charset is accepted
                    return in_array($compare, self::userAgent('charsets'));

                default:
                    // Invalid comparison
                    return FALSE;

            }
        }

        // Return the key, if set
        return isset($info[$key]) ? $info[$key] : NULL;
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
     */
    public static function backtrace($trace)
    {
        if ( ! is_array($trace))
            return '';

        // Final output
        $output = array();

        foreach ($trace as $entry)
        {
            $temp = '<li>';

            if (isset($entry['file'])) {
                if (IN_PRODUCTION or @$_SERVER['SERVER_ADDR'] != @$_SERVER['REMOTE_ADDR']) {
                    $entry['file'] = preg_replace('!^' . preg_quote(DOCROOT) . '!', '', $entry['file']);
                }

                $temp .= self::lang('core.error_file_line', $entry['file'], $entry['line']);
            }

            $temp .= '<pre>';

            if (isset($entry['source'])) {
                $temp .= $entry['source'];
                $temp .= '</pre></li>';
                $output[] = $temp;
                continue;
            }

            if (isset($entry['class']))
            {
                // Add class and call type
                $temp .= $entry['class'].$entry['type'];
            }

            // Add function
            $temp .= $entry['function'].'( ';

            // Add function args
            if (isset($entry['args']) AND is_array($entry['args']))
            {
                // Separator starts as nothing
                $sep = '';

                while ($arg = array_shift($entry['args']))
                {
                    if (is_string($arg))
                    {
                        $arg = Enc::cleanfunky($arg);

                        // Remove docroot from filename
                        if (is_file($arg))
                        {
                            $arg = preg_replace('!^'.preg_quote(DOCROOT).'!', '', $arg);
                        }
                    }

                    $temp .= $sep.'<span>'.Enc::html(print_r($arg, TRUE)).'</span>';

                    // Change separator to a comma
                    $sep = ', ';
                }
            }

            $temp .= ' )</pre></li>';

            $output[] = $temp;
        }

        return '<ul class="backtrace">'.implode("\n", $output).'</ul>';
    }

    /**
     * Saves the internal caches: configuration, include paths, etc.
     *
     * @return  boolean
     */
    public static function internalCacheSave()
    {
        if ( ! is_array(self::$write_cache))
            return FALSE;

        // Get internal cache names
        $caches = array_keys(self::$write_cache);

        // Nothing written
        $written = FALSE;

        // The 'sprout' config is read from different skins based on the subsite
        unset(self::$internal_cache['find_file_paths']['config/sprout.php']);

        foreach ($caches as $cache)
        {
            if (isset(self::$internal_cache[$cache]))
            {
                // Write the cache file
                self::cacheSave($cache, self::$internal_cache[$cache]);

                // A cache has been written
                $written = TRUE;
            }
        }

        return $written;
    }

    /**
     * Ends the current output buffer with callback in mind
     * PHP doesn't pass the output to the callback defined in ob_start() since 5.4
     *
     * @param callable|null $callback
     * @return boolean
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
 */
class Kohana_Exception extends Exception
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
        parent::__construct($message);
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

    /**
     * Sends an Internal Server Error header.
     *
     * @return  void
     */
    public function sendHeaders()
    {
        // Send the 500 header
        header('HTTP/1.1 500 Internal Server Error');
    }

} // End Kohana Exception

/**
 * Creates a custom exception.
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

        Exception::__construct(Kohana::lang('core.page_not_found', $page));
    }

    /**
     * Sends "File Not Found" headers, to emulate server behavior.
     *
     * @return void
     */
    public function sendHeaders()
    {
        // Send the 404 header
        header('HTTP/1.1 404 File Not Found');
    }

} // End Kohana 404 Exception


/**
* Recursive version of array_map function
* I would imagine would be much slower than the original. Only use if necessary
*
* @param mixed $callback Callback function, can be string, array or in PHP 5.3, a function
* @param array $array The array to process
**/
function arrayMapRecursive($callback, $array) {
    $keys = array_keys($array);
    foreach ($keys as $k)
    {
        $array[$k] = is_array($array[$k]) ? arrayMapRecursive($callback, $array[$k]) : call_user_func($callback, $array[$k]);
    }
    return $array;
}
