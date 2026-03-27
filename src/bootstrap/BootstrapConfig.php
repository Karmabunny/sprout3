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

/**
 * Methods for determining the details of the very initial environment,
 * prior to the rest of the system coming up
 *
 * This is a fallback class. Define this in your app config folder and load
 * it into your index.php.
 */
class BootstrapConfig
{

    /**
     * The PHP error reporting level.
     */
    const ERROR_REPORTING = IN_PRODUCTION
        ? E_ALL ^ E_NOTICE
        : E_ALL;


    /**
     * The PHP timezone will be set to this value using date_default_timezone_set
     *
     * If set to an empty value then the timezone will not be set, which may cause
     * warnings if the server config has not set the timezone.
     */
    const TIMEZONE = 'Australia/Adelaide';


    /**
     * Turns on the debug mode for origin cleanup, which outputs the redirect
     * which would occur, but doesn't actually perform the redirect
     */
    const ORIGIN_CLEANUP_DEBUG = false;


    /**
     * Whether to process fatal errors with the shutdown handler.
     *
     * Else uses native display_errors.
     */
    const ENABLE_FATAL_ERRORS = true;


    /**
     * Toggle Kohana caching for files and configurations.
     */
    const ENABLE_KOHANA_CACHE = false;


    /**
     * Copy media assets into the target web folder.
     *
     * This assumes the web server (nginx/apache) will pick up the real file
     * before it defers to the PHP application.
     */
    const ENABLE_MEDIA_CACHE = IN_PRODUCTION;

}
