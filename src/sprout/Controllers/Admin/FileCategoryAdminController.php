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
* Handles categories for files
**/
class FileCategoryAdminController extends CategoryAdminController
{
    protected $controller_name = 'file_category';
    protected $friendly_name = 'File categories';
    protected $navigation_name = 'Files';
    protected $main_columns = null;
}


