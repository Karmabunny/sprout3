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

namespace SproutModules\Karmabunny\Demo\Controllers\Admin;

use Sprout\Controllers\Admin\CategoryAdminController;


/**
* Handles most processing for Demo item categories
**/
class DemoItemCategoryAdminController extends CategoryAdminController
{
    protected $controller_name = 'demo_item_category';
    protected $friendly_name = 'Demo item categories';
    protected $navigation_name = 'Demo items';
}
