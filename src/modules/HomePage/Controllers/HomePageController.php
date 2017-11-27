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
use Sprout\Exceptions\RowMissingException;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\SubsiteSelector;
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
        $q = "SELECT hmpg.id, hmpg.text, hmpg.meta_keywords, hmpg.meta_description, hmpg.alt_browser_title
            FROM ~homepages AS hmpg
            WHERE hmpg.subsite_id = ?";
        $page = Pdb::query($q, [SubsiteSelector::$subsite_id], 'row');

        if (!empty($page['alt_browser_title'])) {
            $browser_title = $page['alt_browser_title'];
        } else {
            $browser_title = Kohana::config('sprout.site_title');
        }

        $this->setMeta($page);

        try {
            $banner = HomePages::getRandomActiveBanner($page['id']);
        } catch (RowMissingException $ex) {
            $banner = null;
        }

        $promos = HomePages::getActivePromos($page['id'], 3);

        $view = new View('skin/home');
        $view->browser_title = $browser_title;
        $view->page = $page;
        $view->banner = $banner;
        $view->promos = $promos;
        echo $view->render();
    }


    /**
     * Set page meta-data from the database record
     *
     * @param array $page Home page database record
     * @return void
     */
    private function setMeta(array $page)
    {
        if (!empty($page['meta_keywords'])) {
            Needs::addMeta('keywords', $page['meta_keywords']);
        }

        if (!empty($page['meta_description'])) {
            Needs::addMeta('description', $page['meta_description']);
        }
    }

}
