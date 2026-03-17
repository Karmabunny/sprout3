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

use karmabunny\pdb\Exceptions\QueryException;


/**
* Logic for selecting the page if no controller matches
**/
class PageRouting
{

    /**
    * Called before the main Kohana routing code
    **/
    public static function prerouting()
    {
        if (strpos(Router::$current_uri, 'admin/') === 0) return;
        if (strpos(Router::$current_uri, 'dbtools/') === 0) return;
        if (strpos(Router::$current_uri, '_media/') === 0) return;

        // Redirect
        try {
            $url_std = trim(Router::$current_uri, '/ ');
            $params = [
                'url_std' => $url_std,
                'url_like' => Pdb::likeEscape($url_std),
                'subsite_id' => SubsiteSelector::$subsite_id,
                'domain_std' => $_SERVER['HTTP_HOST'],
            ];

            $q = "SELECT type, destination, preserve_query
                FROM ~redirects
                WHERE
                    active = 1
                    AND
                    (path_exact = '' OR path_exact LIKE :url_like)
                    AND
                    (path_contains = '' OR :url_std LIKE CONCAT('%', path_contains, '%'))
                    AND
                    (subsite_id = 0 OR subsite_id = :subsite_id)
                    AND
                    (domain_contains = '' OR :domain_std LIKE CONCAT('%', domain_contains, '%'))
                ORDER BY id
                LIMIT 1";
            $row = Pdb::q($q, $params, 'row');

            $url = Lnk::url($row['destination']);
            $method = ($row['type'] == 'Permanent' ? 301 : 302);

            if ($url) {
                if ($row['preserve_query']) {
                    $params = $_GET;

                    if (strpos($url, '?') !== false) {
                        list($url, $query) = explode('?', $url, 2);
                        parse_str($query, $parts);
                        $params = array_merge($params, $parts);
                    }

                    if ($params) {
                        $url .= '?' . http_build_query($params);
                    }
                }

                Url::redirect($url, (string)$method);
            }
        } catch (QueryException $ex) {}
    }


    /**
    * Called after the main Kohana routing code
    **/
    public static function postrouting()
    {
        // This should have already hit a controller or produced a config error.
        if (Router::$current_uri === '') {
            return;
        }

        // If we've already got a controller, there isn't anything to do here
        if (Router::$controller !== NULL) {
            return;
        }

        Router::$controller = 'Sprout\\Controllers\\PageController';

        // Look for a valid page
        $root = Navigation::getRootNode();
        $matcher = new TreenodePathMatcher(Router::$current_uri);
        $node = $root->findNode($matcher);
        if ($node) {
            Router::$method = 'viewById';
            Router::$arguments = array($node['id']);
            return;
        }

        // 404 error
        Router::$method = 'fourOhFour';
        Router::$arguments = array(Router::$current_uri);
    }

}
