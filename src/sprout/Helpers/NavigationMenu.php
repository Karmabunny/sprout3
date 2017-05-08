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

use Kohana;


/**
 * Renders a menu for the page node structure
 *
 * If you would like to edit this, it would be much wiser to sub-class it
 * and override the various methods
 */
class NavigationMenu
{

    /**
     * HTML to be returned prior to the first navigation item
     */
    protected static $open_html = '<ul id="frankenmenu-list" class="-clearfix">';

    /**
     * HTML to be returned after the last navigation item
     */
    protected static $close_html = '</ul>';

    /**
     * The view name to use for rendering sub-menu dropdowns.
     * Note that if a sub-class overrides the {@see navigation::sub_menu} method
     * then this parameter may get ignored.
     */
    protected static $dropdown_view_name = 'sprout/navigation_dropdown';


    /**
     * Return HTML for a mega menu.
     *
     * You shouldn't need to modify or extend this method;
     * If you do then your use case is probably too bespoke for this class.
     *
     * @return string HTML
     */
    public static function render()
    {
        $root = Navigation::getRootNode();
        $root->filterChildren(new TreenodeInMenuMatcher());

        $selected_node = Navigation::getMatchedNode();
        if ($selected_node !== null) {
            $selected_ancestors = $selected_node->findAncestors();
        } else {
            $selected_ancestors = array();
        }

        $limit = (int) Kohana::config('sprout.nav_limit');
        if ($limit == 0) {
            $limit = 9999;
        }

        $out = static::$open_html . PHP_EOL;

        if (Kohana::config('sprout.nav_home')) {
            --$limit;
            $out .= static::home() . PHP_EOL;
        }

        $custom_dropdown = Kohana::config('sprout.nav_custom_dropdown');

        $index = 0;
        foreach ($root->children as $node) {
            if ($limit-- == 0) break;
            ++$index;

            // Fetch the various navigation groups before the rendering, so it can be determined if there
            // actually is a menu or not, so as to affect the classes on the LI
            $groups = array();
            $group_names = NavigationGroups::getAllNames($node['id']);
            foreach ($group_names as $position => $name) {
                $group_id = NavigationGroups::getId($node['id'], $position);
                $items = $root->findAllNodes(new TreenodeValueMatcher('menu_group', $group_id));
                if (count($items) > 0) {
                    $groups[$name] = $items;
                }
            }
            $has_children = (count($groups) > 0);

            $classes = static::determineClasses($node, 1, $index, $selected_node, $selected_ancestors, $has_children);

            $out .= '<li class="' . Enc::html(implode(' ', $classes)) . '">';
            $out .= '<a href="' . Enc::html($node->getFriendlyUrl()) . '">' . Enc::html($node->getNavigationName()) . '</a>';

            if (isset($custom_dropdown[$node['id']])) {
                $out .= PHP_EOL . PHP_EOL;
                $out .= trim(call_user_func(
                    $custom_dropdown[$node['id']], $node, $groups, $selected_node, $selected_ancestors
                ));
                $out .= PHP_EOL . PHP_EOL;
            } else if (count($groups)) {
                $out .= PHP_EOL . PHP_EOL;
                $out .= trim(static::subMenu($node, $groups, $selected_node, $selected_ancestors));
                $out .= PHP_EOL . PHP_EOL;
            }

            $out .= '</li>' . PHP_EOL;
        }

        $out .= static::$close_html;

        $root->removeFilter();

        return $out;
    }


    /**
     * Return HTML for the home page nav item
     * This would typically be a LI
     *
     * @return string HTML
     */
    protected static function home() {
        $classes = 'menu-item menu-item-depth1 menu-home-page';
        if (Url::current() == 'home_page' || Url::current() == null) {
            $classes .= ' menu-current-item';
        }

        $out = '<li class="' . $classes . '">';
        $out .= '<a href="' . Enc::html(Url::base()) . '">Home</a>';
        $out .= '</li>';

        return $out;
    }


    /**
     * Determine the classes for the LI for a navigation item
     *
     * @param Treenode $node The node which is being rendered
     * @param int $depth The menu depth; 1 for top-level, 2 for a sub menu
     * @param int $index The position in the menu; 1 for the first item, 2 for the second, etc
     * @param Treenode $selected_node The page the user is currently looking at
     * @param array $selected_ancestors All ancestors of the selected node ({@see Treenode::find_ancestors})
     * @param bool $has_children Does the node have children
     * @return array Zero or more class names
     */
    public static function determineClasses(Treenode $node, $depth, $index, $selected_node, array $selected_ancestors, $has_children) {
        $classes = array('menu-item', "menu-item-depth{$depth}", "menu-item-depth{$depth}--item{$index}");

        if ($has_children) {
            $classes[] = 'menu-item-has-children';
        }

        if ($selected_node === $node) {
            $classes[] = 'menu-current-item';
        } else if (in_array($node, $selected_ancestors, true)) {
            $classes[] = 'menu-current-item-ancestor';
        }

        return $classes;
    }


    /**
     * Return HTML for a drop-down sub menu
     *
     * @param Treenode $parent_node The node which is being rendered
     * @param array $groups The groups of items to render, in the format [name => array of Treenode, ...]
     * @param Treenode $selected_node The page the user is currently looking at
     * @param array $selected_ancestors All ancestors of the selected node ({@see Treenode::find_ancestors})
     * @return string HTML
     */
    protected static function subMenu(Treenode $parent_node, array $groups, $selected_node, array $selected_ancestors) {
        $view = new View(static::$dropdown_view_name);
        $view->parent_node = $parent_node;
        $view->groups = $groups;
        $view->selected_node = $selected_node;
        $view->selected_ancestors = $selected_ancestors;
        $view->extra = NavigationGroups::getExtras($parent_node['id']);
        return $view->render();
    }

}
