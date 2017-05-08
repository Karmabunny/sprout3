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

namespace Sprout\Helpers;

use Sprout\Helpers\Navigation;
use Sprout\Helpers\Pagenode;
use Sprout\Helpers\TreenodeInMenuMatcher;


class SitemapGenPages extends SitemapGen
{

    /**
     * Loads content page URLs and calls {@see SitemapGenPages::childrenPages} to output their XML URLs in the sitemap
     * @return void Outputs XML directly
     */
    public function generate()
    {
        $root = Navigation::getRootNode();
        $root->filterChildren(new TreenodeInMenuMatcher());
        $this->childrenPages($root, 0.9);
    }


    /**
     * Outputs XML URLs for the children of a page in the sitemap, recursively until all descendents have been output
     * @param Pagenode $node The page which should have its children/descendents output
     * @param float $prio Priority for matching pages; the deeper the pages are, the lower priority they are given
     * @return void Outputs XML directly
     */
    private function childrenPages(Pagenode $node, $prio)
    {
        foreach ($node->children as $child) {
            $this->url($child->getFriendlyUrlNoprefix(), $child['date_modified'], NULL, $prio);
            $this->childrenPages($child, $prio - 0.1);
        }
    }

}