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

use Sprout\Helpers\FilesBackendDirectory;
use Sprout\Helpers\FilesBackendS3;
use Sprout\Helpers\ResizeImageTransform;


/**
 * Backends available for file processing
 *
 * The key for each class type is stored in the db against file records (e.g. 's3')
 *
 * The 'name' key is the name that will be displayed in the admin interface
 * The 'class' key should be a class that extends Sprout\Helpers\FilesBackend
 *
 * If using URL signing in S3, you may use a private bucket
 *   (block all public access)to protect files.
 *
 * - NOTE That custom backends can use whatever settings they like
 *   as long as they are within the key 'settings' and contains the key 'config'
 *
 * The structure of this config is as follows:
 *
 * backend_type => [
 *      name => string
 *      class => string (full namespaced class path)
*       settings => [
 *         ...
 *      ]
 * ]
 *
 */
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

        // Config for the S3 client.
        'client' => [
            // Uses 'aws.region' if not specified.
            'region' => getenv('AWS_REGION') ?: null,
        ],

        // See FilesBackendS3::DEFAULT_SETTINGS
        'settings' => [
            // The bucket name (REQUIRED)
            'bucket' => '__bucket_not_configured__',

            // Override the domain for public URLs.
            // e.g. https://xxx.cloudfront.net/ - false to disable
            'public_url_domain' => false,

            // Build object URLs statically without making API calls.
            'static_object_urls' => true,

            // Call object APIs in async using a redirect (file/resolve).
            'lazy_object_urls' => true,

            // Apply a default ACL for all new objects.
            // e.g. 'public-read' - false to disable
            'default_acl' => false,

            // Produce signed URLs for objects if not using other other methods
            // for public access.
            // e.g. '+1 hour' - false to disable
            'signed_urls' => false,

            // Folder prefix for transformed images
            'transform_folder_prefix' => 'transformed/',

            // Time to cache file helpers responses, such as ::exists()
            'default_cache_ttl' => 86400,

            // Chunk size for processing streams
            'stream_chunk_size' => 1024 * 1024,
        ],
    ],
];


/**
 * Which backend are we using, from one of the types above
 */
$config['backend_type'] = 'local';


/**
 * Image transformations
 *
 * This is an array of different operations that should be applied to the image
 *
 * Currently supported transformations are:
 *    ResizeImageTransform  ( width , height )
 *    CropImageTransform  ( width , height , top_pos = 'center' , left_pos = 'center')
 *
 * Transformations get applied in the order they are provided in the array.
 *
 * You can also create your own transformations, just implement the ImageTransform interface
 */

$config['image_transformations']['small'] = array(
    new ResizeImageTransform (400, 400),
);

$config['image_transformations']['medium'] = array(
    new ResizeImageTransform (680, null),
);

$config['image_transformations']['large'] = array(
    new ResizeImageTransform (1280, null),
);


// Instant transformations are for sizes needed straight away
// Such as previews shown in the admin page while the background job runs

$config['image_transformations_instant']['r200x0'] = array(
    new ResizeImageTransform (200, null),
);

$config['image_transformations_instant']['r300x0'] = array(
    new ResizeImageTransform (300, null),
);


/**
* The size to use for image links added using a rich text editor.
* Specified in on-the-fly resize format.
**/
$config['imagelink_size'] = 'r500x500';

/**
 * Permit upscaling images.
 */
$config['upscale_images'] = false;
