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
            'path' => '/_trace/log',
            'uid' => self::getSiteUid(),
            'token' => self::getSiteToken(),
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
