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

use karmabunny\kb\Uuid;
use Sprout\Exceptions\SignatureInvalidException;

/**
 * Utilities for frontend error logging.
 *
 * Register this using `JsErrors::needs()` in your `skin/sprout_load.php`.
 *
 * Alternatively, one can configure this manually (twig example):
 *
 * ```html
 * <script src="ROOT/media/js/kbtrace.min.js"></script>
 * <script>
 * kbtrace.config({{ sprout.errors.config|json_encode|raw }});
 * kbtrace.register();
 * </script>
 * ```
 */
class JsErrors
{

    const API_PATH = '_errors/log';


    /**
     * Get the tracing UID.
     *
     * This is namespaced to the site URL so it's consistent but still unique
     * for each hostname.
     *
     * @return string
     */
    public static function getSiteUid(): string
    {
        return Uuid::uuid5(Uuid::NS_URL, $_SERVER['HTTP_HOST']);
    }


    /**
     * Get the tracing auth token.
     *
     * This is an HMAC signature (SHA1) made against the Sprout security
     * key (database.server_key).
     *
     * @return string
     */
    public static function getSiteToken(): string
    {
        $payload = self::getTokenPayload();

        if (!$payload) {
            return '';
        }

        return Security::serverKeySign($payload);
    }


    /**
     * Get a JS tracing config to use with `kbtrace.config()`.
     *
     * @return array
     */
    public static function getConfig(): array
    {
        return [
            'url' => Url::base(),
            'path' => self::API_PATH,
            'uid' => self::getSiteUid(),
            'token' => self::getSiteToken(),
        ];
    }


    /**
     * Register the needs for the JS error handler.
     *
     * This uses the bootstrapping feature and the JS library will configure itself.
     *
     * Best place this in the `skin/sprout_load.php`.
     *
     * @return void
     */
    public static function needs()
    {
        $attrs = [
            'data-path' => self::API_PATH,
            'data-uid' => self::getSiteUid(),
            'data-token' => self::getSiteToken(),
            'async' => '',
            'defer' => '',
        ];

        Needs::addJavascriptInclude('ROOT/media/js/kbtrace.min.js', $attrs, 'js-errors');
    }


    /**
     * This is the basis for generating the auth token.
     *
     * This payload must be reasonably predictable (for us). If the payload
     * changes between issuing the token to the JS library and receiving an
     * error event - it will be invalid.
     *
     * The payload will change if:
     *  - the session expires (usually ~30 minutes without a refresh)
     *  - the IP address changes (roaming networks)
     *  - the base URL changes (somehow)
     *
     * These are acceptable conditions for the authentication to re-generate.
     *
     * The payload is null if there is no session, such as CLI environments.
     *
     * @return array|null
     */
    protected static function getTokenPayload(): ?array
    {
        Session::instance();
        $session_id = Session::id();

        if (!$session_id) {
            return null;
        }

        return [
            'uid' => self::getSiteUid(),
            'session' => $session_id,
            'ip_address' => Request::userIp(),
        ];
    }


    /**
     * Verify the request is valid.
     *
     * @return bool
     */
    public static function authorize()
    {
        $uid = self::getSiteUid();

        $_GET['uid'] = $_GET['uid'] ?? '';

        if ($_GET['uid'] !== $uid) {
            return false;
        }

        $payload = self::getTokenPayload();

        if (!$payload) {
            return false;
        }

        try {
            $signature = Request::getAuthorization('bearer');
            Security::serverKeyVerify($payload, $signature);
        }
        catch (SignatureInvalidException $ex) {
            return false;
        }

        return true;
    }


    /**
     * Format a JS error + stack into a string.
     *
     * A formatted stack-trace, as defined at:
     * https://v8.dev/docs/stack-trace-api
     *
     * @param array $error
     * @param bool $cleanPaths
     * @return string
     */
    public static function formatError(array $error, bool $cleanPaths = false): string
    {
        $out = '';

        $out .= $error['name'];
        $out .= ': ';
        $out .= $error['message'];
        $out .= "\n";

        foreach ($error['stack'] as $frame) {
            $out .= '   at ';
            $out .= self::formatFrame($frame, $cleanPaths);
            $out .= "\n";
        }

        return $out;
    }


    /**
     * Format a JS stack frame into a string.
     *
     * A formatted stack-trace, as defined at:
     * https://v8.dev/docs/stack-trace-api
     *
     * @param array $frame
     * @param bool $cleanPath
     * @return string
     */
    public static function formatFrame(array $frame, bool $cleanPath = false): string
    {
        if ($cleanPath) {
            $filename = self::parseFilename($frame['fileName'] ?? '');
        }
        else {
            $filename = $frame['fileName'] ?? null;
        }

        $out = '';
        $out .= $frame['functionName'] ?? '<anonymous>';
        $out .= ' (';
        $out .= $filename ?? 'unknown';
        $out .= ':';
        $out .= $frame['lineNumber'] ?? '0';
        $out .= ':';
        $out .= $frame['columnNumber'] ?? '0';
        $out .= ')';
        return $out;
    }


    /**
     * Clean up URL paths so we can compare them against a source map.
     *
     * @param string $filename
     * @return string|null
     */
    public static function parseFilename(string $filename): ?string
    {
        $filename = preg_replace('!^/webpack://[^/]+/!', '', $filename);
        $filename = parse_url($filename, PHP_URL_PATH) ?: null;
        return $filename;
    }
}
