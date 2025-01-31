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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Sprout\Exceptions\MediaException;

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
     * @return string a directory path
     * @throws MediaException
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
            return DOCROOT . "skin/{$subsite}/";
        }

        if ($module = Modules::getModule($section)) {
            return $module->getPath() . 'media/';
        }

        throw new MediaException("Module not found: '{$section}'");
    }


    /**
     * Determine the section from a media path.
     *
     * @param string $path full path
     * @return string core|sprout|skin|{module}
     * @throws MediaException
     */
    public static function getSection(string $path): string
    {
        if (strpos($path, COREPATH . 'media/') === 0) {
            return 'core';
        }

        if (strpos($path, APPPATH . 'media/') === 0) {
            return 'sprout';
        }

        if (strpos($path, DOCROOT . 'skin/') === 0) {
            return 'skin';
        }

        if ($module = Modules::getModuleByPath($path)) {
            return $module->getName();
        }

        throw new MediaException("Unknown section root: '{$path}'");
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
     * @param string $name like `section/path/to/file`
     * @return string absolute path
     */
    public static function path(string $name): string
    {
        [$section, $file] = explode('/', $name, 2) + [null, null];

        $root = self::getRoot($section);

        // Include the skin if the caller left it out.
        if (
            $section === 'skin'
            and strpos($file, SubsiteSelector::$subsite_code) !== 0
        ) {
            $file = SubsiteSelector::$subsite_code . '/' . $file;
        }

        return "{$root}{$file}";
    }


    /**
     * Get the URL for a resource.
     *
     * @param string $name like `section/path/to/file`
     * @param bool $generate
     * @return string a relative URL (without ROOT/)
     */
    public static function url(string $name, bool $generate = true): string
    {
        if ($generate) {
            $path = self::path($name);
            return self::generateUrl($path);
        }

        [$section, $file] = explode('/', $name, 2) + [null, null];

        $root = self::getRoot($section);

        // Include the skin if the caller left it out.
        if (
            $section === 'skin'
            and strpos($file, SubsiteSelector::$subsite_code) !== 0
        ) {
            $file = SubsiteSelector::$subsite_code . '/' . $file;
        }

        $mtime = @filemtime("{$root}{$file}") ?: time();
        $url = "_media/{$section}/{$file}?{$mtime}";

        return $url;
    }


    /**
     * Generate a URL for a resource.
     *
     * @param string $path full path to a file
     * @return string media URL like `_media/{checksum}/{section}/{file}`
     * @throws MediaException
     */
    public static function generateUrl(string $path): string
    {
        $cache = Cache::instance('media');

        $section = self::getSection($path);

        // We always check the short cache.
        $checksum = self::$checksums[$section] ?? null;
        $generated = false;

        // Now check the long cache, if enabled.
        if (
            $checksum === null
            and defined('BootstrapConfig::ENABLE_MEDIA_CACHE')
            and constant('BootstrapConfig::ENABLE_MEDIA_CACHE')
        ) {
            $checksum = $cache->get($section);
        }

        // Generate it.
        if ($checksum === null) {
            $checksum = self::generateChecksum($section);
            $generated = true;
        }

        // Double check it.
        if (!is_dir(WEBROOT . "_media/{$checksum}")) {
            $checksum = self::generateChecksum($section);
            $generated = true;
        }

        if ($checksum === null) {
            throw new MediaException("Failed to generate checksum for: {$section}");
        }

        if ($generated) {
            $cache->set($section, $checksum);
        }

        // This structure here is reasonably important.
        // - checksum: invalidates browser caches
        // - root: identifies the source of the file
        // - file: a full path to maintain relative linking
        $root = self::getRoot($section, false);
        $file = substr($path, strlen($root));

        $url = "_media/{$checksum}/{$section}/" . $file;
        $dest = WEBROOT . $url;

        if (!file_exists($dest)) {
            $dir = dirname($dest);

            // if (exists) mkdir() is not atomic. Another thread can always                       ..
            // beat us to it. Do and ask for forgiveness later.
            @mkdir($dir, 0777, true);

            if (!is_dir($dir)) {
                throw new MediaException("Target directory is missing: {$dir}");
            }

            $ok = copy($path, $dest);

            if (!$ok) {
                throw new MediaException("Failed to copy file: {$path} to {$dest}");
            }
        }

        return $url;
    }


    /**
     *
     * @param string $section core | sprout | skin/{name} | module/{name}
     * @param bool $copy_files
     * @return string|null
     */
    public static function generateChecksum(string $section, bool $copy_files = false): ?string
    {
        $root = self::getRoot($section);

        if (!is_dir($root)) {
            return null;
        }

        $paths = [];
        $checksum = '';

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if (!$file->isFile()) continue;
            $path = $file->getPathname();

            $paths[] = $path;
            $checksum .= '.' . sha1_file($path);
        }

        $checksum = sha1($checksum);
        $checksum = substr($checksum, 0, 8);

        // Now copy all the media files for this section.
        // This helps retains relative paths.
        if ($copy_files) {
            foreach ($paths as $path) {
                $file = substr($path, strlen($root));
                $dest = WEBROOT . "_media/{$checksum}/{$section}/" . $file;

                $dir = dirname($dest);

                @mkdir($dir, 0777, true);
                copy($path, $dest);
            }
        }

        return $checksum;
    }


    /**
     * Get a media tag for a file.
     *
     * This will return a <script> or <link> tag for the given file based on
     * the file extension.
     *
     * @param string $file section/path/to/file
     * @param array $extra_attrs
     * @return string
     */
    public static function tag(string $file, array $extra_attrs = []): string
    {
        unset($extra_attrs['_ts']);

        $url = 'ROOT/' . self::url($file);
        $group = self::getGroup($file);

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
     * @param bool|string $act act|dry|silent
     * @return void
     */
    public static function clean($act = true)
    {
        $dir = WEBROOT . '_media/';
        $children = is_dir($dir) ? scandir($dir) : [];

        if (is_bool($act)) {
            $act = $act ? 'act' : 'dry';
        }

        $log = function($message) use ($act) {
            if ($act == 'silent') return;
            echo $message, "\n";
        };

        $log($act == 'dry' ? 'Dry run...' : 'Clearing...');

        $count = 0;

        foreach ($children as $item) {
            $path = $dir . $item;

            if (!is_dir($path)) continue;
            if (strpos($item, '.') === 0) continue;

            $log($path);

            if ($act != 'dry') {
                exec('rm -rf ' . escapeshellarg($path));
            }

            $count++;
        }

        if ($act) {
            $cache = Cache::instance('media');
            $cache->deleteAll();
        }

        $log("Clean: {$count}");
    }
}
