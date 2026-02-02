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
use Kohana_Exception;
use karmabunny\kb\Events;
use Sprout\Events\ShutdownEvent;
use Sprout\Helpers\Drivers\SessionDriver;

/**
 * Session library.
 */
class Session
{

    /** @var Session|null singleton */
    protected static $instance;

    /** @var string[] Protected key names (cannot be set by the user) */
    protected static $protect = array('session_id', 'user_agent', 'last_activity', 'ip_address', 'total_hits', '_kf_flash_');

    /** @var array Configuration and driver */
    protected static $config;

    /** @var SessionDriver|null driver */
    protected static $driver;

    /** @var array Flash variables */
    protected static $flash;

    /**
     * Singleton instance of Session.
     */
    public static function instance()
    {
        if (Session::$instance == NULL)
        {
            // Create a new instance
            new Session;
        }

        return Session::$instance;
    }

    /**
     * On first session instance creation, sets up the driver and creates session.
     */
    public function __construct()
    {
        if (PHP_SAPI === 'cli') {
            $_SESSION = [];
            return;
        }

        // This part only needs to be run once
        if (Session::$instance === NULL)
        {
            // Load config
            Session::$config = Kohana::config('session');

            // Makes a mirrored array, eg: foo=foo
            Session::$protect = array_combine(Session::$protect, Session::$protect);

            // Create a new session
            static::create();

            if (Session::$config['regenerate'] > 0 AND ($_SESSION['total_hits'] % Session::$config['regenerate']) === 0)
            {
                // Regenerate session id and update session cookie
                static::regenerate();
            }

            // Close the session on system shutdown (run before sending the headers), so that
            // the session cookie(s) can be written.
            Events::on(Kohana::class, ShutdownEvent::class, [self::class, 'writeClose']);

            // Singleton instance
            Session::$instance = $this;
        }
    }

    /**
     * Get the session id.
     *
     * Note, if the session is not yet started this returns an empty string.
     *
     * @return  string
     */
    public static function id()
    {
        return $_SESSION['session_id'] ?? '';
    }

    /**
     * Create a new session.
     *
     * @param array|null $vars Variables to set after creation
     * @return void
     */
    public static function create($vars = NULL): void
    {
        // Destroy any current sessions
        static::destroy();

        // Configure garbage collection
        if (session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.gc_probability', (int) Session::$config['gc_probability']);
            ini_set('session.gc_divisor', 100);
            ini_set('session.gc_maxlifetime', (Session::$config['expiration'] == 0) ? 86400 : Session::$config['expiration']);
        }

        if (Session::$config['driver'] !== 'native' and Session::$config['driver'] !== 'redis') {
            // Set driver name
            $driver = 'Sprout\\Helpers\\Drivers\\Session\\' . ucfirst(Session::$config['driver']);

            // Load the driver
            if (!class_exists($driver))
                throw new Kohana_Exception('core.driver_not_found', Session::$config['driver'], static::class);

            // Initialize the driver
            Session::$driver = new $driver();

            // Validate the driver
            if ( ! (Session::$driver instanceof SessionDriver))
                throw new Kohana_Exception('core.driver_implements', Session::$config['driver'], static::class, 'SessionDriver');

            // Register non-native driver as the session handler
            session_set_save_handler
            (
                array(Session::$driver, 'open'),
                array(Session::$driver, 'close'),
                array(Session::$driver, 'read'),
                array(Session::$driver, 'write'),
                array(Session::$driver, 'destroy'),
                array(Session::$driver, 'gc')
            );
        }

        // Validate the session name
        if ( ! preg_match('~^(?=.*[a-z])[a-z0-9_]++$~iD', Session::$config['name']))
            throw new Kohana_Exception('session.invalid_session_name', Session::$config['name']);

        // Name the session, this will also be the name of the cookie
        session_name(Session::$config['name']);

        // Set the session cookie parameters
        session_set_cookie_params
        (
            Session::$config['expiration'],
            Kohana::config('cookie.path'),
            Kohana::config('cookie.domain'),
            Kohana::config('cookie.secure'),
            true    // never allow javascript to access session cookies
        );

        // If redis is available then it's used for session storage
        if (self::$config['driver'] == 'redis') {
            Rdb::registerSessionHandler();
        }

        // Start the session!
        session_start();

        // Put session_id in the session variable
        $_SESSION['session_id'] = session_id();

        // Set defaults
        if ( ! isset($_SESSION['_kf_flash_']))
        {
            $_SESSION['total_hits'] = 0;
            $_SESSION['_kf_flash_'] = array();

            $_SESSION['user_agent'] = Kohana::$user_agent;
            $_SESSION['ip_address'] = Request::userIp();
        }

        // Set up flash variables
        Session::$flash =& $_SESSION['_kf_flash_'];

        // Increase total hits
        $_SESSION['total_hits'] += 1;

        // Validate data only on hits after one
        if ($_SESSION['total_hits'] > 1)
        {
            // Validate the session
            foreach (Session::$config['validate'] as $valid)
            {
                switch ($valid)
                {
                    // Check user agent for consistency
                    case 'user_agent':
                        if ($_SESSION[$valid] !== Kohana::$user_agent) {
                            static::create();
                            return;
                        }
                    break;

                    // Check ip address for consistency
                    case 'ip_address':
                        if ($_SESSION[$valid] !== Request::userIp()) {
                            static::create();
                            return;
                        }
                    break;

                    // Check expiration time to prevent users from manually modifying it
                    case 'expiration':
                        if (time() - $_SESSION['last_activity'] > ini_get('session.gc_maxlifetime')) {
                            static::create();
                            return;
                        }
                    break;
                }
            }
        }

        // Expire flash keys
        static::expireFlash();

        // Update last activity
        $_SESSION['last_activity'] = time();

        // Set the new data
        Session::set($vars);
    }

