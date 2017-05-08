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


class LaunchChecks
{
    protected static $results;
    protected static $skin;


    public static function runTests()
    {
        self::$results = [];

        // Get list of unique subsite codes actually in use, to avoid noise
        $q = "SELECT code FROM ~subsites WHERE active = 1 GROUP BY code";
        $codes = Pdb::query($q, [], 'col');

        $methods = get_class_methods(__CLASS__);
        foreach ($methods as $m) {
            self::$skin = '';

            if (strpos($m, 'testSkin') === 0) {
                foreach ($codes as self::$skin) {
                    call_user_func([__CLASS__, $m], self::$skin);
                }

            } else if (strpos($m, 'test') === 0) {
                call_user_func([__CLASS__, $m]);
            }
        }

        return self::$results;
    }


    protected static function addResult($check, $result, $message)
    {
        self::$results[] = [
            'check' => $check,
            'skin' => self::$skin,
            'result' => $result,
            'message' => $message,
        ];
    }


    /**
     * Check that the "CLI domain" has been set
     */
    public static function testCliDomain()
    {
        $cli_domain = Kohana::config('config.cli_domain');

        if (empty($cli_domain)) {
            self::addResult('CLI domain', 'error', 'Not set');
            return;
        }

        // This would only be a false-positive for IANA...
        if ($cli_domain === 'www.example.com' or $cli_domain === 'devel.example.com') {
            self::addResult('CLI domain', 'error', 'Default value "' . $cli_domain . '"');
            return;
        }

        if (strpos($cli_domain, 'www.') !== 0) {
            self::addResult('CLI domain', 'warning', 'Does not begin with www.');
            return;
        }

        self::addResult('CLI domain', 'okay', $cli_domain);
    }


    /**
     * Check that each skin has a site title set
     */
    public static function testSkinSiteTitle($skin_code)
    {
        $subsite_config = Subsites::loadConfig($skin_code);

        if (empty($subsite_config['site_title'])) {
            self::addResult('Site title', 'error', 'Not set');
            return;
        }

        if ($subsite_config['site_title'] === 'Sprout3 test') {
            self::addResult('Site title', 'error', 'Default');
            return;
        }

        self::addResult('Site title', 'okay', $subsite_config['site_title']);
    }


    /**
     * Check that each skin has Google Analytics configured
     */
    public static function testSkinAnalytics($skin_code)
    {
        $subsite_config = Subsites::loadConfig($skin_code);
        if (empty($subsite_config['google_analytics_id'])) {
            self::addResult('Google Analytics', 'error', 'Not set');
        } else {
            self::addResult('Google Analytics', 'okay', $subsite_config['google_analytics_id']);
        }
    }


    /**
     * Check that each skin has Google Analytics configured
     */
    public static function testSkinTemplatesExist($skin_code)
    {
        $templates = array('home', 'inner', 'wide');
        foreach ($templates as $tmpl) {
            $exists = file_exists(DOCROOT . "skin/{$skin_code}/{$tmpl}.php");
            self::addResult("Template {$tmpl}.php exists", ($exists ? 'okay' : 'error'), '');
        }
    }

}
