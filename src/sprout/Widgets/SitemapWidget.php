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
* Shows a site map
**/
class SitemapWidget extends Widget
{
    protected $friendly_name = "Sitemap";
    protected $friendly_desc = 'A complete sitemap of the website';


    /**
    * Does the front-end rendering of this widget
    *
    * @param int $orientation The orientation of the widget
    **/
    public function render($orientation)
    {
        $root_node = Navigation::getRootNode();
        if ($root_node == null) return;

        $root_node->filterChildren(new TreenodeInMenuMatcher());

        $out = "<ul class=\"depth1\">";
        foreach ($root_node->children as $page) {
            $out .= self::drawnode ($page, 1, $ancestors);
        }
        $out .= "</ul>";

        $root_node->removeFilter();

        return $out;
    }


    /**
    * Draws a single item, and its sub-items
    *
    * @param TreeNode $node The node to draw
    * @param int $depth The depth of the current node
    * @param array $ancestors The ancestors of the current page node, for highlighting.
    **/
    static private function drawnode($node, $depth, &$ancestors)
    {
        $classes = 'depth' . $depth;

        $node_title = Enc::html($node->getNavigationName());
        $node_url = Enc::html($node->getFriendlyUrl());

        $out = "<li class=\"{$classes}\"><a href=\"{$node_url}\">{$node_title}</a>";

        if (count($node->children)) {
            $new_depth = $depth + 1;
            $out .= "<ul class=\"depth{$new_depth}\">";
            foreach ($node->children as $node) {
                $out .= self::drawnode($node, $new_depth, $ancestors);
            }
            $out .= "</ul>";
        }

        $out .= "</li>";

        return $out;
    }

}



