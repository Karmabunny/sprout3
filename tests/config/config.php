<?php

/**
 * Base path of the web site.
 * Most common value would be '/' but it may be something else instead.
 * Should always have a trailing slash.
 */
$config['site_domain'] = '/';


/**
 * When PHP is called in CLI mode (e.g. a cron script), it cannot
 * autodetect the host name, so you need to specify it here.
 * If you don't, you will get an exception.
 *
 * Examples:
 *   example.com
 *   test.example.com
 */
$config['cli_domain'] = 'www.example.com';
