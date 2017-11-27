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

namespace SproutModules\Karmabunny\HomePage\Helpers;

use Sprout\Helpers\Pdb;
use Sprout\Helpers\SitemapGen;
use Sprout\Helpers\SubsiteSelector;


/**
 * Tool to generate a sitemap entry for the home page(s)
 */
class SitemapGenHome extends SitemapGen
{

    /**
     * Generate a single sitemap entry for the home page
     */
    public function generate()
    {
        $q = "SELECT date_modified FROM ~homepages WHERE subsite_id = ?";
        $row = Pdb::q($q, [SubsiteSelector::$subsite_id], 'row');

        $this->url('/', $row['date_modified'], null, 1.0);
    }

}