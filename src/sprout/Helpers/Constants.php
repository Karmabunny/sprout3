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

use ReflectionClass;


/**
* A bunch of different constants
**/
class Constants
{
    /** Permission types */
    const PERM_INHERIT = 1;
    const PERM_SPECIFIC = 2;


    /** Extra pages **/
    // These are now handled by the register helper, and APPPATH/sprout_load
    // but these are left for compatibility.
    const EXTRAPAGES_404 = 1;
    const EXTRAPAGES_ADMIN_LOGIN = 2;


    /** Cron and workers - job status */
    public static $job_status = array(
        0 => 'Incomplete',
        1 => 'Complete',
        -1 => 'Failed',
    );


    /** Relative dates */
    public static $relative_dates = array(
        'n1'  => 'Newer than 1 month',
        'n3'  => 'Newer than 3 months',
        'n12' => 'Newer than 12 months',
        'o12' => 'Older than 12 months',
    );


    /** Relative dates */
    public static $search_modifiers = array(
        'or' => 'Match any',
        'and' => 'Match all',
    );


    /**
     * Labels for the page revision statuses
     */
    public static $rev_statuses = [
        'old' => 'Old',
        'wip' => 'Work in progress',
        'need_approval' => 'Needs approval',
        'live' => 'Live',
        'rejected' => 'Rejected',
        'auto_launch' => 'Ready for autolaunch',
    ];


    /**
    * Looks for a class constant which has a specific value.
    *
    * @param string $class The class to look for constants in.
    * @param string $prefix The prefix to require a constant to have.
    * @param string $value The value to require a constant to have.
    * @return string The name of the constant, or null if no matching constant was found.
    **/
    public static function reverseLookup($class, $prefix, $value)
    {
        $refl_class = new ReflectionClass($class);

        $constants = $refl_class->getConstants();
        foreach ($constants as $name => $val) {
            if (strncasecmp($name, $prefix, strlen($prefix)) == 0 and $val == $value) return $name;
        }

        return null;
    }


    /**
    * For the admin 'last modified' refine field
    **/
    public static $recent_dates = array(
        '1 DAY' => 'Last 24 hours',
        'YESTERDAY' => 'Yesterday',
        '1 WEEK' => 'This week',
        '1 MONTH' => 'This month',
        '3 MONTH' => 'This quarter',
        '1 YEAR' => 'This year',
    );


    /**
    * List of month names
    **/
    public static $month_names = array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May',
        6 => 'June', 7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');


    /**
    * Password algorithms
    **/
    const PASSWORD_SHA_SALT = 2;
    const PASSWORD_BCRYPT12 = 3;
    const PASSWORD_SHA_SALT_5000 = 5;

    // Read-only algorithms, for data migration
    const PASSWORD_SHA = 1;
    const PASSWORD_PLAIN = 4;


    /**
    * Maximum age of locks (seconds)
    **/
    const LOCK_AGE = 120;        // two minutes


    /**
    * The different category options in the admin
    **/
    const CATEGORIES_CURRENT = 1;
    const CATEGORIES_ARCHIVE = 2;
    const CATEGORIES_ALL = 3;

    public static $category_admin_options = array(
        self::CATEGORIES_CURRENT => 'Live',
        self::CATEGORIES_ARCHIVE => 'Archived',
        self::CATEGORIES_ALL => 'All',
    );

    public static $category_admin_where = array(
        self::CATEGORIES_CURRENT => 'categories.show_admin = 1',
        self::CATEGORIES_ARCHIVE => 'categories.show_admin = 0',
        self::CATEGORIES_ALL => '1',
    );


    /**
    * This is a list of common extensions and mimetypes
    **/
    public static $mimetypes = array(
        'aac'   =>  'audio/aac',
        'avi'   =>  'video/vnd.avi',
        'bmp'   =>  'image/bmp',
        'csv'   =>  'text/csv',
        'doc'   =>  'application/msword',
        'docx'  =>  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'flv'   =>  'video/x-flv',
        'gif'   =>  'image/gif',
        'jpeg'  =>  'image/jpeg',
        'jpe'   =>  'image/jpeg',
        'jpg'   =>  'image/jpeg',
        'mov'   =>  'video/quicktime',
        'mp3'   =>  'audio/mpeg',
        'mp4'   =>  'application/mp4',
        'mpeg'  =>  'video/mpeg',
        'mpg'   =>  'video/mpeg',
        'ods'   =>  'application/vnd.oasis.opendocument.spreadsheet',
        'odp'   =>  'application/vnd.oasis.opendocument.presentation',
        'odt'   =>  'application/vnd.oasis.opendocument.text',
        'oga'   =>  'audio/ogg',
        'ogv'   =>  'video/ogg',
        'pdf'   =>  'application/pdf',
        'png'   =>  'image/png',
        'pps'   =>  'application/vnd.ms-powerpoint',
        'ppsx'  =>  'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
        'ppt'   =>  'application/powerpoint',
        'pptx'  =>  'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rtf'   =>  'text/rtf',
        'tiff'  =>  'image/tiff',
        'tif'   =>  'image/tiff',
        'txt'   =>  'text/plain',
        'webm'  =>  'video/webm',
        'wmv'   =>  'application/vnd.ms-asf',
        'xls'   =>  'application/excel',
        'xlsx'  =>  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'zip'   =>  'application/zip',
    );


    /**
    * If the first segment of a URL cannot be used because it conflicts with a controller name
    * A similar problem exists for top-level directories
    **/
    public static $conflict_page_urls = array(
        'ad', 'admin', 'article', 'action_log', 'advanced_search',
        'cart', 'config', 'content_block', 'captcha', 'category', 'content_subscribe', 'cron_job',
        'dbtools', 'document_search',
        'event', 'extra_page', 'ext_video', 'email_share',
        'file', 'files', 'form', 'forum',
        'galleryfile', 'gallery',
        'home_page',
        'job_advert', 'job_application',
        'list', 'link',
        'man', 'managed', 'media', 'modules', 'mailchimp',
        'newsletter',
        'operator', 'order',
        'page', 'page_feedback', 'payment', 'paypal', 'project', 'product', 'pg',
        'redirect', 'recurring_payment',
        'search', 'skin', 'sprout', 'subsite', 'subscriber', 'secfile', 'sponsor', 'scss',
        'testing', 'tools', 'tree', 'tinymce',
        'user',
        'worker_job',
    );

}


