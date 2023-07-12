<?php

$config['account_id'] = getenv('AWS_ACCOUNT_ID') ?: '';
$config['region'] = getenv('AWS_REGION') ?: '';

// SHOULD NOT BE A ROOT USER - PLEASE USE IAM
$config['credentials']['key'] = getenv('AWS_KEY') ?: '';
$config['credentials']['secret'] = getenv('AWS_SECRET') ?: '';

/**
 * Optional overrides for the files backend.
 */
if (IN_PRODUCTION) {
    $config['files_backend'] = [
        'bucket' => 'sproutcms-files-backend', // Required
        'acl' => 'public-read', // Required. e.g. 'public-read' or 'private'

        // THE FOLLOWING AY BE OVERRIDDEN PER BACKEND
        // 'account_id' => '',
        // 'region' => '',
        // 'credentials' => [
        //     'key' => '',
        //     'secret' => '',
        // ],
    ];

} else {
    $config['files_backend'] = [
        'bucket' => 'sproutcms-files-backend-test', // Required
        'acl' => 'public-read', // Required. e.g. 'public-read' or 'private'
    ];
}

/**
 * Optional overrides for the resized files backend.
 */
if (IN_PRODUCTION) {
    $config['files_resized'] = [
        'bucket' => 'sproutcms-files-resized', // Required
        'acl' => 'public-read', // Required. e.g. 'public-read' or 'private'
    ];

} else {
    $config['files_resized'] = [
        'bucket' => 'sproutcms-files-resized-test', // Required
        'acl' => 'public-read', // Required. e.g. 'public-read' or 'private'
    ];
}
