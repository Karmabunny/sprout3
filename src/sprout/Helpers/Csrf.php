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


/**
 * Protection against Cross Site Request Forgery (CSRF) attacks
 */
class Csrf
{
    /**
     * Initialises the PHP session and, if not present, generates a CSRF secret for the session
     *
     * @return void
     */
    protected static function initialiseSession()
    {
        Session::instance();

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = Security::randStr(32);
        }
    }

    /**
     * Generates a CSRF hidden form field
     *
     * @return string HTML: an <input type="hidden"> tag which contains the token
     */
    public static function token()
    {
        self::initialiseSession();

        return '<input type="hidden" name="edit_token" value="' . Enc::html($_SESSION['csrf_token']) . '">';
    }


    /**
    * Gets the CSRF token in the postdata.
    * Checks that it is valid.
    * Returns TRUE on success and FALSE on failure
    **/
    public static function check()
    {
        Session::instance();

        // Clearly invalid request, avoid any exceptions being generated
        if (empty($_POST['edit_token']) or empty($_SESSION['csrf_token'])) {
            return false;
        }

        if ($_POST['edit_token'] !== $_SESSION['csrf_token']) {
            return false;
        }

        return true;
    }


    /**
    * Checks the CSRF token
    * If it fails, redirect the user to the home page, and report an error
    **/
    public static function checkOrDie()
    {
        if (self::check()) return;

        Notification::error('Session timeout or missing security token');
        Url::redirect('result/error');
    }


    /**
     * Fetches the secret token value
     *
     * This is intended for use on JavaScript requests that require CSRF protection.
     * Note that it is important that this value isn't placed in GET parameters, as this
     * may result in the value being leaked through logging or other methods.
     *
     * @return string The CSRF secret
     */
    public static function getTokenValue()
    {
        self::initialiseSession();

        return $_SESSION['csrf_token'];
    }
}


