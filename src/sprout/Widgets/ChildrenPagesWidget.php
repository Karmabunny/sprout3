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

namespace Sprout\Widgets;

use Sprout\Helpers\Enc;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\TreenodeInMenuMatcher;

/**
* Shows a list of pages that are related to this one
**/
class ChildrenPagesWidget extends Widget
{
    protected string $friendly_name = "Children pages";
    protected string $friendly_desc = 'An list of the children pages as a textual list';


    /**
    * Does the front-end rendering of this widget
    *
    * @param int $orientation The orientation of the widget
    **/
    public function render($orientation)
    {
        $this->settings['max_depth'] = (int) @$this->settings['max_depth'];
        if ($this->settings['max_depth'] <= 0) $this->settings['max_depth'] = 1;

        $root_node = Navigation::getRootNode();
        if ($root_node == null) return '';

        $matcher = Navigation::getPageNodeMatcher();
        if ($matcher == null) return '';

        $page_node = $root_node->findNode($matcher);
        if ($page_node == null) return '';

        $page_node->filterChildren(new TreenodeInMenuMatcher());

        if (count($page_node->children) == 0) {
            $page_node->removeFilter();
            return '';
        }

        $out = "<ul>";
        foreach ($page_node->children as $page) {
            $page_url = Enc::html($page->getFriendlyUrl());
            $page_title = Enc::html($page->getNavigationName());
            $out .= "<li><a href=\"{$page_url}\">{$page_title}</a></li>";
        }
        $out .= "</ul>";

        $page_node->removeFilter();

        return $out;
    }

}



