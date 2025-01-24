<?php

use Sprout\Helpers\FilesBackendS3;

$config['file_backends'] = [
    's3' => [
        'name' => 'Amazon S3',
        'class' => FilesBackendS3::class,

        'client' => [
            'region' => 'ap-southeast-2',
        ],
        'settings' => [
            'bucket' => 'sproutcms-files-backend-test',
        ],
    ],
];

$config['backend_type'] = 's3';
