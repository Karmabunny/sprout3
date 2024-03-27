<?php
/*
 * kate: tab-width 4; indent-width 4; space-indent on; word-wrap off; word-wrap-column 120;
 * :tabSize=4:indentSize=4:noTabs=true:wrap=false:maxLineLen=120:mode=php:
 *
 * Copyright (C) 2016 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

/**
 * Base config file used by Kohana. Won't need changing usually.
 * @package Kohana
 */

/**
 * Some config options are a bit more site-specific, so are stored in the root config directory.
 * Modules are also loaded from that file.
 */
require_once DOCROOT . 'config/config.php';

/**
 * Force a default protocol to be used by the site. If no site_protocol is
 * specified, then the current protocol is used, or when possible, only an
 * absolute path (with no protocol/domain) is used.
 */
$config['site_protocol'] = '';

/**
 * Name of the front controller for this application. Default: index.php
 *
 * This can be removed by using URL rewriting.
 */
if (PHP_SAPI === 'cgi') {
    $config['index_page'] = 'index.php';
} else {
    $config['index_page'] = '';
}

/**
 * Fake file extension that will be added to all generated URLs. Example: .html
 */
$config['url_suffix'] = '';

/**
 * Length of time of the internal cache in seconds. 0 or FALSE means no caching.
 * The internal cache stores file paths and config entries across requests and
 * can give significant speed improvements at the expense of delayed updating.
 */
$config['internal_cache'] = FALSE;

/**
 * Internal cache directory.
 */
$config['internal_cache_path'] = STORAGE_PATH . 'cache/';

/**
 * Enable or disable gzip output compression. This can dramatically decrease
 * server bandwidth usage, at the cost of slightly higher CPU usage. Set to
 * the compression level (1-9) that you want to use, or FALSE to disable.
 *
 * Do not enable this option if you are using output compression in php.ini!
 */
$config['output_compression'] = FALSE;

/**
 * Enable or disable global XSS filtering of GET, POST, and SERVER data. This
 * option also accepts a string to specify a specific XSS filtering tool.
 */
$config['global_xss_filtering'] = FALSE;

/**
 * Enable or disable displaying of Kohana error pages. This will not affect
 * logging. Turning this off will disable ALL error pages.
 */
$config['display_errors'] = TRUE;

/**
 * Configure the router component.
 *
 * {@see \karmabunny\router\RouterConfig} for more.
 */
$config['router'] = [
    'case_insensitive' => true,
    'extract' => 'attributes|convert|prefixes|nested',
    'mode' => 'regex',
];


/**
 * The list of tags that can be added/used by the custom head tags system
 */
$config['custom_head_tags'] = [
    'available_list' => [
        'meta' => [
            'name' => [
                'application-name' => [
                    'content' => 'text',
                ],
                'author' => [
                    'content',
                ],
                // 'description' => [
                //     'content',
                // ],
                'generator' => [
                    'content',
                ],
                'keywords' => [
                    'content',
                ],
                'viewport' => [
                    'content',
                ],
                'robots' => [
                    'content',
                ],
                'googlebot' => [
                    'content',
                ],
            ],
        ],
        'link' => [
            'rel' => [
                'alternate' => [
                    'type',
                    'title',
                    'href',
                    'hreflang',
                    'media',
                ],
                'canonical' => [
                    'href',
                ],
                // 'icon' => [
                //     'href',
                //     'sizes',
                // ],
                'stylesheet' => [
                    'href',
                    'type',
                    'media',
                ],
                'prev' => [
                    'href',
                ],
                'next' => [
                    'href',
                ],
                'amphtml' => [
                    'href',
                ],

            ],
        ],
        // Note that for scripts, the "content" is rendered as the script itself, not inside a "content" attribute
        'script' => [
            'type' => [
                'application/ld+json' => [
                    'content',
                ],
            ],
        ],
    ],
];

/**
 * Sprout version is in another file too.
 */
require_once APPPATH . '/version.php';

