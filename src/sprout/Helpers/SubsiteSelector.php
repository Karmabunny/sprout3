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

use Sprout\Exceptions\QueryException;


class SubsiteSelector
{
    static public $subsite_id = 0;
    static public $content_id = 0;
    static public $subsite_code = '';
    static public $url_prefix = '';
    static public $mobile = false;


    /**
    * Selects a single subsite by looking up the provided subsite code
    * Subsite code is provided by the .htaccess file
    **/
    static public function selectSubsite()
    {
        try {
            $q = "SELECT id, content_id, code, mobile, cond_domain, cond_directory, require_admin, require_user
                FROM ~subsites
                WHERE active = 1
                ORDER BY id";
            $res = Pdb::query($q, [], 'arr');
        } catch (QueryException $ex) {
            self::$subsite_id = 1;
            self::$content_id = 1;
            self::$subsite_code = 'default';
            return;
        }

        if (count($res) == 0) {
            if (!preg_match('!^(admin|admin_ajax|testing|dbtools)!', Router::$current_uri)) {
                throw new Exception('This website does not have any accessable subsites defined');
            }
        }

        // Choose the best subsite for our situation
        $selected = null;
        $default = null;
        foreach ($res as $row) {
            if (!$row['cond_domain'] and !$row['cond_directory']) {
                if (!$default) $default = $row;
                continue;
            }

            $cond_domains = array_filter(preg_split('/[\r\n]/', $row['cond_domain']));

            // If the site is mobile and on a different domain, redirect
            if ($row['mobile'] and !in_array($_SERVER['HTTP_HOST'], $cond_domains)) {
                // Does the user want the mobile site?
                Session::instance();
                if (isset($_GET['mobile'])) {
                    $_SESSION['mobile'] = (int)$_GET['mobile'];
                } else if (!isset($_SESSION['mobile'])) {
                    $_SESSION['mobile'] = 1;
                }

                // If mobile is desired, do the redirect
                if ($_SESSION['mobile'] == 1) {
                    $ua_info = UserAgent::getInfo();
                    if ($ua_info['device_category'] == 'Mobile') {
                        $_SERVER['HTTP_HOST'] = $cond_domains[0];
                        Url::redirect(Url::base(false, 'http') . Url::current(true));
                    }
                }
            }

            // Check domain matches
            if (count($cond_domains)) {
                if (!in_array($_SERVER['HTTP_HOST'], $cond_domains)) continue;
            }

            // Check directory matches
            if ($row['cond_directory']) {
                $pos = strpos(Router::$current_uri, $row['cond_directory']);
                if (! ($pos === 0)) continue;
            }

            // If admin access is required to view the subsite
            if ($row['require_admin']) {
                if (! AdminAuth::isLoggedIn()) continue;
            }

            // If user access is required to view the subsite
            if ($row['require_user']) {
                if (! Register::hasFeature('users')) continue;
                if (! UserAuth::isLoggedIn()) continue;
            }

            $selected = $row;
            break;
        }

        if (! $selected) {
            $selected = $default;

            // Check admin or user auth requirements for default subsite
            if (!AdminAuth::isLoggedIn() and PHP_SAPI !== 'cli') {
                if ($default['require_admin'] and !preg_match('!^(admin|admin_ajax|file)/!', Router::$current_uri)) {
                    AdminAuth::checkLogin();
                }

                if ($default['require_user'] and Register::hasFeature('users') and !preg_match('!^user/!', Router::$current_uri)) {
                    UserAuth::checkLogin();
                }
            }
        }

        if ($selected === null) {
            if (!preg_match('!^(admin|admin_ajax|testing|dbtools)!', Router::$current_uri)) {
                throw new Exception('This website does not have any accessable subsites defined');
            }
        }

        // For directory subsites, we need to nuke the leading directory part
        $directory = trim($selected['cond_directory'], '/');
        if ($directory) {
            Router::$current_uri = trim(preg_replace('!^' . preg_quote($directory) . '!', '', Router::$current_uri), '/');
            $directory .= '/';
        }

        self::$subsite_id = $selected['id'];
        self::$content_id = $selected['content_id'] ? $selected['content_id'] : $selected['id'];
        self::$subsite_code = $selected['code'];
        self::$url_prefix = $directory;
        self::$mobile = $selected['mobile'];
    }
}
