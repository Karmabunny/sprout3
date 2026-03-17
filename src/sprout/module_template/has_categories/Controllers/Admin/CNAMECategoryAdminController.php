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

namespace SproutModules\AUTHOR\MODULE\Controllers\Admin;

use Sprout\Controllers\Admin\CategoryAdminController;


/**
* Handles most processing for SNICE categories
**/
class CNAMECategoryAdminController extends CategoryAdminController
{
    protected string $friendly_name = 'SNICE categories';
    protected string $navigation_name = 'PNICE';
}
