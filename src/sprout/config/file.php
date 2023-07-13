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

use Sprout\Helpers\ResizeImageTransform;


/**
 * Backends available for file processing
 *
 * The key for each class type is stored in the db against file records (e.g. 's3')
 *
 * The 'name' key is the name that will be displayed in the admin interface
 * The 'class' key should be a class that extends Sprout\Helpers\FilesBackend
 */
$config['file_backends'] = [
    'local' => [
        'name' => 'Local directory',
        'class' => 'Sprout\Helpers\FilesBackendDirectory',
        'settings' => [
            'store_abs_urls' => false,
        ]
    ],
    's3' => [
        'name' => 'Amazon S3',
        'class' => 'Sprout\Helpers\FilesBackendS3',
        'settings' => [
            'store_abs_urls' => true,
        ]
    ],
];


/**
 * Which backend are we using, from one of the types above
 */
$config['backend_type'] = 's3';


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


/**
* The size to use for image links added using a rich text editor.
* Specified in on-the-fly resize format.
**/
$config['imagelink_size'] = 'r500x500';


