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

namespace SproutModules\Karmabunny\HomePage\Controllers;

use Kohana;

use Sprout\Controllers\Controller;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\SubsiteSelector;
use Sprout\Helpers\View;


class HomePageController extends Controller
{

    /**
     * Shows the home page
     */
    public function index()
    {
        $page_view = new View('skin/home');
        $page_view->browser_title = Kohana::config('sprout.site_title');

        // Load the page from the database
        $q = "SELECT * FROM ~homepages WHERE subsite_id = ?";
        $page = Pdb::q($q, [SubsiteSelector::$subsite_id], 'row');
        $page_view->page = $page;
        $page_view->main_content = $page['text'];

        if ($page['meta_keywords']) {
            Needs::addMeta('keywords', $page['meta_keywords']);
        }
        if ($page['meta_description']) {
            Needs::addMeta('description', $page['meta_description']);
        }
        if ($page['alt_browser_title']) {
            $page_view->browser_title = $page['alt_browser_title'];
        }

        echo $page_view->render();
    }

}
