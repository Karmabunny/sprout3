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
 * @package Session
 *
 * Session driver name: native/database
 */
$config['driver'] = 'native';

/**
 * Session storage parameter, used by drivers.
 */
$config['storage'] = '';

/**
 * Session name.
 * It must contain only alphanumeric characters and underscores. At least one letter must be present.
 */
$config['name'] = 'PHP5BLVI80';

/**
 * Session parameters to validate: userAgent, ip_address, expiration.
 */
$config['validate'] = array();

/**
 * Enable or disable session encryption.
 * Note: this has no effect on the native session driver.
 * Note: the cookie driver always encrypts session data. Set to TRUE for stronger encryption.
 */
$config['encryption'] = FALSE;

/**
 * Session lifetime. Number of seconds that each session will last.
 * A value of 0 will keep the session active until the browser is closed (with a limit of 24h).
 */
$config['expiration'] = 0;

/**
 * Number of page loads before the session id is regenerated.
 * A value of 0 will disable automatic session id regeneration.
 */
$config['regenerate'] = 0;

/**
 * Percentage probability that the gc (garbage collection) routine is started.
 * N.B. On Debian systems, there's a system cron that cleans up the session
 * data, so PHP's session garbage collector should be turned off.
 * @see https://bugs.launchpad.net/ubuntu/+source/php5/+bug/619855
 * @see http://stackoverflow.com/questions/2904862/issues-with-php-5-3-and-sessions-folder
 * @see http://somethingemporium.com/2007/06/obscure-error-with-php5-on-debian-ubuntu-session-phpini-garbage
 */
if (preg_match('/ubuntu|debian/i', php_uname())) {
    $config['gc_probability'] = 0;
} else {
    $config['gc_probability'] = 2;
}
