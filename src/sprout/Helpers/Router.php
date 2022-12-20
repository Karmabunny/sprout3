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

use BootstrapConfig;
use Exception;

use Kohana;
use Kohana_Exception;
use utf8;

use karmabunny\router\Router as KbRouter;

/**
 * Router
 */
class Router
{
    /** The original URI requested e.g. by a HTTP user agent or via CLI */
    public static $current_uri = '';

    /** Original query string requested by HTTP user agent */
    public static $query_string = '';

    /** Original URI and query string combined. */
    public static $complete_uri = '';

    /** Controller/method URI to use, from the configured routes */
    public static $routed_uri = '';

    /** Optional fake file extension that will be added to all generated URLs, e.g. '.html' */
    public static $url_suffix = '';

    /** Controller to use */
    public static $controller;

    /** Method to call on controller */
    public static $method = 'index';

    /** Arguments to pass to controller method */
    public static $arguments = [];

    /** @var KbRouter */
    protected static $router;


    /**
     * Router setup routine; determines controller/method from URI.
     * Automatically called during Kohana setup process.
     *
     * @return  void
     */
    public static function setup()
    {
        // Load configured routes
        $routes = Kohana::config('routes');

        // Use the default route when no segments exist
        $uri = self::$current_uri;
        if ($uri === '') {
            if (!isset($routes['_default'])) {
                throw new Kohana_Exception('core.no_default_route');
            }

            $uri = '_default';
        }

        $config = Kohana::config('core.router');

        self::$router = KbRouter::create($config);
        self::$router->load($routes);

        // Find matching configured route
        $routed_uri = Router::routedUri(Router::$current_uri);
        if ($routed_uri === false) return;

        // The routed URI is now complete
        Router::$routed_uri = $routed_uri;

        // Find the controller from the registered route. If no namespace specified, assume Sprout\Controllers\...
        $segments = explode('/', trim(Router::$routed_uri, '/'));
        $controller = array_shift($segments);
        if (strpos($controller, '\\') === false) $controller = 'Sprout\\Controllers\\' . $controller;
        if (class_exists($controller)) {
            Router::$controller = $controller;
            if (count($segments) > 0) {
                Router::$method = array_shift($segments);
                Router::$arguments = $segments;
            } else {
                Router::$arguments = [];
            }
        }
    }


    /**
     * Get the routes tables.
     *
     * @return array
     */
    public static function getRoutes()
    {
        return self::$router->routes;
    }


    /**
     * Attempts to determine the current URI using CLI, GET, PATH_INFO, ORIG_PATH_INFO, or PHP_SELF.
     *
     * @return  void
     */
    public static function findUri()
    {
        if (isset($_GET['_apache_error']))
        {
            $e = array(400 => '400 Bad Request', 401 => '401 Unauthorized', 403 => '403 Forbidden', 500 => '500 Internal Server Error');
            $e = $e[$_GET['_apache_error']];

            if (!$e) {
                if ($_GET['_apache_error'][0] == '4') {
                    $e = '403 Forbidden';
                } else {
                    $e = '500 Internal Server Error';
                }
            }

            throw new Exception($e);
        }

        if (PHP_SAPI === 'cli')
        {
            // Command line requires a bit of hacking
            if (isset($_SERVER['argv'][1]))
            {
                Router::$current_uri = $_SERVER['argv'][1];

                // Remove GET string from segments
                if (($query = strpos(Router::$current_uri, '?')) !== FALSE)
                {
                    list (Router::$current_uri, $query) = explode('?', Router::$current_uri, 2);

                    // Parse the query string into $_GET
                    parse_str($query, $_GET);

                    // Convert $_GET to UTF-8
                    $_GET = utf8::clean($_GET);
                }
            }
        }
        elseif (isset($_GET['kohana_uri']))
        {
            // Use the URI defined in the query string
            Router::$current_uri = $_GET['kohana_uri'];

            // Remove the URI from $_GET
            unset($_GET['kohana_uri']);

            // Remove the URI from $_SERVER['QUERY_STRING']
            $_SERVER['QUERY_STRING'] = preg_replace('~\bkohana_uri\b[^&]*+&?~', '', $_SERVER['QUERY_STRING']);
        }
        elseif (isset($_SERVER['PATH_INFO']) AND $_SERVER['PATH_INFO'])
        {
            Router::$current_uri = $_SERVER['PATH_INFO'];
        }
        elseif (isset($_SERVER['ORIG_PATH_INFO']) AND $_SERVER['ORIG_PATH_INFO'])
        {
            Router::$current_uri = $_SERVER['ORIG_PATH_INFO'];
        }
        elseif (isset($_SERVER['PHP_SELF']) AND $_SERVER['PHP_SELF'])
        {
            Router::$current_uri = $_SERVER['PHP_SELF'];
        }

        if (($strpos_fc = strpos(Router::$current_uri, KOHANA)) !== FALSE)
        {
            // Remove the front controller from the current uri
            Router::$current_uri = (string) substr(Router::$current_uri, $strpos_fc + strlen(KOHANA));
        }

        // Remove slashes from the start and end of the URI
        Router::$current_uri = trim(Router::$current_uri, '/');

        if (Router::$current_uri !== '')
        {
            if ($suffix = Kohana::config('core.url_suffix') AND strpos(Router::$current_uri, $suffix) !== FALSE)
            {
                // Remove the URL suffix
                Router::$current_uri = preg_replace('#'.preg_quote($suffix).'$#u', '', Router::$current_uri);

                // Set the URL suffix
                Router::$url_suffix = $suffix;
            }

            // Reduce multiple slashes into single slashes
            Router::$current_uri = preg_replace('#//+#', '/', Router::$current_uri);
        }

        // Set the query string to the current query string
        if (!empty($_SERVER['QUERY_STRING'])) {
            Router::$query_string = '?'.trim($_SERVER['QUERY_STRING'], '&/');
        }

        // Remove all dot-paths from the URI, they are not valid
        Router::$current_uri = preg_replace('#\.[\s./]*/#', '', Router::$current_uri);
        Router::$current_uri = trim(Router::$current_uri, '/');

        // Remember the complete URI for some reason
        Router::$complete_uri = Router::$current_uri . Router::$query_string;
    }

