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
        echo '<link href="ROOT/media/css/common.css" rel="stylesheet">', PHP_EOL;
        echo '<script type="text/javascript" src="ROOT/media/js/common.js"></script>', PHP_EOL;
    }


    /**
    * ECHOs one or more <link> tags for the module css
    * Uses either modules.css in the the skin css directory or the module.css in the modules media directories
    **/
    public static function modules()
    {
        if (file_exists(DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/css/modules.css')) {
            $ts = @filemtime(DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/css/modules.css');
            if (! $ts) $ts = time();
            echo '<link href="ROOT/skin/', SubsiteSelector::$subsite_code, '/css/modules.css" rel="stylesheet">', PHP_EOL;

        } else {
            $ts = time();
            foreach (Register::getModuleDirs() as $module_path) {
                if (file_exists($module_path . '/media/css/modules.css')) {
                    $mod = basename($module_path);
                    echo '<link href="ROOT/media-' . $ts . '/' . $mod . '/css/modules.css" rel="stylesheet">', PHP_EOL;
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
    * @return string URL for the specified css file
    **/
    public static function cssUrl($file)
    {
        $ts = @filemtime(DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/css/' . $file . '.css');
        if (! $ts) $ts = time();

        return 'ROOT/skin-' . $ts . '/' . SubsiteSelector::$subsite_code . '/css/' . $file . '.css';
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
    *   <link href="skin-ts/skin/css/site.css" rel='stylesheet' media='print'>
    *   <link href="skin-ts/skin/css/home.css" rel='stylesheet' media='print'>
    *
    * There isn't a guarantee that multiple tags will be ECHOed, but the order will always remain as specified.
    * If you need more control use the helper `css_url` and echo the tags yourself.
    **/
    public static function css()
    {
        $attributes = ['rel' => 'stylesheet'];
        $args = [];
        $ts = 0;

        // Collect attributes or args.
        // Also calculate the oldest timestamp.
        foreach (func_get_args() as $arg) {
            if (is_array($arg)) {
                $attributes += $arg;
            }
            else {
                $args[] = $arg;
                $ts = max($ts, @filemtime(DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/css/' . $arg . '.css'));
            }
        }
        if (! $ts) $ts = time();

        // Build the attributes.
        $attr = '';
        foreach ($attributes as $key => $value) {
            $attr .= ' ' . $key . '="' . Enc::html($value) . '"';
        }

        foreach ($args as $arg) {
            echo '<link href="ROOT/skin-' . $ts . '/' . SubsiteSelector::$subsite_code . '/css/' . $arg . '.css"' . $attr . '>' . PHP_EOL;
        }
    }


    /**
    * Return the URL for a JS file in the skin for the current subsite.
    * The URL will have an embedded timestamp.
    *
    * Usage example:
    *   <script src="<?php echo Skin::cssUrl('site'); ?>"></script>
    *
    * @return string URL for the specified js file
    **/
    public static function jsUrl($file)
    {
        $ts = @filemtime(DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/js/' . $file . '.js');
        if (! $ts) $ts = time();

        return 'ROOT/skin-' . $ts . '/' . SubsiteSelector::$subsite_code . '/js/' . $file . '.js';
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
    *   <script src="skin-ts/skin/js/site.js" crossorigin="anonymous" defer=""></script>
    *   <script src="skin-ts/skin/js/home.js" crossorigin="anonymous" defer=""></script>
    *
    * There isn't a guarantee that multiple tags will be ECHOed, but the order will always remain as specified.
    * If you need more control use the helper `js_url` and echo the tags yourself.
    **/
    public static function js()
    {
        $attributes = [];
        $args = [];
        $ts = 0;

        // Collect attributes or args.
        // Also calculate the oldest timestamp.
        foreach (func_get_args() as $arg) {
            if (is_array($arg)) {
                $attributes += $arg;
            }
            else {
                $args[] = $arg;
                $ts = max($ts, @filemtime(DOCROOT . 'skin/' . SubsiteSelector::$subsite_code . '/js/' . $arg . '.js'));
            }
        }
        if (! $ts) $ts = time();

        // Build the attributes.
        $attr = '';
        foreach ($attributes as $key => $value) {
            $attr .= ' ' . $key . '="' . Enc::html($value) . '"';
        }

        foreach ($args as $arg) {
            echo '<script src="ROOT/skin-' . $ts . '/' . SubsiteSelector::$subsite_code . '/js/' . $arg . '.js"' . $attr . '></script>' . PHP_EOL;
        }
    }


    /**
     * Find a template within the current skin.
     *
     * @param mixed $name
     * @param mixed $extension
     * @return string
     * @throws Exception
     */
    public static function findTemplate($name, $extension)
    {
        $matches = [];

        if (!preg_match('!^(skin|sprout|modules)/(.+)$!', $name, $matches)) {
            throw new Exception('View files must begin with skin/, sprout/, or modules/*/');
        }

        [, $base, $file] = $matches;

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

            if (!file_exists(DOCROOT . $name)) {
                throw new FileMissingException("View file missing (app): {$name}");
            }

            return DOCROOT . $name;
        }

        if ($base === 'sprout') {
            if (substr($file, 0, 6) != 'views/') {
                $file = 'views/' . $file;
            }

            $name = $file . $extension;

            if (!file_exists(APPPATH . $name)) {
                throw new FileMissingException("View file missing (core): {$name}");
            }

            return APPPATH . $name;
        }

        if ($base === 'modules') {
            $module = dirname($file, 2);

            if (substr($file, 0, 6) != 'views/') {
                $module = dirname($file, 1);
                $file = 'views/' . basename($file);
            }

            $name = 'modules/' . $module . '/' . $file . $extension;

            if (!file_exists(DOCROOT . $name)) {
                throw new FileMissingException("View file missing (app): {$name}");
            }

            return DOCROOT . $name;
        }

        // Just to be sure.
        throw new Exception('View files must begin with skin/, sprout/, or modules/*/');
    }
}

