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
 *
 */
class JsErrors
{

    const API_PATH = '_errors/log';


    /**
     * Get the tracing UID.
     *
     * @return string
     */
    public static function getSiteUid(): string
    {
        return Uuid::uuid5(Uuid::NS_URL, Url::base());
    }


    /**
     * Get the tracing auth token.
     *
     * @return string
     */
    public static function getSiteToken(): string
    {
        $uid = self::getSiteUid();
        return Security::serverKeySign(['scope' => $uid]);
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

        try {
            $signature = Request::getAuthorization('bearer');
            Security::serverKeyVerify(['scope' => $uid], $signature);
        }
        catch (SignatureInvalidException $ex) {
            return false;
        }

        return true;
    }
}
