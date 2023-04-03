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

/**
 * Bridge the CLI-server environment with Kohana/Sprout.
 *
 * DO NOT USE THIS IN PRODUCTION.
 *
 * @package Sprout\Helpers
 */
class CliServer
{

    const REWRITES = [
        '!^media/(.+)!' => '_media/core/$1',
        '!^media-[0-9]+/(.+)!' => '_media/core/$1',
        '!^sprout/media/(.+)!' => '_media/sprout/$1',
        '!^modules/(.+)/media/(.+)!' => '_media/$1/$2',
        '!^skin/(.+)!' => '_media/skin/$1',
        '!^skin-[0-9]+/(.+)!' => '_media/skin/$1',
    ];


    /**
     * Perform rewrites.
     *
     * This should match those in the htaccess/nginx configurations.
     *
     * @param string $path
     * @return null|string the rewritten path, or null if no match
     */
    public static function rewrites(string $path): ?string
    {
        foreach (self::REWRITES as $pattern => $replacement) {
            $count = 0;
            $rewrite = preg_replace($pattern, $replacement, $path, 1, $count);

            if ($count > 0) {
                error_log("Rewrite: $path -> {$rewrite}");
                return $rewrite;
            }
        }

        return null;
    }


    /**
     * Serve things.
     *
     * @return bool
     *  - If true, continue the bootstrap (load kohana/sprout)
     *  - otherwise false - do nothing and let the CLI-server handle the file request.
     */
    public static function serve(): bool
    {
        if (PHP_SAPI !== 'cli-server') {
            die('CLI-server cannot be used outside of CLI-server mode.');
        }

        if (IN_PRODUCTION) {
            die('What on Earth are you doing? No means no!');
        }

        // Kohana bootstrap hasn't happened yet, so Request/Router are
        // empty at this point. Gotta parse it all ourselves.
        $url = parse_url($_SERVER['REQUEST_URI']);
        $_SERVER['QUERY_STRING'] = $url['query'] ?? '';
        $path = trim($url['path'], '/');

        // Perform rewrites.
        $path = self::rewrites($path) ?? $path;

        // Serve it statically.
        if (is_file(DOCROOT . $path)) {
            return false;
        }

        // Prep a URL for kohana.
        // Like, if we did any rewrites.
        $_GET['kohana_uri'] = $path;

        return true;
    }
}
