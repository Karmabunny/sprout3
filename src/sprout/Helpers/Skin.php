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
use Kohana;
use Sprout\Exceptions\FileMissingException;

/**
* Skin stuff - autoversioning mainly.
**/
class Skin
{

    /**
    * ECHOs a <link> tag and a <script> tag which point to common.css and common.js respectively.
    **/
    public static function common()
    {
        echo '<link href="ROOT/_media/core/css/common.css" rel="stylesheet">', PHP_EOL;
        echo '<script type="text/javascript" src="ROOT/_media/core/js/common.js"></script>', PHP_EOL;
    }


    /**
    * ECHOs one or more <link> tags for the module css
    * Uses either modules.css in the the skin css directory or the module.css in the modules media directories
    **/
    public static function modules()
    {
        $subsite = SubsiteSelector::$subsite_code;

        if (file_exists(DOCROOT . "skin/{$subsite}/css/modules.css")) {
            $ts = @filemtime(DOCROOT . "skin/{$subsite}/css/modules.css");
            if (! $ts) $ts = time();
            echo "<link href=\"ROOT/_media/skin/{$subsite}/css/modules.css?{$ts}\" rel=\"stylesheet\">", PHP_EOL;

        } else {
            $ts = time();
            foreach (Modules::getModules() as $module) {
                if (file_exists($module->getPath() . 'media/css/modules.css')) {
                    $mod = $module->getName();
                    echo "<link href=\"ROOT/_media/{$mod}/css/modules.css?{$ts}\" rel=\"stylesheet\">", PHP_EOL;
                }
            }
        }
    }


    /**
    * Return the URL for a CSS file in the skin for the current subsite.
    * The URL will have an embedded timestamp.
    *
    * Usage example:
    *   <link href="<?php echo Skin::cssUrl('layout'); ?>" rel="stylesheet">
    *
    * @param string $file
    * @param int $ts
    * @return string URL for the specified css file
    **/
    public static function cssUrl($file, $ts = null)
    {
        $subsite = SubsiteSelector::$subsite_code;

        if (!$ts) {
            $ts = @filemtime(DOCROOT . "skin/{$subsite}/css/{$file}.css");
        }

        if (! $ts) $ts = time();

        return "ROOT/_media/skin/{$subsite}/css/{$file}.css?{$ts}";
    }


    /**
    * ECHOs one or more <link> tags referring to specified CSS files.
    * Uses variable arguments, one per css file.
    * The URLs will contain embedded timestamps, making them auto-versioned
    *
    * Usage example:
    *   <?php Skin::css('reset', 'layout', 'content'); ?>
    *
    * Optionally provide an array to specify attributes, like so:
    *   <?php Skin::css('site', 'home', ['crossorigin' => 'anonymous', 'media' => 'print']); ?>
    * Will return:
    *   <link href="skin/css/site.css" rel='stylesheet' media='print'>
    *   <link href="skin/css/home.css" rel='stylesheet' media='print'>
    *
    * There isn't a guarantee that multiple tags will be ECHOed, but the order will always remain as specified.
    * If you need more control use the helper `css_url` and echo the tags yourself.
    **/
    public static function css()
    {
        $attributes = ['rel' => 'stylesheet'];
        $args = [];
        $ts = 0;

        $subsite = SubsiteSelector::$subsite_code;

        // Collect attributes or args.
        // Also calculate the oldest timestamp.
        foreach (func_get_args() as $arg) {
            if (is_array($arg)) {
                $attributes += $arg;
            }
            else {
                $args[] = $arg;
                $ts = max($ts, @filemtime(DOCROOT . "skin/{$subsite}/css/{$arg}.css"));
            }
        }
        if (! $ts) $ts = time();

        // Build the attributes.
        $attr = '';
        foreach ($attributes as $key => $value) {
            $attr .= ' ' . $key . '="' . Enc::html($value) . '"';
        }

        foreach ($args as $arg) {
            $url = self::cssUrl($arg, $ts);
            echo "<link href=\"{$url}\"{$attr}>" . PHP_EOL;
        }
    }


    /**
    * Return the URL for a JS file in the skin for the current subsite.
    * The URL will have an embedded timestamp.
    *
    * Usage example:
    *   <script src="<?php echo Skin::cssUrl('site'); ?>"></script>
    *
    * @param string $file
    * @param int $ts
    * @return string URL for the specified js file
    **/
    public static function jsUrl($file, $ts = null)
    {
        $subsite = SubsiteSelector::$subsite_code;

        if (!$ts) {
            $ts = @filemtime(DOCROOT . "skin/{$subsite}/js/{$file}.js");
        }

        if (! $ts) $ts = time();

        return "ROOT/_media/skin/{$subsite}/js/{$file}.js?{$ts}";
    }


