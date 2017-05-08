<?php

/**
 * Methods for determining the details of the very initial environment,
 * prior to the rest of the system coming up
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
     * Define the website environment status. This determines how much debugging
     * information is provided when errors occur, among other things.
     *
     * @return bool True if in PROD environment, False if in TEST environment
     */
    public static function isInProduction()
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            if (preg_match('!localhost$!', $_SERVER['HTTP_HOST'])) return false;
            if (preg_match('!local$!', $_SERVER['HTTP_HOST'])) return false;
            return true;

        } else {
            // Lookup IP of another server in the dev environment
            // If it's a local IP then this must be a dev install
            if (strpos(gethostbyname('devel.example.com'), '192.168.') === 0) {
                return false;
            } else {
                return true;
            }
        }
    }


    /**
     * Turns on the debug mode for origin cleanup, which outputs the redirect
     * which would occur, but doesn't actually perform the redirect
     */
    const ORIGIN_CLEANUP_DEBUG = false;


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
