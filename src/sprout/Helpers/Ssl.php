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

use Kohana;


/**
 * Used to force an SSL (i.e. HTTPS) connection for configured controllers and methods.
 */
class Ssl
{

    /**
    * Checks the current request against the list of require_ssl controllers/actions and force SSL for them.
    * If the user accesses a page not on the list, in SSL mode, we let them anyway.
    **/
    public static function check()
    {
        $nonssl = true;

        if (PHP_SAPI === 'cli') return;

        // Iterate through the controllers, looking for a match
        foreach (Kohana::config('require_ssl.require_ssl') as $controller => $methods) {
            if (Router::$controller == $controller) {

                if ($methods == '*') {
                    // Match any method
                    if (Request::protocol() != 'https') {
                        Url::redirect(Url::base(false, 'https') . Url::current(true));
                    }
                    $nonssl = false;

                } else {
                    if (in_array(Router::$method, $methods)) {
                        if (Request::protocol() != 'https')    {
                            Url::redirect(Url::base(false, 'https') . Url::current(true));
                        }
                        $nonssl = false;
                    }
                }
            }
        }
    }

}