    /**
    * ECHOs one or more <script> tags referring to specified JS files.
    * Uses variable arguments, one per css file.
    * The URLs will contain embedded timestamps, making them auto-versioned
    *
    * Usage example:
    *   <?php Skin::js('site', 'home'); ?>
    *
    * Optionally provide an array to specify attributes, like so:
    *   <?php Skin::js('site', 'home', ['crossorigin' => 'anonymous', 'defer' => '']); ?>
    * Will return:
    *   <script src="skin/js/site.js" crossorigin="anonymous" defer=""></script>
    *   <script src="skin/js/home.js" crossorigin="anonymous" defer=""></script>
    *
    * There isn't a guarantee that multiple tags will be ECHOed, but the order will always remain as specified.
    * If you need more control use the helper `js_url` and echo the tags yourself.
    **/
    public static function js()
    {
        $attributes = [];
        $args = [];
        $ts = 0;

        $subsite = SubsiteSelector::$subsite_code;

        // Collect attributes or args.
        // Also calculate the oldest timestamp.
        foreach (func_get_args() as $arg) {
            if (is_array($arg)) {
                $attributes += $arg;
            }
            else {
                $args[] = $arg;
                $ts = max($ts, @filemtime(DOCROOT . "skin/{$subsite}/js/{$arg}.js"));
            }
        }
        if (! $ts) $ts = time();

        // Build the attributes.
        $attr = '';
        foreach ($attributes as $key => $value) {
            $attr .= ' ' . $key . '="' . Enc::html($value) . '"';
        }

        foreach ($args as $arg) {
            $url = self::jsUrl($arg, $ts);
            echo "<script src=\"{$url}\"{$attr}></script>" . PHP_EOL;
        }
    }


    /**
     * Find a template within the current skin.
     *
     * @param mixed $name
     * @param mixed $extension
     * @return string
     * @throws Exception
     * @throws FileMissingException
     */
    public static function findTemplate($name, $extension)
    {
        static $cache = [];

        $key = $name . ':' . $extension;
        $hit = $cache[$key] ?? null;

        if ($hit) return $hit;

        $matches = [];

        if (!preg_match('!^(skin|sprout|modules/([^/]+))/(.+)$!', $name, $matches)) {
            throw new Exception('View files must begin with skin/, sprout/, or modules/*/');
        }

        [, $base, $module, $file] = $matches;

        if ($base === 'skin') {
            $name = 'skin/' . SubsiteSelector::$subsite_code . '/' . $file;

            $unavail = Kohana::config('sprout.unavailable');
            if (!empty($_GET['_unavailable'])) {
                $_GET['_unavailable'] = preg_replace('/[^_a-z]/', '', $_GET['_unavailable']);
                $unavail = $_GET['_unavailable'];
            }

            if ($unavail and !AdminAuth::isLoggedIn()) {
                SubsiteSelector::$subsite_code = 'unavailable';
                $name = 'skin/unavailable/' . $unavail;
            }

            $name = $name . $extension;
            $path = DOCROOT . $name;

            if (!file_exists($path)) {
                throw new FileMissingException("View file missing (app): {$name}");
            }

            $cache[$key] = $path;
            return $path;
        }

        if ($base === 'sprout') {
            if (substr($file, 0, 6) != 'views/') {
                $file = 'views/' . $file;
            }

            $name = $file . $extension;
            $path = APPPATH . $name;

            if (!file_exists($path)) {
                throw new FileMissingException("View file missing (core): {$name}");
            }

            $cache[$key] = $path;
            return $path;
        }

        if (strpos($base, 'modules') === 0) {
            if (substr($file, 0, 6) != 'views/') {
                $file = 'views/' . $file;
            }

            $module = Modules::getModule($module);
            if (!$module) {
                throw new FileMissingException("View file missing (app): {$name}");
            }

            $path = $module->getPath() . $file . $extension;
            if (!file_exists($path)) {
                throw new FileMissingException("View file missing (app): {$name}");
            }

            $cache[$key] = $path;
            return $path;
        }

        // Just to be sure.
        throw new Exception('View files must begin with skin/, sprout/, or modules/*/');
    }
}

