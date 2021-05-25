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


/**
 * Get information about the incoming HTTP request.
 */
class Request
{

    // Default config for handling CORS.
    const DEFAULT_CORS = [
        'origins' => ['*'],
        'methods' => [
            'options',
            'get',
        ],
        'headers' => [
            'accept',
            'accept-language',
            'accept-encoding',
            'content-language',
            'content-type',
            'pragma',
            'cache-control',
        ],
    ];

    // Possible HTTP methods
    protected static $http_methods = array('get', 'head', 'options', 'post', 'put', 'delete');

    // Content types from client's HTTP Accept request header (array)
    protected static $accept_types;


    /**
     * Returns the HTTP referrer, or the default if the referrer is not set.
     *
     * @param   mixed   default to return
     * @return  string
     */
    public static function referrer($default = FALSE)
    {
        if ( ! empty($_SERVER['HTTP_REFERER']))
        {
            // Set referrer
            $ref = $_SERVER['HTTP_REFERER'];

            if (strpos($ref, Url::base(FALSE)) === 0)
            {
                // Remove the base URL from the referrer
                $ref = substr($ref, strlen(Url::base(FALSE)));
            }
        }

        return isset($ref) ? $ref : $default;
    }

    /**
     * Returns the current request protocol, based on $_SERVER['https']. In CLI
     * mode, NULL will be returned.
     *
     * @return  string
     */
    public static function protocol()
    {
        if (!empty($_SERVER['PHP_S_PROTOCOL'])) {
            return $_SERVER['PHP_S_PROTOCOL'];
        } elseif (PHP_SAPI === 'cli') {
            return NULL;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) and Kohana::config('sprout.load_balanced')) {
            return $_SERVER['HTTP_X_FORWARDED_PROTO'];
        } elseif (!empty($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] === 'on')         {
            return 'https';
        } else {
            return 'http';
        }
    }

    /**
     * Tests if the current request is an AJAX request by checking the X-Requested-With HTTP
     * request header that most popular JS frameworks now set for AJAX calls.
     *
     * @return  boolean
     */
    public static function isAjax()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
    }

    /**
     * Returns current request method.
     *
     * @throws  Kohana_Exception in case of an unknown request method
     * @return  string
     */
    public static function method()
    {
        $method = strtolower($_SERVER['REQUEST_METHOD']);

        if ( ! in_array($method, Request::$http_methods))
            throw new Kohana_Exception('request.unknown_method', $method);

        return $method;
     }


    /**
     * Returns the users IP address, accounting for proxy forwarding
     */
    public static function userIp()
    {
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR']) and Kohana::config('sprout.load_balanced'))
        {
            $parts = explode(', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return $parts[0];
        }
        else if (!empty($_SERVER['REMOTE_ADDR']))
        {
            return $_SERVER['REMOTE_ADDR'];
        }
        else
        {
            return '0.0.0.0';
        }
    }

    /**
     * Returns boolean of whether client accepts content type.
     *
     * @param   string   content type
     * @param   boolean  set to TRUE to disable wildcard checking
     * @return  boolean
     */
    public static function accepts($type = NULL, $explicit_check = FALSE)
    {
        Request::parseAcceptHeader();

        if ($type === NULL)
            return Request::$accept_types;

        return (Request::acceptsAtQuality($type, $explicit_check) > 0);
    }

    /**
     * Compare the q values for given array of content types and return the one with the highest value.
     * If items are found to have the same q value, the first one encountered in the given array wins.
     * If all items in the given array have a q value of 0, FALSE is returned.
     *
     * @param   array    content types
     * @param   boolean  set to TRUE to disable wildcard checking
     * @return  mixed    string mime type with highest q value, FALSE if none of the given types are accepted
     */
    public static function preferredAccept($types, $explicit_check = FALSE)
    {
        // Initialize
        $mime_types = array();
        $max_q = 0;
        $preferred = FALSE;

        // Load q values for all given content types
        foreach (array_unique($types) as $type)
        {
            $mime_types[$type] = Request::acceptsAtQuality($type, $explicit_check);
        }

        // Look for the highest q value
        foreach ($mime_types as $type => $q)
        {
            if ($q > $max_q)
            {
                $max_q = $q;
                $preferred = $type;
            }
        }

        return $preferred;
    }

    /**
     * Returns quality factor at which the client accepts content type.
     *
     * @param   string   content type (e.g. "image/jpg", "jpg")
     * @param   boolean  set to TRUE to disable wildcard checking
     * @return  integer|float
     */
    public static function acceptsAtQuality($type = NULL, $explicit_check = FALSE)
    {
        Request::parseAcceptHeader();

        // Normalize type
        $type = strtolower((string) $type);

        // General content type (e.g. "jpg")
        if (strpos($type, '/') === FALSE)
        {
            // Don't accept anything by default
            $q = 0;

            // Look up relevant mime types
            foreach ((array) Kohana::config('mimes.'.$type) as $type)
            {
                $q2 = Request::acceptsAtQuality($type, $explicit_check);
                $q = ($q2 > $q) ? $q2 : $q;
            }

            return $q;
        }

        // Content type with subtype given (e.g. "image/jpg")
        $type = explode('/', $type, 2);

        // Exact match
        if (isset(Request::$accept_types[$type[0]][$type[1]]))
            return Request::$accept_types[$type[0]][$type[1]];

        // Wildcard match (if not checking explicitly)
        if ($explicit_check === FALSE AND isset(Request::$accept_types[$type[0]]['*']))
            return Request::$accept_types[$type[0]]['*'];

        // Catch-all wildcard match (if not checking explicitly)
        if ($explicit_check === FALSE AND isset(Request::$accept_types['*']['*']))
            return Request::$accept_types['*']['*'];

        // Content type not accepted
        return 0;
    }

    /**
     * Parses client's HTTP Accept request header, and builds array structure representing it.
     *
     * @return  void
     */
    protected static function parseAcceptHeader()
    {
        // Run this function just once
        if (Request::$accept_types !== NULL)
            return;

        // Initialize accept_types array
        Request::$accept_types = array();

        // No HTTP Accept header found
        if (empty($_SERVER['HTTP_ACCEPT']))
        {
            // Accept everything
            Request::$accept_types['*']['*'] = 1;
            return;
        }

        // Remove linebreaks and parse the HTTP Accept header
        foreach (explode(',', str_replace(array("\r", "\n"), '', $_SERVER['HTTP_ACCEPT'])) as $accept_entry)
        {
            // Explode each entry in content type and possible quality factor
            $accept_entry = explode(';', trim($accept_entry), 2);

            // Explode each content type (e.g. "text/html")
            $type = explode('/', $accept_entry[0], 2);

            // Skip invalid content types
            if ( ! isset($type[1]))
                continue;

            // Assume a default quality factor of 1 if no custom q value found
            $q = (isset($accept_entry[1]) AND preg_match('~\bq\s*+=\s*+([.0-9]+)~', $accept_entry[1], $match)) ? (float) $match[1] : 1;

            // Populate accept_types array
            if ( ! isset(Request::$accept_types[$type[0]][$type[1]]) OR $q > Request::$accept_types[$type[0]][$type[1]])
            {
                Request::$accept_types[$type[0]][$type[1]] = $q;
            }
        }
    }


    /**
     * Get the headers as an array.
     *
     * All keys are lowercase.
     *
     * @return array [ name => value ]
     */
    public static function getHeaders()
    {
        static $headers;
        if ($headers !== null) return $headers;

        $headers = [];

        foreach ($_SERVER as $key => $value) {
            $key = strtolower($key);

            if (strpos($key, 'http_') === 0) {
                $key = str_replace('_', '-', substr($key, 5));
            }
            else if (strpos($key, 'content_') === 0) {
                $key = str_replace('_', '-', $key);
            }
            else {
                continue;
            }

            $headers[$key] = $value;
        }

        return $headers;
    }


    /**
     * Handling a CORS request.
     *
     * This is a work-in-progress. CORS is a big thing so there's some edge
     * cases that this doesn't yet handle.
     *
     * TODO support for access-control-allow-credentials
     * TODO actually validate access-control-request-headers
     * TODO restricting origins
     * TODO support for max-age, caching 'options' requests
     *
     * @param array $config [ headers, methods ]
     * @return void exits if invalid or pre-flight
     * @throws Kohana_Exception
     */
    public static function handleCors($config = [])
    {
        $config = array_merge(self::DEFAULT_CORS, $config);

        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        $method = Request::method();
        $headers = self::getHeaders();

        // Standard headers, these are always allowed.
        unset($headers['user-agent']);
        unset($headers['connection']);
        unset($headers['host']);
        unset($headers['origin']);
        unset($headers['referer']);
        unset($headers['access-control-request-headers']);
        unset($headers['access-control-request-method']);

        $errors = [];

        // Origin must exist.
        // TODO Validate origins here.
        if (!$origin) {
            $errors[] = 'bad origin';
        }

        // Validate permitted methods.
        if (!in_array($method, $config['methods'])) {
            $errors[] = 'bad method';
        }

        // Validate permitted headers.
        // TODO Also do access-control-request-headers
        if (count(array_intersect($config['headers'], array_keys($headers))) !== count($headers)) {
            $errors[] = 'bad headers';
        }

        // Toss it and quit on errors.
        if ($errors) {
            Kohana::closeBuffers(false);
            http_response_code(400);

            // Some debugging info in the response headers.
            // Browsers don't like to show the contents on a bad CORS request.
            if (!IN_PRODUCTION) {
                header('x-debug-config: ' . json_encode($config));
                header('x-debug-headers: ' . implode(',', array_keys($headers)));
                header('x-debug-method: ' . $method);
                header('x-debug-origin: ' . $origin);
            }

            exit;
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Headers: ' . implode(',', $config['headers']));
        header('Access-Control-Allow-Methods: ' . implode(',', $config['methods']));
        header('Vary: Origin,Access-Control-Request-Headers,Access-Control-Request-Method');

        // An options request stops here, sends 'no content'.
        if ($method === 'options') {
            Kohana::closeBuffers(false);
            http_response_code(204);
            exit;
        }
    }

} // End request
