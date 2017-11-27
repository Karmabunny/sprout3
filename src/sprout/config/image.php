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

/**
 * @package  Image
 *
 * Driver name. Default: GD
 */
$config['driver'] = 'GD';

/**
 * Driver parameters:
 * ImageMagick - set the "directory" parameter to your ImageMagick installation directory
 */
$config['params'] = array();

/**
 * Maximum size an image can be resized to before we emit an error
 */
$config['max_size'] = [
    'width' => 2048,
    'height' => 2048
];

/**
 * Maximum dimensions to store 'original' version of uploaded image
 *
 * When a larger image is uploaded, the original is to be discarded and a shrunken copy stored instead
 */
$config['original_size'] = [
    'width' => 2048,
    'height' => 2048,
];
