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
 * Implementation of parser for user-agents.json file
 */
class UserAgent {
    const MAX_AGE_HOURS = 6;

    private static $ua;
    private static $info = [];

    private $rules;


    public static function init()
    {
        if (self::$ua) return;

        $rules_file = STORAGE_PATH . 'cache/user-agents.json';

        $mtime = @filemtime($rules_file);
        $age = time() - $mtime;

        if ($age > 3600 * self::MAX_AGE_HOURS) {
            try {
                $new_rules = HttpReq::get('https://raw.githubusercontent.com/Karmabunny/user-agents.json/master/data/user-agents.json');
                if ($new_rules and HttpReq::getLastreqStatus() == '200') {
                    file_put_contents($rules_file, $new_rules);
                }
            } catch (Exception $ex) {
                // In case of e.g. DNS resolution failure, retain the old file
            }
        }

        self::$ua = new UserAgent($rules_file);
        self::$info = self::$ua->getAgentInfo($_SERVER['HTTP_USER_AGENT'] ?? '');
    }


    public static function getInfo() {
        self::init();
        return self::$info;
    }


    public static function getDeviceCategory() {
        self::init();
        return self::$info['device_category'] ?? 'unknown';
    }


    public static function getBodyClasses() {
        self::init();
        return 'dc-' . self::getDeviceCategory();
    }


    /**
     * Load the rules file
     * @param string $filename Filename of the rules file to load
     * @throws Exception if JSON file missing or invalid
     **/
    public function __construct($filename)
    {
        $json = @file_get_contents($filename);
        if (!$json) throw new Exception('Rules file missing or empty');

        $json = @json_decode($json);
        if (!$json) throw new Exception('Rules file not valid JSON');

        $this->rules = $json;
    }


    /**
     * Return info about a given user-agent
     * @return array Keys will only exist if values determined. Possible keys are:
     *         'os_name', 'os_version', 'os_title',
     *         'browser_name', 'browser_version',
     *         'device_category'
     */
    public function getAgentInfo($ua)
    {
        // Extra space makes the regexes much simpler
        $res = $this->process(' ' . $ua . ' ', $this->rules);

        // Convert to readable format
        $out = [];
        $mappings = [
            'on' => 'os_name',
            'ov' => 'os_version',
            'ot' => 'os_title',
            'bn' => 'browser_name',
            'bv' => 'browser_version',
            'dc' => 'device_category',
        ];
        foreach ($mappings as $old => $new) {
            if (isset($res[$old])) $out[$new] = $res[$old];
        }
        return $out;
    }


    /**
     * Internal recursive processing method
     * @param string $ua User agent
     * @param array $rules Rules extracted from JSON file
     * @return array Keys will only exist if values determined. Possible keys are:
     *         'on', 'ov', 'ot', 'bn', 'bv', 'dc'
     */
    private function process($ua, array $rules)
    {
        $out = [];
        foreach ($rules as $obj) {
            if (! preg_match($obj->regex, $ua, $matches)) continue;

            if (isset($obj->on)) $out['on'] = $this->pregInject($obj->on, $matches);
            if (isset($obj->ov)) $out['ov'] = $this->pregInject($obj->ov, $matches);
            if (isset($obj->ot)) $out['ot'] = $this->pregInject($obj->ot, $matches);
            if (isset($obj->bn)) $out['bn'] = $this->pregInject($obj->bn, $matches);
            if (isset($obj->bv)) $out['bv'] = $this->pregInject($obj->bv, $matches);
            if (isset($obj->dc)) $out['dc'] = $this->pregInject($obj->dc, $matches);

            if (isset($obj->rules)) {
                $sub = $this->process($ua, $obj->rules);
                foreach ($sub as $key => $val) {
                    $out[$key] = $val;
                }
            }
        }

        foreach ($out as $key => $val) {
            if (!$val) unset($out[$key]);
        }

        return $out;
    }


    /**
     * Replace values in a way similar to preg_replace
     */
    function pregInject($text, $matches)
    {
        foreach ($matches as $idx => $str) {
            $text = str_replace('$' . $idx , $str, $text);
        }
        return $text;
    }
}


