<?php
/*
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout\Helpers;


/**
* A bunch of different constants
**/
class FileConstants
{

    /** Revisions */
    const TYPE_NONE = 0;
    const TYPE_DOCUMENT = 1;
    const TYPE_IMAGE = 2;
    const TYPE_SOUND = 3;
    const TYPE_VIDEO = 4;
    const TYPE_OTHER = 5;

    public static $type_names = array(
        self::TYPE_DOCUMENT => 'Document',
        self::TYPE_IMAGE => 'Image',
        self::TYPE_SOUND => 'Sound',
        self::TYPE_VIDEO => 'Video',
        self::TYPE_OTHER => 'Other',
    );


    public static $type_exts = array(
        self::TYPE_DOCUMENT => array('doc','docx','odt','txt','xls','xlsx','ods','csv','pdf','odp','ppt','pptx','pps','ppsx','rtf'),
        self::TYPE_IMAGE => array('jpg','jpeg','gif','png'),
        self::TYPE_SOUND => array('mp3','aac','oga'),
        self::TYPE_VIDEO => array('flv','mp4','mpeg','mpg','webm','ogv','avi','mov','wmv'),
    );


    // Ordering in the FileList
    const ORDER_NONE = 0;
    const ORDER_NAME = 1;
    const ORDER_MANUAL = 2;
    const ORDER_OLDEST = 3;
    const ORDER_NEWEST = 4;

    public static $order_names = array(
        self::ORDER_NAME => 'Alphabetically',
        self::ORDER_MANUAL => 'Manually',
        self::ORDER_OLDEST => 'Oldest first',
        self::ORDER_NEWEST => 'Newest first',
    );


    // Image ratios (for focal points); each is the maximum ratio for that type
    public static $image_ratios = [
        'portrait' => 0.91,
        'square' => 1.1,
        'landscape' => 2.0,
        // anything larger is a 'panorama'
    ];
}


