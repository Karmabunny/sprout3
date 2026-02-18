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

/**
 * Eases reading and writing cookies.
 */
class Cookie
{

    /**
     * Sets a cookie with the given parameters.
     *
     * @param   string $name Cookie name
     * @param   string|null $value Cookie value
     * @param   int|null $expire Number of seconds before the cookie expires
     * @param   string|null $path URL path to allow
     * @param   string|null $domain URL domain to allow
     * @param   bool|null $secure HTTPS only
     * @param   bool|null $httponly HTTP only (requires PHP 5.2 or higher)
     * @return  bool
     */
    public static function set($name, $value = NULL, $expire = NULL, $path = NULL, $domain = NULL, $secure = NULL, $httponly = NULL)
    {
        if (headers_sent())
            return FALSE;

        // Fetch default options
        $config = Kohana::config('cookie');

        foreach (array('value', 'expire', 'domain', 'path', 'secure', 'httponly') as $item)
        {
            if ($$item === NULL AND isset($config[$item]))
            {
                $$item = $config[$item];
            }
        }

        // Expiration timestamp
        $expire = ($expire == 0) ? 0 : time() + (int) $expire;

        return setcookie($name, $value, $expire, $path, $domain, $secure, $httponly);
    }

    /**
     * Fetch a cookie value
     *
     * @param   string $name Cookie name
     * @param   mixed $default Default value
     * @return  string
     */
    public static function get($name, $default = NULL)
    {
        return (isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default);
    }

    /**
     * Nullify and unset a cookie.
     *
     * @param   string $name Cookie name
     * @param   string|null $path URL path
     * @param   string|null $domain URL domain
     * @return  bool
     */
    public static function delete($name, $path = NULL, $domain = NULL)
    {
        if ( ! isset($_COOKIE[$name]))
            return FALSE;

        // Delete the cookie from globals
        unset($_COOKIE[$name]);

        // Sets the cookie value to an empty string, and the expiration to 24 hours ago
        return Cookie::set($name, '', -86400, $path, $domain, FALSE, FALSE);
    }

} // End cookie
