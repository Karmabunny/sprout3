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
 * @package  Encrypt
 *
 * Encrypt configuration is defined in groups which allows you to easily switch
 * between different encryption settings for different uses.
 * Note: all groups inherit and overwrite the default group.
 *
 * Group Options:
 *  key    - Encryption key used to do encryption and decryption. The default option
 *           should never be used for a production website.
 *
 *           For best security, your encryption key should be at least 16 characters
 *           long and contain letters, numbers, and symbols.
 *           @note Do not use a hash as your key. This significantly lowers encryption entropy.
 *
 *  cipher - openSSL encryption cipher. By default, the 'aes-256-cbc' cipher is used.
 */
$config['default'] = array
(
    'key'    => 'K0H@NA+PHP_7hE-SW!FtFraM3w0R|<',
    'cipher' => 'aes-256-cbc',
    'iv_size' => 16,
);
