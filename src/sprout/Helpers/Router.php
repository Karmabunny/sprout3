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

use Exception;
use karmabunny\kb\Events;
use Kohana;
use Kohana_Exception;
use utf8;

use karmabunny\router\Router as KbRouter;
use Sprout\Events\PostRoutingEvent;
use Sprout\Events\PreRoutingEvent;

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
        $event = new PreRoutingEvent([
            'uri' => trim(Router::$current_uri, '/'),
        ]);

        Events::trigger(Router::class, $event);

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
        $routed_uri = Router::routedUri($uri);

        // The routed URI is now complete
        if ($routed_uri !== false) {
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

        $event = new PostRoutingEvent([
            'uri' => trim($uri, '/'),
        ]);

        Events::trigger(Router::class, $event);
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
            $error_code = (string) ($_GET['_apache_error'] ?? '');
            $e = $e[(int) $error_code] ?? false;

            if (!$e) {
                if (isset($error_code[0]) && $error_code[0] == '4') {
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
     * Generates routed URI (i.e. controller/method/arg1/arg2/...) from given URI.
     *
     * @param string $uri URI to convert, e.g. 'admin/edit/page/3'
     * @return string|bool Routed URI or false, e.g. 'AdminController/edit/page/3'
     * @throws Exception if no routes configured
     */
    public static function routedUri($uri)
    {
        if (Router::$router === NULL or empty(Router::$router->routes)) {
            throw new Exception('No routes loaded');
        }

        $method = Request::method();
        $action = self::$router->find(strtoupper($method), $uri);
        if (!$action) return false;

        $target = $action->target;
        $rule = explode(' ', $action->rule, 2);
        $rule = count($rule) == 2 ? $rule[1] : $rule[0];

        // Convert class::method into sprout style segments.
        if (is_array($target)) {
            [$class, $method] = $target;
            $routed_uri = "{$class}/{$method}";

            foreach ($action->args as $value) {
                $routed_uri .= '/' . $value;
            }
        } else {
            // - 'rule' is a regex: some/regex/([^/]+)/path/([^/]+)
            // - 'target' is a placeholder: ns\\to\\class/method/$1/$2
            // - 'uri' is the actual URI: some/regex/123/path/456
            // The results should look like:
            // - ns\\to\\class/method/123/456
            $routed_uri = preg_replace('#^' . $rule . '$#u', $target, $uri);
        }

        return trim($routed_uri, '/');
    }

} // End Router