    /**
     * Redirect to alternate hostname and/or protocol if requred
     *
     * The actual business rules for the desired protocol/hostname is defined in
     * the {@see BootstrapConfig} class which is located at config/_bootstrap_config.php
     *
     * @return void Redirects (301) if protocol and/or hostname should change
     */
    public static function originCleanup()
    {
        if (PHP_SAPI === 'cli') return;

        $old_proto = Request::protocol();
        $old_hostname = $_SERVER['HTTP_HOST'];

        list($new_proto, $new_hostname) = BootstrapConfig::originCleanup($old_proto, $old_hostname);

        if (BootstrapConfig::ORIGIN_CLEANUP_DEBUG) {
            self::originCleanupDebug($old_proto, $old_hostname, $new_proto, $new_hostname);
        }

        if ($new_proto !== $old_proto or $new_hostname !== $old_hostname) {
            $url = $new_proto . '://' . $new_hostname . '/' . Router::$complete_uri;
            Url::redirect($url, '301');
        }
    }

    /**
     * Output information about origin cleanup, and then exit
     * This is turned on by the BootstrapConfig::ORIGIN_CLEANUP_DEBUG constant
     *
     * @param string $old_proto
     * @param string $old_hostname
     * @param string $new_proto
     * @param string $new_hostname
     * @return void Terminates script execution
     */
    private static function originCleanupDebug($old_proto, $old_hostname, $new_proto, $new_hostname)
    {
        header('Content-type: text/plain');

        echo "Old proto:     {$old_proto}\n";
        echo "New proto:     {$new_proto}\n";
        echo "Old hostname:  {$old_hostname}\n";
        echo "New hostname:  {$new_hostname}\n\n";

        if ($new_proto !== $old_proto or $new_hostname !== $old_hostname) {
            $url = $new_proto . '://' . $new_hostname . '/' . Router::$complete_uri;
            echo "Redirect:\n{$url}";
        } else {
            echo "No redirect";
        }

        exit(0);
    }

    /**
     * Generates routed URI (i.e. controller/method/arg1/arg2/...) from given URI.
     *
     * @param string URI to convert, e.g. 'admin/edit/page/3'
     * @return string|bool Routed URI or false, e.g. 'AdminController/edit/page/3'
     * @throws Exception if no routes configured
     */
    public static function routedUri($uri)
    {
        if (Router::$router === NULL or empty(Router::$router->routes)) {
            throw new Exception('No routes loaded');
        }

        $routed_uri = $uri = trim($uri, '/');

        $method = Request::method();
        $action = self::$router->find($method, $uri);
        if (!$action) return false;

        $target = $action->target;

        // Convert class::method into sprout style segments.
        if (is_array($target)) {
            [$class, $method] = $target;
            $target = "{$class}/{$method}";

            foreach ($action->args as $arg) {
                $target .= '/' . $arg;
            }
        }

        // Ok now splice the rule args into the target.
        // So my/rule/{arg1}/path/{arg2} => 'ns\\to\\class/method/{arg1}/{arg2}'
        $routed_uri = preg_replace('#^' . $action->rule . '$#u', $target, $uri);
        return trim($routed_uri, '/');
    }

} // End Router
