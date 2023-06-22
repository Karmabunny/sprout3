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

namespace Sprout\Controllers\Admin;

/**
 * Implement this (on a controller) to disable record permissions.
 *
 * When included the controller will not appear in the 'per record permissions'
 * admin tooling.
 *
 * Some base controllers include this implicitly:
 *
 * - {@see CategoryAdminController}
 * - {@see NoRecordsAdminController}
 *
 * @package Sprout\Controllers\Admin
 */
interface NoRecordPermissionsInterface
{
}
