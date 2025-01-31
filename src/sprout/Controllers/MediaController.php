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

use Kohana;
use Kohana_404_Exception;
use Sprout\Exceptions\MediaException;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\File;
use Sprout\Helpers\Media;
use Sprout\Helpers\Modules;
use Sprout\Helpers\Subsites;
use Sprout\Helpers\SubsiteSelector;
use Sprout\Helpers\Url;
use Throwable;

/**
 * Serving media assets.
 *
 * These are not files, {@see FileController}. These are JS/CSS assets
 * typically loaded by the {@see Needs} and {@see Media} helpers.
 */
class MediaController extends Controller
{

    /**
     * Serve
     *
     * This exists to serve media files not already processed by the
     * {@see Media} helper, such as relative urls.
     *
     * `_media/checksum/section/file`
     *
     * @return never serve the file
     */
    public function generate($checksum, $section, ...$segments)
    {
        // Resolve the root path.
        $root = Media::getRoot($section, false);
        $path = $root . implode('/', $segments);

        if (!file_exists($path)) {
            throw new Kohana_404_Exception();
        }

        $actual = Media::getChecksum($section);

        if ($actual === null) {
            throw new MediaException('Failed to read file: ' . $path);
        }

        // We don't want the browser thinking this file belongs with the
        // wrong checksum.
        if ($checksum !== $actual) {
            $url = Media::generateUrl($path);
            Url::redirect($url);
        }

        // Shush. Drop any existing output.
        Kohana::closeBuffers(false);
        set_exception_handler(null);

        // Caching for 7 days.
        header('Cache-Control: cache, store, max-age=604800, must-revalidate');

        $mimetype = File::mimetypeExtended($path);
        if ($mimetype) {
            header("Content-Type: {$mimetype}");
        }

        // For debugging.
        header('X-Media-Hit: true');

        // Dump out the file.
        $ok = readfile($path);

        if ($ok === false) {
            throw new MediaException('Failed to read file: ' . $path);
        }

        // Do the dirty.
        try {
            Media::generateUrl($path);
        } catch (Throwable $ex) {
            Kohana::logException($ex, true);
        }
    }


    /**
     *
     *  `_media/section/path/to/file`
     *
     * @return never redirect to generated checksum URL
     */
    public function resolve(...$segments)
    {
        try {
            $section = array_shift($segments);
            $root = Media::getRoot($section, false);

        } catch (MediaException $ex) {
            array_unshift($segments, $section);
            $root = Media::getRoot('core');
        }

        try {
            $path = $root . implode('/', $segments);
            $url = Media::generateUrl($path);
            Url::redirect($url);

        } catch (MediaException $ex) {
            throw new Kohana_404_Exception();
        }
    }


    /**
     * Requests to the old media endpoints.
     *
     * - `media/` (core)
     * - `sprout/media/`
     * - `modules/XX/media/`
     * - `skin/`
     *
     * @param mixed ...$segments
     * @return never redirect to generated checksum URL
     */
    public function compat(...$segments)
    {
        try {
            $section = array_shift($segments);

            if ($section == 'media') {
                $section = 'core';
            }

            if ($segments[0] == 'media') {
                array_shift($segments);
            }

            $name = implode('/', $segments);

            $root = Media::getRoot($section, false);
            $url = Media::generateUrl($root . $name);
            Url::redirect($url);

        } catch (MediaException $ex) {
            throw new Kohana_404_Exception();
        }
    }


    /**
     * Clean out the media cache.
     *
     * @return void
     */
    public function clean()
    {
        if (PHP_SAPI != 'cli') {
            AdminAuth::checkLogin();
        }

        header('content-type: text/plain');
        Media::clean();
    }


    public function process($skin = null)
    {
        if (PHP_SAPI != 'cli') {
            AdminAuth::checkLogin();
        }

        header('content-type: text/plain');

        if ($skin === null) {
            $subsite = Subsites::getDefaultSubsite();
        } else {
            $subsite = Subsites::getSubsiteByCode($skin);
        }

        if ($subsite === null) {
            echo "Subsite not found: {$skin}\n";
            exit(1);
        }

        SubsiteSelector::setSubsite($subsite);
        echo "Selected skin: {$subsite['code']}\n";
        echo "--------------------------------\n";

        $sections = [
            'core',
            'sprout',
            'skin',
        ];

        foreach (Modules::getModules() as $module) {
            $sections[] = $module->getName();
        }

        foreach ($sections as $section) {
            $checksum = Media::generateChecksum($section, true);
            echo sprintf("Processed: %-12s %s\n", $section, $checksum);
        }
    }

}
