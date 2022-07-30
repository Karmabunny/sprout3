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
     * The PHP timezone will be set to this value using date_default_timezone_set
     *
     * If set to an empty value then the timzone will not be set, which may cause
     * warnings if the server config has not set the timezone.
     */
    const TIMEZONE = 'Australia/Adelaide';


    /**
     * Turns on the debug mode for origin cleanup, which outputs the redirect
     * which would occur, but doesn't actually perform the redirect
     */
    const ORIGIN_CLEANUP_DEBUG = false;


    /**
     * Toggle Kohana caching for files and configurations.
     */
    const ENABLE_KOHANA_CACHE = false;


    /**
     * Specify what the protocol and/or hostname which should be for requests
     * If this doesn't match the current values, then a 301 redirect will occur
     *
     * Default version of this method does nothing, but commented-out examples
     * for common adjustments are included
     *
     * @param string $proto Current request protocol, either 'http' or 'https'
     * @param string $hostname Current request hostname, e.g. 'example.com'
     * @return array New values for $proto and $hostname vars.
     *      If these are different from the incoming values, then a redirect will occur
     *      First element is protocol, second element is hostname
     *      Example: ['https', 'www.example.com']
     */
    public static function originCleanup($proto, $hostname)
    {
        // On test-server, don't change anything
        if (!IN_PRODUCTION) {
            return [$proto, $hostname];
        }

        // Force https for all traffic
        ////$proto = 'https';

        // Force specific domain name
        ////$hostname = 'www.example.com';

        // If hostname does not begin with www. then append this
        ////if (strpos($hostname, 'www.') !== 0) {
        ////    $hostname = 'www.' . $hostname;
        ////}

        // If hostname begins with www. then strip this off
        ////if (strpos($hostname, 'www.') === 0) {
        ////    $hostname = substr($hostname, 4);
        ////}

        return [$proto, $hostname];
    }

}
