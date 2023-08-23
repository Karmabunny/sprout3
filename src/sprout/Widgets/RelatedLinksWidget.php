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

use Kohana;

use Sprout\Helpers\Enc;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\Pagenode;
use Sprout\Helpers\TreenodeInMenuMatcher;
use Sprout\Helpers\UserPerms;
use Sprout\Helpers\Url;


/**
* Shows a list of pages that are related to this one
**/
class RelatedLinksWidget extends Widget
{
    protected $friendly_name = "Related links";


    /**
    * Does the front-end rendering of this widget
    *
    * @param int $orientation The orientation of the widget
    **/
    public function render($orientation)
    {
        $root_node = Navigation::getRootNode();
        if ($root_node == null) return '';

        $matcher = Navigation::getPageNodeMatcher();
        if ($matcher == null) return '';

        $page_node = $root_node->findNode($matcher);
        if ($page_node == null) return '';

        $ancestors = $page_node->findAncestors();
        $top_anc = $ancestors[0];

        $top_anc->filterChildren(new TreenodeInMenuMatcher());

        if (count($top_anc->children) == 0) {
            $top_anc->removeFilter();
            return '';
        }

        $out = Kohana::config('sprout.related_heading');
        $out = str_replace('SECTION', Enc::html($top_anc->getNavigationName()), $out);

        $out .= "<ul class=\"depth1\">";

        // Top-parent, either page name (TRUE) or custom text (any string)
        $top = Kohana::config('sprout.related_top');
        if ($top) {
            $classes = 'depth1';
            if ($page_node === $top_anc) $classes .= ' on';

            $page_title = Enc::html($top_anc->getNavigationName());
            $page_url = Enc::html($top_anc->getFriendlyUrl());

            if ($top === true) {
                $out .= "<li class=\"{$classes}\"><a href=\"{$page_url}\">{$page_title}</a></li>";
            } else {
                $out .= "<li class=\"{$classes}\"><a href=\"{$page_url}\">" . Enc::html($top) . "</a></li>";
            }
        }

        foreach ($top_anc->children as $page) {
            if (! UserPerms::checkPermissionsTree('pages', $page['id'])) continue;

            $out .= self::drawnode ($page, 1, $ancestors);
        }
        $out .= "</ul>";

        $top_anc->removeFilter();

        return $out;
    }


    /**
    * Draws a single item, and its sub-items
    *
    * @param Pagenode $node The node to draw
    * @param int $depth The depth of the current node
    * @param array $ancestors The ancestors of the current page node, for highlighting.
    **/
    static private function drawnode($node, $depth, &$ancestors)
    {
        $classes = 'depth' . $depth;

        $max_depth = Kohana::config('sprout.related_max_depth');
        $new_depth = $depth + 1;

        $node_title = Enc::html($node->getNavigationName());
        $node_url = Enc::html($node->getFriendlyUrl());

        // If the page is the current item
        if (Url::current() === $node->getFriendlyUrlNoprefix()) {
            $classes .= ' on';
        } else if (in_array($node, $ancestors, true)) {
            $classes .= ' ancestor';
        }

        if (in_array($node, $ancestors, true)) {

            // Items that are part of the ancestory of the current page
            $out = "<li class=\"{$classes}\"><a href=\"{$node_url}\">{$node_title}</a>";

            if (($max_depth === null or $new_depth <= $max_depth) and count($node->children)) {
                $out .= "<ul class=\"depth{$new_depth}\">";
                foreach ($node->children as $node) {
                    if (! UserPerms::checkPermissionsTree('pages', $node['id'])) continue;

                    $out .= self::drawnode($node, $new_depth, $ancestors);
                }
                $out .= "</ul>";
            }

            $out .= "</li>";

        } else {
            // Everything else
            $out = "<li class=\"{$classes}\"><a href=\"{$node_url}\">{$node_title}</a></li>";
        }

        return $out;
    }

}



