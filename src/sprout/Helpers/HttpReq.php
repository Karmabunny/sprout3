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
    private static $http_info;
    private static $http_headers;


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
     * @return string|false
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
    protected static function reqFopen($url, array $opts, $data = null)
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

        if (isset($opts['timeout'])) {
            $http_opts['timeout'] = (float) $opts['timeout'];
        }

        $context = stream_context_create(array('http' => $http_opts, 'ssl' => $ssl_opts));
        $response = @file_get_contents($url, 0, $context);

        $matches = null;

        if (preg_match('/ ([0-9]+) /', $http_response_header[0] ?? '', $matches)) {
            self::$http_status = $matches[1];
        } else {
            self::$http_status = null;
        }

        return $response;
    }


    /**
     * Sends a HTTP request using cURL.
     */
    protected static function reqCurl($url, array $opts, $data = '')
    {
        $ch = curl_init($url);
        $headers = [];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!empty($opts['getheaders'])) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        } else {
            curl_setopt($ch, CURLOPT_HEADER, false);
        }

        // Headers
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
            function($curl, $header) use (&$headers)
            {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                    return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
            }
        );
        $headers['method'] = $opts['method'];

        if ($opts['method'] === self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
        } else if ($opts['method'] !== self::METHOD_GET) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $opts['method']);
        }

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if (!empty($opts['headers'])) {
            $hdrs = self::buildHeadersString($opts['headers']);
            curl_setopt($ch, CURLOPT_HTTPHEADER, explode("\r\n", $hdrs));
        }

        if (!empty($opts['httpauth'])) {
            curl_setopt($ch, CURLOPT_USERPWD, $opts['httpauth']['username'] . ':' . $opts['httpauth']['password']);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
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

        if (!empty($opts['ssl_self_sign'])) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        }

        if (isset($opts['timeout'])) {
            $timeout = $opts['timeout'] * 1000;
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);

            // Give connections 10%, but always at least 1 second.
            $conn_timeout = $timeout ? max(1000, $timeout / 10) : 0;
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $conn_timeout);

            // Disable signals, which are disabled in multi-threaded SAPI anyway.
            // Reasoning: https://www.php.net/manual/en/function.curl-setopt.php#104597
            curl_setopt($ch, CURLOPT_NOSIGNAL, 1);
        }

        $resp = @curl_exec($ch);
        $info = curl_getinfo($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL error: ' . $error);
        }

        self::$http_status = $info['http_code'];
        self::$http_info = $info;
        self::$http_headers = $headers;

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
        $headers = [];

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (!empty($curl_options['getheaders'])) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        } else {
            curl_setopt($ch, CURLOPT_HEADER, false);
        }

        // Headers
        curl_setopt($ch, CURLOPT_HEADERFUNCTION,
              function($curl, $header) use (&$headers)
              {
                $len = strlen($header);
                $header = explode(':', $header, 2);
                if (count($header) < 2) // ignore invalid headers
                      return $len;

                $headers[strtolower(trim($header[0]))][] = trim($header[1]);

                return $len;
              }
        );

        if ($method === self::METHOD_POST) {
            curl_setopt($ch, CURLOPT_POST, true);
        } else if ($method !== self::METHOD_GET) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($post_data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        }

        $cert_path = $curl_options['cacert_path'] ?? (APPPATH . 'cacert.pem');

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, $cert_path);
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

        self::$http_status = $info['http_code'];
        self::$http_info = $info;
        self::$http_headers = $headers;

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
     * Return info of the last request
     *
     * @return array
     */
    public static function getLastreqInfo()
    {
        return self::$http_info;
    }


    /**
     * Return headers of the last request
     *
     * @return array
     */
    public static function getLastreqHeaders()
    {
        return self::$http_headers;
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
