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
 * Force a default protocol to be used by the site. If no site_protocol is
 * specified, then the current protocol is used, or when possible, only an
 * absolute path (with no protocol/domain) is used.
 */
$config['site_protocol'] = '';

/**
 * Default ISO3 country code for phone numbers and other geo-specific things
 *
 * If not set, it will default to 'AUS'
 */
$config['default_country_code'] = 'AUS';

/**
 * Common country codes for phone numbers or other forms
 *
 * This is a list of country codes that are commonly used in Australia
 * and can be amended for your use case
 *
 * Make this empty to render phone codes as a single level list
 */
$config['common_phone_codes'] = [
    '61', // Australia
    '86', // China
    '91', // India
    '44', // Ireland
    '64', // New Zealand
    '92', // Pakistan
    '27', // South Africa
    '44', // United Kingdom
    '1', // Canada + US
];

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

