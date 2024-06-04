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

namespace Sprout\Services;

/**
 * Provides user authentication functions for the front-end
 *
 * @package Sprout\Services
 */
interface UserAuthInterface extends ServiceInterface
{

    /**
     * Check if the user is logged in or not
     *
     * @return bool True if the user is logged in, false otherwise
     */
    public static function isLoggedIn(): bool;


    /**
     * If the user is not logged in, redirect them to a login page.
     *
     * @param string $msg Optional message to display on the login page
     * @return void
     */
    public static function checkLogin($msg = null);


    /**
     * Gets id of logged-in user
     *
     * @return int 0 if user isn't logged in
     */
    public static function getId(): int;


    /**
    * Logs a user out
    **/
    public static function logout();

}
