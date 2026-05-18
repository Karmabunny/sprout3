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
            'endpoint' => Env::isContainer() ? 'http://host.docker.internal:9000/' : 'http://localhost:9000/',
            'region' => 'us-east-1',
            'request_checksum_calculation' => 'when_required',
            'request_checksum_validation' => 'when_required',
            'use_path_style_endpoint' => true,
        ],
        'settings' => [
            'bucket' => 'sprout3-test',
            'public_url_domain' => Env::isContainer() ? 'http://host.docker.internal:9080/sprout3-test/' : 'http://localhost:9080/sprout3-test/',
        ],
    ],
];

$config['backend_type'] = 'local';
