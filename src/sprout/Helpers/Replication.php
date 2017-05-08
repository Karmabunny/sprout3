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


/**
* Functions for database replication.
* I guess it could be used for cluster situations too.
*
* This default class has replication disabled.
* To enable, replace this class with your own.
**/
class Replication
{

    public static function enabled()
    {
        return false;
    }


    /**
    * Return the IP address or hostname of the write server to use
    **/
    public static function getWriteHost()
    {
        throw new Exception('Replication not enabled');
    }


    /**
    * Return the IP address or hostname of the read server to use
    **/
    public static function getReadHost()
    {
        throw new Exception('Replication not enabled');
    }


    /**
    * Checks we are using the correct server for admin.
    *
    * Return false if the server is correct
    * Return the URL to redirect to if the server is incorrect.
    **/
    public static function adminUrl()
    {
        return false;
    }


    /**
    * Handle replication of a file to other servers.
    * This is always called, even if replication is not enabled above.
    *
    * @param string The file which has just been added or updated.
    * @return bool TRUE on success, FALSE on failure.
    **/
    public static function postFileUpdate($filename)
    {
        return true;
    }

}
