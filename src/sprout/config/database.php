<?php
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
            'user' => ' -- username -- ',
            'database' => ' -- database -- ',
            'host' => getenv('SITES_DB_HOSTNAME') ? getenv('SITES_DB_HOSTNAME') : 'localhost',
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
    if (file_exists(DOCROOT . '../database.config.php')) {
        require DOCROOT . '../database.config.php';
    }

    // A unique random key for this site
    $config['server_key'] = '';

} else {
    // Test server config
    $config['default'] = [
        'connection' => [
            'type' => 'mysql',
            'user' => ' -- username -- ',
            'pass' => ' -- password -- ',
            'database' => ' -- database --',
            'host' => getenv('SITES_DB_HOSTNAME') ? getenv('SITES_DB_HOSTNAME') : 'localhost',
            'port' => FALSE,
        ],
        'character_set' => 'utf8',
    ];



    // This key is not secure, so it must not be used in production environments
    $config['server_key'] = 'NOT SECURE';
}