    /**
     * Regenerates the global session id.
     *
     * @return  void
     */
    public static function regenerate()
    {
        if (Session::$config['driver'] === 'native' or Session::$config['driver'] == 'redis')
        {
            // Generate a new session id
            // Note: also sets a new session cookie with the updated id
            session_regenerate_id(TRUE);

            // Update session with new id
            $_SESSION['session_id'] = session_id();
        }
        else
        {
            // Pass the regenerating off to the driver in case it wants to do anything special
            $_SESSION['session_id'] = Session::$driver->regenerate();
        }

        // Get the session name
        $name = session_name();

        if (isset($_COOKIE[$name]))
        {
            // Change the cookie value to match the new session id to prevent "lag"
            Cookie::set(
                $name,
                $_SESSION['session_id'],
                Session::$config['expiration'],
                Kohana::config('cookie.path'),
                Kohana::config('cookie.domain'),
                Kohana::config('cookie.secure'),
                true     // httpOnly flag, i.e. no javascript access
            );
        }
    }

    /**
     * Destroys the current session.
     *
     * @return  void
     */
    public static function destroy()
    {
        if (session_id() !== '')
        {
            // Get the session name
            $name = session_name();

            // Destroy the session
            session_destroy();

            // Re-initialize the array
            $_SESSION = array();

            // Delete the session cookie
            Cookie::delete($name);
        }
    }

    /**
     * Runs the system.session_write event, then calls session_write_close.
     *
     * @return  void
     */
    public static function writeClose()
    {
        static $run;

        if ($run === NULL)
        {
            $run = TRUE;

            // Run the events that depend on the session being open
            // Not required because we're executing handlers in reverse?
            // Event::run('system.session_write');

            // Expire flash keys
            static::expireFlash();

            // Close the session
            session_write_close();
        }
    }

    /**
     * Set a session variable.
     *
     * @param string|array $keys Key, or array of values
     * @param mixed $val Value (if keys is not an array)
     * @return void
     */
    public static function set($keys, $val = FALSE): void
    {
        if (empty($keys))
            return;

        if ( ! is_array($keys))
        {
            $keys = array($keys => $val);
        }

        foreach ($keys as $key => $val)
        {
            if (isset(Session::$protect[$key]))
                continue;

            // Set the key
            $_SESSION[$key] = $val;
        }
    }

    /**
     * Set a flash variable.
     *
     * @param string|array $keys Key, or array of values
     * @param mixed $val Value (if keys is not an array)
     * @return void
     */
    public static function setFlash($keys, $val = FALSE): void
    {
        if (empty($keys))
            return;

        if ( ! is_array($keys))
        {
            $keys = array($keys => $val);
        }

        foreach ($keys as $key => $val)
        {
            if ($key == FALSE)
                continue;

            Session::$flash[$key] = 'new';
            Session::set($key, $val);
        }
    }

    /**
     * Freshen one, multiple or all flash variables.
     *
     * @param   string  $keys variable key(s)
     * @return  void
     */
    public static function keepFlash(...$keys)
    {
        if (empty($keys)) {
            $keys = array_keys(Session::$flash);
        }

        foreach ($keys as $key)
        {
            if (isset(Session::$flash[$key]))
            {
                Session::$flash[$key] = 'new';
            }
        }
    }

    /**
     * Expires old flash data and removes it from the session.
     *
     * @return  void
     */
    public static function expireFlash()
    {
        static $run;

        // Method can only be run once
        if ($run === TRUE)
            return;

        if ( ! empty(Session::$flash))
        {
            foreach (Session::$flash as $key => $state)
            {
                if ($state === 'old')
                {
                    // Flash has expired
                    unset(Session::$flash[$key], $_SESSION[$key]);
                }
                else
                {
                    // Flash will expire
                    Session::$flash[$key] = 'old';
                }
            }
        }

        // Method has been run
        $run = TRUE;
    }

    /**
     * Get a variable. Access to sub-arrays is supported with key.subkey.
     *
     * @param string|false $key Variable key
     * @param mixed $default Default value returned if variable does not exist
     * @return mixed Variable data if key specified, otherwise array containing all session data.
     */
    public static function get($key = FALSE, $default = FALSE)
    {
        if (empty($key))
            return $_SESSION;

        $result = isset($_SESSION[$key]) ? $_SESSION[$key] : Kohana::keyString($_SESSION, $key);

        return ($result === NULL) ? $default : $result;
    }


    /**
     *
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return self::get($key, null) !== null;
    }


    /**
     * Get a variable, and delete it.
     *
     * @param string $key Variable key
     * @param mixed $default Default value returned if variable does not exist
     * @return mixed
     */
    public static function getOnce($key, $default = FALSE)
    {
        $return = Session::get($key, $default);
        Session::delete($key);

        return $return;
    }

    /**
     * Delete one or more variables.
     *
     * @param   string  $args variable key(s)
     * @return  void
     */
    public static function delete(...$args)
    {
        foreach ($args as $key)
        {
            if (isset(Session::$protect[$key]))
                continue;

            // Unset the key
            unset($_SESSION[$key]);
        }
    }


    /**
     * Removes all session variables.
     */
    public static function deletall()
    {
        foreach ($_SESSION as $key => $_) {
            unset($_SESSION[$key]);
        }
    }


    /**
     * Return the entire session object.
     *
     * @return array
     */
    public static function list()
    {
        return $_SESSION;
    }

} // End Session Class
