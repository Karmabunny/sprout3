<?php

use Sprout\Helpers\FilesBackendDirectory;
use Sprout\Helpers\FilesBackendS3;

$config['file_backends'] = [
    'local' => [
        'name' => 'Local directory',
        'class' => FilesBackendDirectory::class,

        'settings' => [
        ],
    ],
    's3' => [
        'name' => 'Amazon S3',
        'class' => FilesBackendS3::class,

        'client' => [
            'region' => 'ap-southeast-2',
        ],
        'settings' => [
            'bucket' => 'sproutcms-files-backend-test',
            // 'static_object_urls' => true,
            'signed_urls' => '+1 hour',
        ],
    ],
];

$config['backend_type'] = 'local';
