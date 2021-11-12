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

use SproutModules\Karmabunny\HomePage\Helpers\HomePages;
use Sprout\Controllers\Controller;
use Sprout\Helpers\Needs;
use Sprout\Helpers\View;

/**
 * Handles requests for the home page
 */
class HomePageController extends Controller
{

    /**
     * Renders the home page
     *
     * @return void Outputs HTML directly
     */
    public function index()
    {
        $page = HomePages::getForSubSite(0);
        $browser_title = Kohana::config('sprout.site_title');
        $banners = HomePages::getActiveBanners($page['id']);
        $promos = HomePages::getActivePromos($page['id'], 3);

        if (!empty($page['alt_browser_title'])) $browser_title = $page['alt_browser_title'];
        if (!empty($page['meta_keywords'])) Needs::addMeta('keywords', $page['meta_keywords']);
        if (!empty($page['meta_description'])) Needs::addMeta('description', $page['meta_description']);

        $view = View::create('skin/layouts/03_home/home');
        $view->browser_title = $browser_title;
        $view->page = $page;
        $view->banners = $banners;
        $view->promos = $promos;

        echo $view->render();
    }
}
