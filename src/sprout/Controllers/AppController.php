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

namespace Sprout\Controllers;

/**
 * Generic public utilities
 */
class AppController extends Controller
{

    /**
     * A simple 200 response.
     *
     * This is available at: /_healthcheck
     *
     * We want this to do all the ordinary things that a request would do.
     * That includes subsites, pre/post page routing, all of it.
     */
    public function healthcheck()
    {
        echo 'OK';
    }

}
