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

use BootstrapConfig;
use Exception;
use Kohana;
use Kohana_404_Exception;
use Kohana_Exception;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\File;
use Sprout\Helpers\Media;
use Sprout\Helpers\Router;
use Throwable;

/**
 * Serving media assets.
 *
 * These are not files, {@see FileController}. These are JS/CSS assets
 * typically loaded by the {@see Needs} helper.
 */
class MediaController extends Controller
{

    /**
     * Serve the file immediately.
     *
     * @param string $resource
     * @param string[] $segments
     * @return never
     * @throws Kohana_404_Exception
     */
    public function serve(...$segments)
    {
        $resource = array_shift($segments);
        switch ($resource) {
            case 'core':
                $root = COREPATH . 'media/';
                break;

            case 'sprout':
                $root = APPPATH . 'media/';
                break;

            case 'skin':
                $root = DOCROOT . 'skin/';
                break;

            // It's a module (assumed).
            default:
                $root = DOCROOT . "modules/{$resource}/media/";
                break;
        }

        $url = implode('/', $segments);
        $path = $root . $url;

        if (!is_file($path)) {
            throw new Kohana_404_Exception($url);
        }

        // Shush. Drop any existing output.
        Kohana::closeBuffers(false);

        // Caching for 7 days.
        header('Cache-Control: cache, store, max-age=604800, must-revalidate');

        // File types.
        $mimetype = File::mimetypeExtended($path);
        if ($mimetype) {
            header("Content-Type: {$mimetype}");
        }

        // For debugging.
        header('X-Media-Hit: true');

        // Dump out the file.
        $ok = readfile($path);

        if ($ok === false) {
            throw new Exception('Failed to read file: ' . $url);
        }

        // Ok, really shush now.
        // We don't want errors bleeding into the asset bodies.
        set_exception_handler(null);
        ini_set('display_errors', '0');

        // Now copy it so this file doesn't hit the app again. This effectively
        // 'shadows' the file in the same path as this controller. The
        // web server (nginx, apache) should find and serve it before deferring
        // to the PHP app.
        if (BootstrapConfig::ENABLE_MEDIA_CACHE) {
            try {
                $dest = WEBROOT . Router::$current_uri;
                $dir = dirname($dest);

                if (!file_exists($dir)) {
                    $ok = mkdir($dir, 0777, true);
                    if (!$ok) throw new Exception("Failed to create directory: {$dir}");
                }

                if (!is_dir($dir)) {
                    throw new Exception("Target directory exists but isn't a directory: {$dir}");
                }

                $ok = copy($path, $dest);
                if (!$ok) throw new Exception("Failed to copy file: {$path} to {$dest}");
            }
            catch (Throwable $ex) {
                Kohana::logException($ex, true);
            }
        }

        exit;
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

}
