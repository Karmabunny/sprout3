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
     * Serve a file.
     *
     * Generated files should theoretically exist on the filesystem and be
     * served directly from the webserver. However there are a fair few
     * scenarios where this does not happen.
     *
     * - Empty caches on deploy
     * - Stale documents in browsers (these may trigger requests to old checksums)
     * - Stale middleware caches
     * - Relative URLs from other media
     *
     * `_media/checksum/section/file`
     *
     * @return never serve the file
     */
    public function generate($checksum, ...$segments)
    {
        $file = implode('/', $segments);
        $media = Media::parse($file);

        if (!file_exists($media->getPath())) {
            throw new Kohana_404_Exception();
        }

        $actual = $media->getChecksum();

        if ($actual === null) {
            throw new MediaException('Failed to read file: ' . $file);
        }

        // We don't want the browser thinking this file belongs with the
        // wrong checksum. Generate the correct checksum asset + redirect to it.
        if ($checksum !== $actual) {
            $url = $media->generateUrl();
            Url::redirect($url);
        }

        // Shush. Drop any existing output.
        Kohana::closeBuffers(false);
        set_exception_handler(null);

        // Caching for 7 days.
        header('Cache-Control: cache, store, max-age=604800, must-revalidate');

        $mimetype = File::mimetypeExtended($media->getPath());
        if ($mimetype) {
            header("Content-Type: {$mimetype}");
        }

        // For debugging.
        header('X-Media-Hit: true');

        // Dump out the file immediately.
        $ok = readfile($media->getPath());

        if ($ok === false) {
            throw new MediaException("Failed to read file: '{$media->name}' ({$media->section})");
        }

        // Generate the checksum asset for later.
        try {
            $media->generateUrl();
        } catch (Throwable $ex) {
            Kohana::logException($ex, true);
        }
    }


    /**
     * Find an asset and redirect to its generated URL.
     *
     * This serves mostly as backwards compatibility.
     *
     * `_media/section/path/to/file`
     *
     * @return never redirect to generated checksum URL
     */
    public function resolve(...$segments)
    {
        try {
            // Backwards compat for naked modules.
            if (!in_array($segments[0], ['core', 'sprout', 'skin', 'modules'])) {
                array_unshift($segments, 'modules');
            }

            $file = implode('/', $segments);
            $media = Media::parse($file);

            $url = $media->generateUrl();
            Url::redirect($url);

        } catch (MediaException $ex) {
            Kohana::logException($ex);
            throw new Kohana_404_Exception();
        }
    }


    /**
     * Requests to the old media endpoints.
     *
     * - `media/` (core)
     * - `sprout/media/`
     * - `modules/{names}/media/`
     * - `skin/{name}/`
     *
     * @param mixed ...$segments
     * @return never redirect to generated checksum URL
     */
    public function compat($section, ...$segments)
    {
        if ($section === 'media') {
            $section = 'core';

        } else if ($section === 'sprout') {
            $section = 'sprout';

        } else if ($section === 'skin') {
            $name = array_shift($segments);
            $section = 'skin/' . $name;

        } else {
            $section = 'modules/' . $section;
        }

        // Tidy up.
        if ($segments[0] == 'media') {
            array_shift($segments);
        }

        $file = $section . '/' . implode('/', $segments);

        try {
            $media = Media::parse($file);
            $url = $media->generateUrl();
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


    /**
     * Copy all asset files into generated paths.
     *
     * @param string|null $skin specify null for all skins
     */
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

        $paths = [
            ['core', COREPATH . 'media/'],
            ['sprout', APPPATH . 'media/'],
            ['skin/' . $subsite['code'], DOCROOT . 'skin/' . $subsite['code']],
        ];

        foreach (Modules::getModules() as $module) {
            $paths[] = [
                'modules/' . $module->getName(),
                $module->getPath() . 'media/',
            ];
        }

        Media::clean('silent');

        foreach ($paths as [$name, $path]) {
            $checksum = Media::generateChecksum($path, true);
            $checksum ??= '--';
            echo sprintf("Processed: %-12s %s\n", $name, $checksum);
        }
    }

}
