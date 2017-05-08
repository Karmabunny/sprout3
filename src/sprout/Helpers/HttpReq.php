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

use Exception;
use InvalidArgumentException;

use Kohana;


/**
 * Simple HTTP(s) request wrapper
 */
class HttpReq
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';

    private static $http_status;

    /**
     * Make a simple GET request.
     * If you need more control, use @see HttpReq::req
     *
     * @param string $url The URL to fetch
     * @return string Response data
     */
    public static function get($url)
    {
        return self::req(
            $url,
            array('method' => 'GET'),
            null
        );
    }


    /**
     * Make a simple POST request, with optional data
     * If you need more control, use @see HttpReq::req
     *
     * @param string $url The URL to send a POST request to
     * @param array $data The POST data
     * @return string Response data
     */
    public static function post($url, $data = null)
    {
        return self::req(
            $url,
            array('method' => 'POST'),
            $data
        );
    }


    /**
     * Make a HTTP request. Returns the response content.
     *
     * The request options array accepts the following keys:
     *   method     The HTTP method to use ('GET', 'POST')
     *   headers    An array of headers; see ::buildHeadersString
     *              for format information
     *
     * @param string $url The URL to request.
     * @param array $opts Request options array.
     * @param string/array $data Request data, for POST requests.
     *
     * @return Reponse or FALSE on error.
     */
    public static function req($url, array $opts, $data = null)
    {
        if (is_array($data)) $data = http_build_query($data);

        $url = trim($url);
        if (! $url) return false;

        if (empty($opts['method'])) {
            $opts['method'] = 'GET';
        }

        $opts['method'] = strtoupper($opts['method']);

        if (function_exists('curl_init')) {
            return self::reqCurl($url, $opts, $data);
        } else {
            return self::reqFopen($url, $opts, $data);
        }
    }


    /**
     * Sends a HTTP request using fopen (i.e. file_get_contents)
     */
    private static function reqFopen($url, array $opts, $data = null)
    {
        $http_opts = array(
            'method' => $opts['method'],
            'ignore_errors' => true,
        );

        $ssl_opts = array(
            'cafile' => APPPATH . 'cacert.pem',
        );

        if ($opts['method'] == 'POST') {
            $http_opts['content'] = $data;
        }

        if (!empty($opts['headers'])) {
            $http_opts['header'] = self::buildHeadersString($opts['headers']);
        }

        $context = stream_context_create(array('http' => $http_opts, 'ssl' => $ssl_opts));
        $response = @file_get_contents($url, 0, $context);

        $matches = null;
        if (preg_match('/ ([0-9]+) /', $http_response_header[0], $matches)) {
            self::$http_status = $matches[1];
        } else {
            self::$http_status = null;
        }

        return $response;
    }


    /**
     * Sends a HTTP request using cURL.
     */
    private static function reqCurl($url, array $opts, $data = '')
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if ($opts['method'] == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
        } else if ($opts['method'] != 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $opts['method']);
        }

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if (!empty($opts['headers'])) {
            $hdrs = self::buildHeadersString($opts['headers']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, explode("\r\n", $hdrs));
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, APPPATH . 'cacert.pem');
        curl_setopt($ch, CURLOPT_CAPATH, APPPATH);

        if (Kohana::config('sprout.httpreq_proxy_host') and Kohana::config('sprout.httpreq_proxy_port')) {
            curl_setopt($ch, CURLOPT_PROXY, Kohana::config('sprout.httpreq_proxy_host'));
            curl_setopt($ch, CURLOPT_PROXYPORT, Kohana::config('sprout.httpreq_proxy_port'));

            if (Kohana::config('sprout.httpreq_proxy_auth')) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, Kohana::config('sprout.httpreq_proxy_auth'));
            }

            if (Kohana::config('sprout.httpreq_proxy_type') == 'socks5') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            } else {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            }
        }

        $resp = @curl_exec($ch);
        $info = curl_getinfo($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL error: ' . $error);
        }

        curl_close($ch);

        self::$http_status = $info['http_code'];
        return $resp;
    }


    /**
     * Performs a request while permitting advanced cURL options to be specified.
     * Essentially just a wrapper around cURL that has some sane options set by default;
     * e.g. return transfer, no return headers, SSL configured, proxy settings.
     *
     * @param string $url The URL to fetch.
     * @param string $method The HTTP request method, e.g. 'POST', 'GET', etc. Case sensitive.
     * @param string|array $post_data Data for a POST/PUT request, other request types should leave this null.
     * @param array $curl_options Any options for cURL; these will override any default.
     * @return string The response data (if any)
     * @throws \Exception If a cURL error is encountered.
     */
    public static function reqAdvanced($url, $method, $post_data = null, array $curl_options = [])
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);

        if ($method === self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
        } else if ($method !== self::METHOD_GET) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($post_data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, APPPATH . 'cacert.pem');
        curl_setopt($ch, CURLOPT_CAPATH, APPPATH);

        if (Kohana::config('sprout.httpreq_proxy_host') and Kohana::config('sprout.httpreq_proxy_port')) {
            curl_setopt($ch, CURLOPT_PROXY, Kohana::config('sprout.httpreq_proxy_host'));
            curl_setopt($ch, CURLOPT_PROXYPORT, Kohana::config('sprout.httpreq_proxy_port'));

            if (Kohana::config('sprout.httpreq_proxy_auth')) {
                curl_setopt($ch, CURLOPT_PROXYUSERPWD, Kohana::config('sprout.httpreq_proxy_auth'));
            }

            if (Kohana::config('sprout.httpreq_proxy_type') == 'socks5') {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_SOCKS5);
            } else {
                curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
            }
        }

        // Override any of the defaults with those specified
        if (count($curl_options)) {
            curl_setopt_array($ch, $curl_options);
        }

        $resp = @curl_exec($ch);
        $info = curl_getinfo($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL error: ' . $error);
        }

        curl_close($ch);

        static::$http_status = $info['http_code'];
        return $resp;
    }

    /**
     * Return the http status code ofthe last request
     *
     * @return int The HTTP status code of the last request
     */
    public static function getLastreqStatus()
    {
        return self::$http_status;
    }


    /**
     * Converts an array of headers into a \r\n-delimeted string
     *
     * Accepts three formats:
     *  - Strings will be passed through as is.
     *  - Arrays of strings (with numeric keys) will be used as-is
     *  - Arrays of strings (with string keys) will be joined
     *    using : to separate the key and value. Also, values
     *    containing quotes or spaces will be quoted.
     *
     * @param string/array $headers The headers to process
     * @return string HTTP headers
     */
    protected static function buildHeadersString($headers)
    {
        if (is_string($headers)) return $headers;
        if (! is_array($headers)) return null;

        $out = '';
        foreach ($headers as $key => $val) {
            if (is_int($key)) {
                $out .= $val . "\r\n";
            } else {
                $key = str_replace(["\n", "\r", "\t"], '', Enc::cleanfunky($key));
                $val = str_replace(["\n", "\r", "\t"], '', Enc::cleanfunky($val));
                if (empty($key) or empty($val)) {
                    throw new InvalidArgumentException('Invalid header key or value');
                }

                $out .= $key . ': ' . $val . "\r\n";
            }
        }

        return rtrim($out, "\r\n");
    }

}
