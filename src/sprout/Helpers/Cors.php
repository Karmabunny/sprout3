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
use Sprout\Exceptions\CorsException;

/**
 * Cross Origin Resource Sharing.
 *
 * TODO conditional safe headers: content-type, accept, accept-language, content-language
 * TODO conditional safe is unsafe: >128 characters
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
            'from',
            'dnt',
            'via',
        ],
        'allow_credentials' => false,
        'ignore_headers' => false,
        'expose_headers' => false,
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
        'cookie',

        // CORS safe headers.
        'cache-control',
        'content-language',
        'content-length',
        'content-type',
        'expires',
        'last-modified',
        'pragma',
        'range',

        // CORS headers.
        'origin',
        'access-control-request-headers',
        'access-control-request-method',
    ];


    /**
     * Get the origin of the request.
     *
     * @return string|null
     */
    public static function getOrigin(): ?string
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
        return $origin ? trim($origin) : null;
    }


    /**
     * Is this a CORS request?
     *
     * @return bool
     */
    public static function isCors(): bool
    {
        return self::getOrigin() !== null;
    }


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
        $origin = self::getOrigin();

        // Skip everything if we don't have a origin.
        // CORS is purely a browser protection and doesn't extend to
        // non-browser API calls or modified or out-of-date browsers.
        if (!$origin) return;

        $config = array_merge(self::DEFAULT_CONFIG, $config);

        if ($config['allow_credentials'] ?? false) {
            $config['headers'][] = 'authorization';
            $config['headers'][] = 'cookie';
        }

        $headers = Request::getHeaders();
        $method = Request::method();

        $errors = [];

        // Preflight checks.
        if ($method === 'options') {
            $method = strtolower($headers['access-control-request-method'] ?? '');
            $headers = explode(',', $headers['access-control-request-headers'] ?? '');

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

            $headers = array_keys($headers);

            // Clear out fancy 'Sec-' headers, cdn, cf headers, as well as x-forwarded, etc.
            foreach ($headers as $key => $header) {
                if (!(
                    preg_match('/^sec-(ch-ua|fetch|gpc)/', $header)
                    or preg_match('/^x-(forwarded|amzn)-/', $header)
                    or preg_match('/^(cf|cdn)-/', $header)
                )) continue;

                unset($headers[$key]);
            }
        }

        // TODO Validate origins here.
        // $errors[] = 'bad origin';

        // Validate permitted headers.
        if (empty($config['ignore_headers']) and count(array_intersect($config['headers'], $headers)) !== count($headers)) {
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
                header('x-debug-bad-headers: ' . implode(',', array_diff($headers, $config['headers'])));
                header('x-debug-method: ' . $method);
                header('x-debug-origin: ' . $origin);
            }

            $exception = new CorsException('Bad CORS request: ' . implode(', ', $errors));
            $exception->headers = $headers;
            $exception->bad_headers = array_diff($headers, $config['headers']);
            $exception->method = $method;
            $exception->origin = $origin;
            Kohana::logException($exception);

            exit;
        }

        header('Access-Control-Allow-Origin: ' . $origin);
        header('Access-Control-Allow-Headers: ' . implode(',', $config['headers']));
        header('Access-Control-Allow-Methods: ' . implode(',', $config['methods']));
        header('Vary: origin,access-control-request-headers,access-control-request-method', false);

        if ($config['allow_credentials'] ?? false) {
            header('Access-Control-Allow-Credentials: true');
        }

        $expose_headers = $config['expose_headers'] ?? [];

        if ($expose_headers === true) {
            header('Access-Control-Expose-Headers: *');
        } else if ($expose_headers) {
            header('Access-Control-Expose-Headers: ' . implode(',', $expose_headers));
        }

        // An options request stops here, sends 'no content'.
        $method = Request::method();

        if ($method === 'options') {
            Kohana::closeBuffers(false);
            http_response_code(204);
            exit;
        }
    }
}


