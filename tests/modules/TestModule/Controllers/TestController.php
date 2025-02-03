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

namespace Sprout\TestModules\TestModule\Controllers;

use Sprout\Controllers\Controller;
use Sprout\Helpers\BaseView;

class TestController extends Controller
{
    public function home()
    {
        $view = BaseView::create('skin/wide');
        $view->page_title = 'Test';
        $view->main_content = 'testy test test';
        echo $view->render();
    }
}
