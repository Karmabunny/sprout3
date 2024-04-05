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
use BootstrapConfig;

/**
 * Helpers for resolving and loading media files.
 *
 * These are resource files from modules, skin or sprout. These are files are
 * not necessarily accessible from the web root because they're located
 * in the src/ or vendor/ folders.
 *
 * Resource files are expected to live in the `media/` folder of a
 * module/skin/sprout. Inside this files are grouped into sub-folders by their
 * type (js, css, images).
 *
 * For public access, files are served by the {@see MediaController}. This is
 * accessible from the `ROOT/_media` prefix.
 *
 * When accessing files, the format is like: `{section}/{file}`. The group is
 * determined automatically by the extension. Skin files will include the
 * subsite code automatically.
 *
 * Note that sprout has _two_ media folders. This is simply a legacy
 * holdover - there's no true distinction between the two.
 *
 * @see MediaController
 */
class Media
{

    /**
     * Get the root file path for a section.
     *
     * This is a file path, not a URL. Instead prefix `ROOT/_media`.
     *
     * @param string $section
     * @return string
     */
    public static function getRoot(string $section): string
    {
        if ($section === 'core') {
            return COREPATH . 'media/';
        }

        if ($section === 'sprout') {
            return APPPATH . 'media/';
        }

        if ($section === 'skin') {
            $subsite = SubsiteSelector::$subsite_code;
            return DOCROOT . "skin/{$subsite}/media/";
        }

        if ($module = Modules::getModule($section)) {
            return $module->getPath() . 'media/';
        }

        throw new Exception("Module not found: '{$section}'");
    }


    /**
     * The file group for a given file name.
     *
     * This is based on the file extension.
     *
     * @param string $name
     * @return string
     */
    public static function getGroup(string $name): string
    {
        $matches = [];
        if (preg_match('!\.(css|js)$!', $name, $matches)) {
            return $matches[1];
        }

        // Just assume.
        return 'images';
    }


    /**
     * Get the file path of a resource.
     *
     * @param string $name like `{section}/{file}.js|css|etc`
     * @return string absolute path
     */
    public static function path(string $name): string
    {
        [$section, $name] = explode('/', $name, 2) + [null, null];

        // Assume it's a core file.
        if (!$name) {
            $section = 'core';
            $name = $section;
        }

        $root = self::getRoot($section);
        $group = self::getGroup($name);

        return "{$root}/{$group}/{$name}";
    }


    /**
     * Get the URL for a resource.
     *
     * @param string $name like `{section}/{file}.js|css|etc`
     * @param int|null $ts timestamp override, otherwise file mtime
     * @return string a relative URL (without ROOT/)
     */
    public static function url(string $name, int $ts = null): string
    {
        [$section, $name] = explode('/', $name, 2) + [null, null];

        // Assume it's a core file.
        if (!$name) {
            $section = 'core';
            $name = $section;
        }

        $root = self::getRoot($section);
        $group = self::getGroup($name);

        $mtime = $ts ?: @filemtime("{$root}/{$group}/{$name}") ?: time();
        $url = "_media/{$section}/{$group}/{$name}?{$mtime}";

        return $url;
    }


    /**
     * Get a media tag for a file.
     *
     * This will return a <script> or <link> tag for the given file based on
     * the file extension.
     *
     * @param string $name section/file.js|css
     * @param array $extra_attrs
     * @return string
     */
    public static function tag(string $name, array $extra_attrs = []): string
    {
        $ts = $extra_attrs['_ts'] ?? null;
        unset($extra_attrs['_ts']);

        $url = 'ROOT/' . self::url($name, $ts);
        $group = self::getGroup($name);

        if ($group === 'js') {
            $extra_attrs['src'] = $url;
            $extra_attrs['type'] ??= 'text/javascript';

            return '<script ' . Html::attributes($extra_attrs) . '></script>';
        }

        if ($group === 'css') {
            $extra_attrs['href'] = $url;
            $extra_attrs['type'] ??= 'text/css';
            $extra_attrs['rel'] ??= 'stylesheet';

            return '<link ' . Html::attributes($extra_attrs) . '>';
        }

        $extra_attrs['src'] = $url;
        return '<img ' . Html::attributes($extra_attrs) . '>';
    }


    /**
     * Clean out the media cache.
     *
     * @param bool $act
     * @return void
     */
    public static function clean($act = true)
    {
        $dir = WEBROOT . '_media/';
        $children = scandir($dir);

        echo !$act ? 'Dry run...' : 'Clearing...', "\n";

        $count = 0;

        foreach ($children as $item) {
            $path = $dir . $item;

            if (!is_dir($path)) continue;
            if (strpos($item, '.') === 0) continue;

            echo $path, "\n";
            if ($act) {
                exec('rm -rf ' . escapeshellarg($path));
            }

            $count++;
        }

        echo "Enabled: " . json_encode(BootstrapConfig::ENABLE_MEDIA_CACHE) . "\n";
        echo "Clean: {$count}\n";
    }
}
