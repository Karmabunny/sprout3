<?php

use karmabunny\kb\Env;
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
            'endpoint' => getenv('S3_TEST_SERVICE') ?: 'http://localhost:9000/',
            'region' => 'us-east-1',
            'request_checksum_calculation' => 'when_required',
            'request_checksum_validation' => 'when_required',
            'use_path_style_endpoint' => true,
        ],
        'settings' => [
            'bucket' => 'sprout3-test',
            'public_url_domain' => (getenv('S3_TEST_PUBLIC') ?: 'http://localhost:9080/') . 'sprout3-test/',
        ],
    ],
];

$config['backend_type'] = 'local';
