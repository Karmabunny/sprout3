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


/** @var string[] */

use karmabunny\kb\Secrets;

/**
 * Regex fragments for identifying keys that could contain secret data.
 *
 * 'key' being an array index.
 *
 * @var string[]
 */
$config['key_rules'] = Secrets::RULE_KEYS;

/**
 * Regex fragments for identifying values are are secret data.
 *
 * @var string[]
 */
$config['value_rules'] = Secrets::RULE_VALUES;

/**
 * Whether to treat _all_ base64 strings as secrets.
 *
 * If not enabled, base64 strings that contain matching patterns are
 * still removed.
 *
 * If enabled, all base64 strings are removed.
 *
 * Default: false.
 *
 * @var bool
 */
$config['base64'] = false;

/**
 * Whether to treat _all_ hex strings as secrets.
 *
 * If not enabled, hex strings that contain matching patterns are
 * still removed.
 *
 * If enabled, all hex strings are removed.
 *
 * Default: false.
 *
 * @var bool
 */
$config['hex'] = false;

/**
 * Create masks with fixed sizes.
 *
 * @var int|null
 */
$config['mask_length'] = 16;
