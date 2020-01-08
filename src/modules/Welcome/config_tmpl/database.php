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

/**
* Database connection settings.
*
* Each array is a separate group, which can be connected to independently.
*
* The standard connection used by {@see Pdb} is the 'default' group, but
* the method {@see Pdb::connect} can be used to connect to other groups
*
* Group Options:
*  connection      Array of connection specific parameters:
*       type       Only supported value is 'mysql'
*       host       Hostname
*       user       Username
*       pass       Password
*       port       If non-empty, specifies a non-standard port
*       database   Database name
*  character_set   Database character set
**/


if (IN_PRODUCTION) {
    // Live server config
    $config['default'] = [
        'connection' => [
            'type' => 'mysql',
            'user' => '{{PROD-USER}}',
            'database' => '{{PROD-DATABASE}}',
            'host' => '{{PROD-HOST}}',
            'port' => FALSE,
        ],
        'character_set' => 'utf8',
    ];

    // Rather than entering the PRODUCTION database password direct in
    // the config (which would then be saved in repo history and could
    // accidently become public), it's much better to include this in
    // a separate file, preferably outside of DOCROOT.
    //
    // Example file content:
    //     <?php
    //     $config['default']['connection']['pass'] = 'abcd1234';
    //
    // The example path below would be used if the file is a sibling
    // of the main public_html directory
    //
    if (!file_exists(DOCROOT . '../database.config.php')) {
        throw new Exception('Missing database password config file');
    }

    require DOCROOT . '../database.config.php';

    // A unique random key for this site
    $config['server_key'] = '{{SERVER-KEY}}';

} else {
    // Test server config
    $config['default'] = [
        'connection' => [
            'type' => 'mysql',
            'user' => '{{TEST-USER}}',
            'pass' => '{{TEST-PASS}}',
            'database' => '{{TEST-DATABASE}}',
            'host' => '{{TEST-HOST}}',
            'port' => FALSE,
        ],
        'character_set' => 'utf8',
    ];

    // This key is not secure, so it must not be used in production environments
    $config['server_key'] = 'NOT SECURE';
}
