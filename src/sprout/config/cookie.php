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
 * @package  Core
 *
 * Domain, to restrict the cookie to a specific website domain. For security,
 * you are encouraged to set this option. An empty setting allows the cookie
 * to be read by any website domain.
 */
$config['domain'] = '';

/**
 * Restrict cookies to a specific path, typically the installation directory.
 */
$config['path'] = '/';

/**
 * Lifetime of the cookie. A setting of 0 makes the cookie active until the
 * users browser is closed or the cookie is deleted.
 */
$config['expire'] = 0;

/**
 * Enable this option to only allow the cookie to be read when using the a
 * secure protocol.
 */
$config['secure'] = FALSE;

/**
 * Enable this option to disable the cookie from being accessed when using a
 * secure protocol. This option is only available in PHP 5.2 and above.
 */
$config['httponly'] = FALSE;