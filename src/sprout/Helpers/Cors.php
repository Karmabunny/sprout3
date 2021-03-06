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
use Kohana_Exception;

/**
 * Cross Origin Resource Sharing.
 *
 * TODO conditional safe headers: content-type, accept, accept-language, content-language
 * TODO conditional safe is unsafe: >128 characters
 * TODO support for access-control-allow-credentials
 * TODO restricting origins
 * TODO support for max-age, caching 'options' requests
 *
 */
class Cors
{

    // Default (limited) config for handling CORS.
    const DEFAULT_CONFIG = [
        'origins' => ['*'],
        'methods' => [
            'get',
        ],
        'headers' => [
            'accept',
            'accept-language',
            'content-type',
            'x-requested-with',
        ],
    ];

    // https://developer.mozilla.org/en-US/docs/Glossary/CORS-safelisted_response_header
    const SAFE_HEADERS = [
        // Browser headers.
        'if-modified-since',
        'upgrade-insecure-requests',
        'accept-encoding',
        'connection',
        'user-agent',
        'host',
        'referer',

        // CORS safe headers.
        'cache-control',
        'content-language',
        'content-length',
        'content-type',
        'expires',
        'last-modified',
        'pragma',

        // CORS headers.
        'origin',
        'access-control-request-headers',
        'access-control-request-method',
    ];


    /**
     * Handling a CORS request.
     *
     * This is a work-in-progress. CORS is a big thing so there's some edge
     * cases that this doesn't yet handle.
     *
     * @param array $config [ headers, methods ]
     * @return void exits if invalid or pre-flight
     * @throws Kohana_Exception
     */
    public static function handleCors($config = [])
    {
        $config = array_merge(self::DEFAULT_CONFIG, $config);

        $origin = @$_SERVER['HTTP_ORIGIN'] ?: '';

        // Skip everything if we don't have a origin.
        // CORS is purely a browser protection and doesn't extend to
        // non-browser API calls or modified or out-of-date browsers.
        if (!$origin) return;

        $headers = Request::getHeaders();
        $method = Request::method();

        $errors = [];

        // Preflight checks.
        if ($method === 'options') {
            $method = strtolower(@$headers['access-control-request-method'] ?: '');
            $headers = explode(',', @$headers['access-control-request-headers'] ?: '');

            // Tidy up.
            foreach ($headers as &$header) {
                $header = strtolower(trim($header));
            }
            unset($header);
        } else {
            // Clear out safe headers before validation.
            foreach (self::SAFE_HEADERS as $name) {
                unset($headers[$name]);
            }
            unset($name);
            $headers = array_keys($headers);
        }

        // TODO Validate origins here.
        // $errors[] = 'bad origin';

        // Validate permitted headers.
        if (count(array_intersect($config['headers'], $headers)) !== count($headers)) {
            $errors[] = 'bad headers';
        }

        // Validate permitted methods.
        if (!in_array($method, $config['methods'])) {
            $errors[] = 'bad method';
        }

        // Toss it and quit on errors.
        if ($errors) {
            Kohana::closeBuffers(false);
            http_response_code(400);

            // Some debugging info in the response headers.
            // Browsers don't like to show the contents on a bad CORS request.
            if (!IN_PRODUCTION) {
                header('x-debug-config: ' . json_encode($config));
                header('x-debug-headers: ' . implode(',', $headers));
                header('x-debug-method: ' . $method);
                header('x-debug-origin: ' . $origin);
            }

            exit;
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Headers: ' . implode(',', $config['headers']));
        header('Access-Control-Allow-Methods: ' . implode(',', $config['methods']));
        header('Vary: origin,access-control-request-headers,access-control-request-method');

        // An options request stops here, sends 'no content'.
        $method = Request::method();
        if ($method === 'options') {
            Kohana::closeBuffers(false);
            http_response_code(204);
            exit;
        }
    }
}


