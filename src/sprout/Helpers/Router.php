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

use Sprout\App;

/**
 * Router
 */
class Router
{
    /** The original URI requested e.g. by a HTTP user agent or via CLI */
    public static $current_uri = '';

    /** Original query string requested by HTTP user agent */
    public static $query_string = '';

    /** Original URI and query string combined. */
    public static $complete_uri = '';

    /** @deprecated always empty */
    public static $routed_uri = '';

    /** @deprecated always empty */
    public static $url_suffix = '';

    /** @deprecated use PostRoutingEvent */
    public static $controller;

    /** @deprecated use PostRoutingEvent */
    public static $method = 'index';

    /** @deprecated use PostRoutingEvent */
    public static $arguments = [];


    /**
     * Get the routes tables.
     *
     * @deprecated use App::instance()->getRoutes() instead.
     * @return array
     */
    public static function getRoutes()
    {
        return App::instance()->getRoutes();
    }

} // End Router
