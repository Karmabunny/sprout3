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

namespace Sprout\Controllers;

use Sprout\Helpers\View;


/**
 * Used to handle generic success and error messages after processing a POST submission, when
 * there isn't a particularly good URL to redirect to.
 */
class ResultController extends Controller
{
    /**
     * Generic page for displaying one or more error messages set via {@see Notification::error}
     *
     * @return void Outputs HTML directly
     */
    public function error()
    {
        $skin = new View('skin/inner');
        $skin->page_title = 'Error';
        $skin->main_content = '';
        echo $skin->render();
    }


    /**
     * Generic page for displaying one or more success messages set via {@see Notification::confirm}
     *
     * @return void Outputs HTML directly
     */
    public function success()
    {
        $skin = new View('skin/inner');
        $skin->page_title = 'Success';
        $skin->main_content = '';
        echo $skin->render();
    }
}
