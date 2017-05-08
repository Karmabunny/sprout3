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
    new ResizeImageTransform (150, 150),
);

$config['image_transformations']['medium'] = array(
    new ResizeImageTransform (340, null),
);

$config['image_transformations']['large'] = array(
    new ResizeImageTransform (800, null),
);


/**
* The size to use for image links added using a rich text editor.
* Specified in on-the-fly resize format.
**/
$config['imagelink_size'] = 'r500x500';


