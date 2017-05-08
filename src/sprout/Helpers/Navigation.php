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

use Sprout\Exceptions\QueryException;


/**
* Provides navigation functions
**/
class Navigation
{
    static private $root_node = null;
    static private $page_node_matcher = null;
    static private $pmm2_nav_id = 0;


    /**
    * Sets a matcher that should be used to find the current page in the tree
    *
    * @param TreeNodeMatcher $page_node_matcher The matcher to use
    **/
    static public function setPageNodeMatcher($page_node_matcher)
    {
        self::$page_node_matcher = $page_node_matcher;
    }

    /**
    * Gets the matcher that is used to get the current page
    *
    * @return TreeNodeMatcher The matcher being used.
    **/
    static public function getPageNodeMatcher()
    {
        return self::$page_node_matcher;
    }


    /**
    * Loads the page tree from the database
    *
    * @param int $subsite_id The subsite to load pages from. If not provided, the current subsite is assumed.
    * @param bool $is_admin If the user is in the admin, certain restrictions are not enforced.
    * @param bool $set_root Set the Navigation::$root_node paramater, as used by all the other methods.
    *        Default is to set this parameter, but if you want the tree loaded twice for some reason, set to false.
    **/
    static public function loadPageTree($subsite_id = null, $is_admin = false, $set_root = true)
    {
        if ($subsite_id == null) {
            $subsite_id = SubsiteSelector::$content_id;
        }

        if (Kohana::config('cache.enabled') and $is_admin == false) {
            $cache = Cache::instance();
            $from_cache = $cache->get("nav:{$subsite_id}");
            if ($from_cache) {
                if ($set_root) self::$root_node = $from_cache;
                return $from_cache;
            }
        }

        $where = "pages.subsite_id = ?";
        if ($is_admin == false) {
            $where .= " AND pages.active = 1 AND revs.status = 'live'";
        }

        $q = "SELECT pages.id, pages.parent_id, pages.slug, pages.name, pages.menu_group, pages.show_in_nav,
                pages.alt_nav_title, pages.admin_perm_type, banners.filename AS banner,
                gallery_thumbs.filename AS gallery_thumb, revs.controller_entrance, revs.controller_argument,
                pages.date_modified, revs.redirect
            FROM ~pages AS pages
            LEFT JOIN ~files AS banners ON pages.banner = banners.id
            LEFT JOIN ~files AS gallery_thumbs ON pages.gallery_thumb = gallery_thumbs.id
            INNER JOIN ~page_revisions AS revs ON revs.page_id = pages.id
            WHERE {$where}
            GROUP BY pages.id
            ORDER BY pages.parent_id, pages.record_order";
        try {
            $result = Pdb::q($q, [$subsite_id], 'arr');
        } catch (QueryException $ex) {
            // Assume DB has no tables
            $result = [];
        }

        $root_node = new Pagenode(array(
            'id' => 0,
            'parent_id' => 0,
            'slug' => '',
            'name' => '',
            'controller_entrance' => '',
            'controller_argument' => '',
            'menu_group' => '',
        ));

        // Create nodes
        $needprocess = array();
        foreach ($result as $row) {
            $needprocess[(int)$row['id']] = new Pagenode($row);
        }

        // Save all nodes in a cache
        $nodecache = $needprocess;
        $nodecache[0] = $root_node;

        // Process the nodes
        // This may iterate if low-numbered nodes are children of high-numbered ones
        do {
            $num_processed = 0;
            foreach ($needprocess as $id => $node) {
                $parent = @$nodecache[(int)$node['parent_id']];

                if ($parent) {
                    $parent->children[] = $node;
                    $node->parent = $parent;
                    unset($needprocess[$id]);
                    $num_processed++;
                }
            }
            if ($num_processed == 0) break;

        } while (count($needprocess));

        unset($nodecache);

        if (Kohana::config('cache.enabled') and $is_admin == false) {
            $cache->set("nav:{$subsite_id}", $root_node);
        }

        if ($set_root) {
            self::$root_node = $root_node;
        }

        return $root_node;
    }

    /**
    * Gets the root node of the currently loaded navigation tree
    **/
    static public function getRootNode()
    {
        if (! self::$root_node) self::loadPageTree();
        return self::$root_node;
    }

    /**
    * Alias for Navigation::getRootNode()
    **/
    static public function rootNode()
    {
        return self::getRootNode();
    }


    /**
    * Nuke the navigation from the cache.
    * Assumes the user is logged into the admin
    **/
    static public function clearCache()
    {
        $subsite_id = $_SESSION['admin']['active_subsite'];

        if (Kohana::config('cache.enabled')) {
            $cache = Cache::instance();
            $cache->delete("nav:{$subsite_id}");
        }
    }

    /**
    * Draws a pmm2 menu, with the current page highlighted in the menu
    *
    * @tag api
    * @tag designer-api
    **/
    static public function pmm2()
    {
        if (! self::$root_node) self::loadPageTree();

        self::$root_node->filterChildren(new TreenodeInMenuMatcher());

        $pmm_max_depth = (int) Kohana::config('sprout.pmm2_depth');
        $pmm_nav_limit = (int) Kohana::config('sprout.nav_limit');

        if ($pmm_nav_limit == 0) $pmm_nav_limit = 999;
        if ($pmm_max_depth == 0) $pmm_max_depth = 2;


        echo '<ul class="p7PMM">';

        // Home page
        if (Kohana::config('sprout.nav_home')) {
            $nav_id = ++self::$pmm2_nav_id;
            $home_url = Url::base();
            if (Url::current() == 'home_page') {
                echo "<li id=\"nav{$nav_id}\" class=\"on\"><a href=\"{$home_url}\" class=\"main\">Home</a></li>";
            } else {
                echo "<li id=\"nav{$nav_id}\"><a href=\"{$home_url}\" class=\"main\">Home</a></li>";
            }
        }

        // All the other pages
        foreach (self::$root_node->children as $page) {
            self::pmm2Drawnode ($page, 1, $pmm_max_depth, $pmm_nav_limit);
        }

        echo '</ul>', PHP_EOL;

        self::$root_node->removeFilter();
    }


    /**
    * Draws a single item, and its sub-items
    *
    * @param TreeNode $node The node to draw
    * @param int $depth The current depth of the tree
    **/
    static private function pmm2Drawnode($node, $depth, $pmm_max_depth, $pmm_nav_limit)
    {
        $anc = array();
        if (self::$page_node_matcher != null) {
            $page_node = self::$root_node->findNode(self::$page_node_matcher);
            if ($page_node) {
                $anc = $page_node->findAncestors();
            }
        }

        if ($depth == 1) {
            // Top level items are WEIRD!
            $nav_id = ++self::$pmm2_nav_id;
            if ($nav_id > $pmm_nav_limit) return;
            if (in_array($node, $anc, true)) {
                echo "<li id=\"nav{$nav_id}\" class=\"on\">";
            } else {
                echo "<li id=\"nav{$nav_id}\">";
            }
            echo "<a href=\"{$node->getFriendlyUrl()}\" class=\"main\">", Enc::html($node->getNavigationName()), "</a>";

        } else {
            // All other levels
            if (in_array($node, $anc, true)) {
                echo "<li class=\"on\"><a href=\"{$node->getFriendlyUrl()}\">", Enc::html($node->getNavigationName()), "</a>";
            } else {
                echo "<li><a href=\"{$node->getFriendlyUrl()}\">", Enc::html($node->getNavigationName()), "</a>";
            }
        }

        // Draw its children
        if ($depth < $pmm_max_depth and count($node->children) > 0) {
            echo '<div><ul>';
            foreach ($node->children as $node) {
                self::pmm2Drawnode($node, $depth + 1, $pmm_max_depth, $pmm_nav_limit);
            }
            echo '</ul></div>';
        }

        echo '</li>';
    }


    /**
    * Draws a superfish menu, with the current page highlighted in the menu
    **/
    static public function superfish()
    {
        if (! self::$root_node) self::loadPageTree();

        self::$root_node->filterChildren(new TreenodeInMenuMatcher());
        self::$pmm2_nav_id = 0;

        $pmm_max_depth = (int) Kohana::config('sprout.pmm2_depth');
        $pmm_nav_limit = (int) Kohana::config('sprout.nav_limit');

        if ($pmm_nav_limit == 0) $pmm_nav_limit = 999;
        if ($pmm_max_depth == 0) $pmm_max_depth = 2;


        echo '<ul id="frankenmenu-list" class="-clearfix">';

        // Home page
        if (Kohana::config('sprout.nav_home')) {
            $pmm_nav_limit--;
            $home_url = Url::base();
            if (Url::current() == 'home_page') {
                echo "<li class=\"on\"><a href=\"{$home_url}\">Home</a></li>";
            } else {
                echo "<li><a href=\"{$home_url}\">Home</a></li>";
            }
        }

        // All the other pages
        foreach (self::$root_node->children as $page) {
            $pmm_nav_limit--;
            self::superfishDrawnode ($page, 1, $pmm_max_depth);
            if ($pmm_nav_limit == 0) break;
        }

        echo '</ul>', PHP_EOL;

        self::$root_node->removeFilter();
    }


    /**
    * Private node drawing function for SuperFish menu.
    **/
    static private function superfishDrawnode($node, $depth, $pmm_max_depth)
    {
        $anc = array();
        if (self::$page_node_matcher != null) {
            $page_node = self::$root_node->findNode(self::$page_node_matcher);
            if ($page_node) {
                $anc = $page_node->findAncestors();
            }
        }

        // All other levels
        if (in_array($node, $anc, true)) {
            echo "<li class=\"on\"><a href=\"{$node->getFriendlyUrl()}\">", Enc::html($node->getNavigationName()), "</a>";
        } else {
            echo "<li><a href=\"{$node->getFriendlyUrl()}\">", Enc::html($node->getNavigationName()), "</a>";
        }

        // Draw its children
        if ($depth < $pmm_max_depth and count($node->children) > 0) {
            echo '<ul>';
            foreach ($node->children as $node) {
                self::superfishDrawnode($node, $depth + 1, $pmm_max_depth);
            }
            echo '</ul>';
        }

        echo '</li>';
    }


    /**
    * Draws a current-reveal menu.
    * This is a menu where the top level items all get shown, but the children
    * only get shown for active items.
    *
    * Uses the same HTML as the pmm2 menu, just because that HTML has plenty of
    * classes and ids, and I already had the code for that menu :)
    *
    * See http://www.rymill.com.au/ for an example of this type of menu.
    *
    * @tag api
    * @tag designer-api
    **/
    static public function currentReveal()
    {
        if (! self::$root_node) self::loadPageTree();

        self::$root_node->filterChildren(new TreenodeInMenuMatcher());

        if (Kohana::config('sprout.nav_limit') == 0) {
            Kohana::configSet('sprout.nav_limit', 9999);
        }

        echo "<ul>\n";

        // Home page
        if (Kohana::config('sprout.nav_home')) {
            $nav_id = ++self::$pmm2_nav_id;
            $home_url = Url::base();
            if (Url::current() == 'home_page') {
                echo "<li id=\"nav{$nav_id}\" class=\"on\"><a href=\"{$home_url}\" class=\"main\">Home</a></li>";
            } else {
                echo "<li id=\"nav{$nav_id}\"><a href=\"{$home_url}\" class=\"main\">Home</a></li>";
            }
        }

        // All the other pages
        foreach (self::$root_node->children as $page) {
            self::currentRevealDrawnode($page, 1);
        }
        echo "</ul>\n";

        self::$root_node->removeFilter();
    }


    /**
    * Draws a single item, and its sub-items
    *
    * @param TreeNode $node The node to draw
    * @param int $depth The current depth of the tree
    **/
    static private function currentRevealDrawnode($node, $depth)
    {
        $anc = array();
        if (self::$page_node_matcher != null) {
            $page_node = self::$root_node->findNode(self::$page_node_matcher);
            if ($page_node) {
                $anc = $page_node->findAncestors();
            }
        }

        if ($depth == 1) {
            // Top level items are WEIRD!
            $nav_id = ++self::$pmm2_nav_id;
            if ($nav_id > Kohana::config('sprout.nav_limit')) return;
            if (in_array($node, $anc, true)) {
                echo "<li id=\"nav{$nav_id}\" class=\"on\">";
            } else {
                echo "<li id=\"nav{$nav_id}\">";
            }
            echo "<a href=\"{$node->getFriendlyUrl()}\" class=\"main\">", Enc::html($node->getNavigationName()), "</a>";

        } else {
            // All other levels
            if (in_array($node, $anc, true)) {
                echo "<li class=\"on\"><a href=\"{$node->getFriendlyUrl()}\">", Enc::html($node->getNavigationName()), "</a>";
            } else {
                echo "<li><a href=\"{$node->getFriendlyUrl()}\">", Enc::html($node->getNavigationName()), "</a>";
            }
        }

        $max_depth = (int) Kohana::config('sprout.current_reveal_depth');
        if ($max_depth == 0) $max_depth = 2;

        // Draw its children
        if (in_array($node, $anc, true) and $depth < $max_depth and count($node->children) > 0) {
            echo "\n<div><ul>\n";
            foreach ($node->children as $node) {
                self::currentRevealDrawnode($node, $depth + 1);
            }
            echo "</ul></div>\n";
        }

        echo "</li>\n";
    }


    /**
    * Draws a UL of children for a zippmenu
    **/
    static public function zippmenuChildren($page_url)
    {
        if (! self::$root_node) self::loadPageTree();

        self::$root_node->filterChildren(new TreenodeInMenuMatcher());

        $page = self::$root_node->findNode(new TreenodePathMatcher($page_url));

        if ($page == null) return;
        if (count($page->children) == 0) return;


        // If there is a current page, note down the ancestors
        $anc = array();
        if (self::$page_node_matcher != null) {
            $page_node = self::$root_node->findNode(self::$page_node_matcher);
            if ($page_node) {
                $anc = $page_node->findAncestors();
            }
        }

        // Dump all children
        echo "<ul class=\"zippmenu-sub\">\n";
        foreach ($page->children as $child) {
            if (in_array($child, $anc, true)) {
                echo "<li class=\"on\"><a href=\"{$child->getFriendlyUrl()}\">", Enc::html($child->getNavigationName()), "</a>";
            } else {
                echo "<li><a href=\"{$child->getFriendlyUrl()}\">", Enc::html($child->getNavigationName()), "</a>";
            }
        }
        echo "</ul>\n";

        self::$root_node->removeFilter();
    }


    /**
    * Renders breadcrumbs for the current page
    *
    * @param string $seperator_front The separator to use. Defaults to ' >> '
    * @param array $post_crumbs Additional crumbs to add after the navigation ones. Should be in the format url => label
    * @param string $seperator_back The separator for the closing tag, if needed.
    * @return string HTML
    **/
    static public function breadcrumb($seperator_front = ' &raquo; ', $post_crumbs = null, $seperator_back = '')
    {
        if (! self::$root_node) self::loadPageTree();

        // Load a page node from the page tree.
        if (self::$page_node_matcher == null) return;
        $page_node = self::$root_node->findNode(self::$page_node_matcher);
        if ($page_node == null) return;

        // Generate the breadcrumbs. Will be generated in reverse order.
        $crumbs = array();
        $node = $page_node;
        while ($node['id'] != 0) {
            $crumbs[] = "<a href=\"{$node->getFriendlyUrl()}\">" . Enc::html($node->getNavigationName()) . "</a>";
            $node = $node->parent;
        }

        $home_url = Url::base();
        $crumbs[] = "<a href=\"{$home_url}\">Home</a>";

        // Reverse the order of the breadcrumbs
        $crumbs = array_reverse ($crumbs);

        // Add in any extra crumbs
        if (is_array($post_crumbs)) {
            foreach ($post_crumbs as $url => $label) {
                if (!is_string($url)) $url = '';
                $crumbs[] = '<a href="' . Enc::html($url) . '">' . Enc::html($label) . '</a>';
            }
        }

        // Change the last crumb to not have a link
        $c = array_pop($crumbs);
        $crumbs[] = '<span>' . strip_tags($c) . '</span>';

        if (!empty($seperator_front) and !empty($seperator_back)) {
            foreach ($crumbs as &$crumb) {
                $crumb = $seperator_front . $crumb . $seperator_back;
            }
            return implode ('', $crumbs);
        }

        return implode ($seperator_front, $crumbs);
    }


    /**
    * Renders a custom breadcrumb based on an array of crumbs
    *
    * @param array $crumbs Crumbs for the breadcrumb. Should be in the format url => label
    * @param string $seperator_front The separator to use. Defaults to ' >> '
    * @param string $seperator_back The separator for the closing tag, if needed.
    * @return string HTML
    **/
    static public function customBreadcrumb(array $crumbs, $seperator_front = ' &raquo; ', $seperator_back = '')
    {
        $bc = array();
        $bc[] = '<a href="SITE/">Home</a>';

        foreach ($crumbs as $url => $label) {
            if (!is_string($url)) $url = '';
            $bc[] = '<a href="' . Enc::html($url) . '">' . Enc::html($label) . '</a>';
        }

        $c = array_pop($bc);
        $bc[] = '<span>' . strip_tags($c) . '</span>';

        if (!empty($seperator_front) and !empty($seperator_back)) {
            foreach ($bc as &$crumb) {
                $crumb = $seperator_front . $crumb . $seperator_back;
            }
            return implode('', $bc);
        }

        return implode($seperator_front, $bc);
    }


    /**
    * Generates a menu which does not have dropdowns, but just has the top-level items
    *
    * @tag api
    * @tag designer-api
    **/
    static public function nonDropdown()
    {
        if (! self::$root_node) self::loadPageTree();

        self::$root_node->filterChildren(new TreenodeInMenuMatcher());

        if (Kohana::config('sprout.nav_limit') == 0) {
            Kohana::configSet('sprout.nav_limit', 9999);
        }

        // Selected page detection
        $anc = array();
        if (self::$page_node_matcher != null) {
            $page_node = self::$root_node->findNode(self::$page_node_matcher);
            if ($page_node) {
                $anc = $page_node->findAncestors();
            }
        }

        echo "<ul>";
        $i = 0;

        // Show home page
        if (Kohana::config('sprout.nav_home')) {
            $i++;
            $home_url = Url::base();
            if (Url::current() == 'home_page') {
                echo "<li class=\"nav{$i} on\"><a href=\"{$home_url}\">Home</a></li>";
            } else {
                echo "<li class=\"nav{$i}\"><a href=\"{$home_url}\">Home</a></li>";
            }
        }

        // Show items
        foreach (self::$root_node->children as $page) {
            $i++;
            if ($i > Kohana::config('sprout.nav_limit')) break;
            if ($page === @$anc[0]) {
                echo "<li class=\"nav{$i} on\"><a href=\"{$page->getFriendlyUrl()}\">", Enc::html($page->getNavigationName()), "</a></li>";
            } else {
                echo "<li class=\"nav{$i}\"><a href=\"{$page->getFriendlyUrl()}\">", Enc::html($page->getNavigationName()), "</a></li>";
            }
        }
        echo "</ul>";

        self::$root_node->removeFilter();
    }


    /**
    * Builds the title that gets put into TITLE tags in the head of the page
    * Just glues the site title onto the end of the page title
    *
    * @param string $page_title The title of the page
    **/
    static public function buildBrowserTitle($page_title)
    {
        $format = Kohana::config('sprout.browser_title');
        if ($format == '') $format = '%1$s | %2$s';

        return sprintf($format, $page_title, Kohana::config('sprout.site_title'));
    }


    /**
    * Returns an array of all pages.
    **/
    static public function getAllPages($parent = null, $indent = 0)
    {
        if (! self::$root_node) self::loadPageTree();

        if ($parent == null) {
            $parent = self::$root_node;
        }

        $pages = array();
        foreach ($parent->children as $page) {
            // [0] => id, [1] => name, [2] = url
            $pages[] = array(
                $page['id'],
                str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $indent) . $page->getNavigationName(),
                $page->getFriendlyUrl(),
            );

            $children = self::getAllPages($page, $indent + 1);
            $pages = array_merge($pages, $children);
        }

        return $pages;
    }


    /**
    * Returns the filename to use for a banner image, or NULL if no appropriate banner was found
    **/
    static public function banner()
    {
        if (! self::$root_node) self::loadPageTree();

        if (! self::$page_node_matcher) return null;

        $page_node = self::$root_node->findNode(self::$page_node_matcher);
        if (! $page_node) return null;

        return $page_node->getBanner();
    }


    /**
    * Returns the name of this section, or the site name if no reasonable section can be determined.
    **/
    static public function sectionName()
    {
        if (! self::$root_node) self::loadPageTree();
        if (! self::$page_node_matcher) return Kohana::config('sprout.site_title');

        $page_node = self::$root_node->findNode(self::$page_node_matcher);
        if (! $page_node) return Kohana::config('sprout.site_title');

        $ancestors = $page_node->findAncestors();
        if (! $ancestors[0]) return Kohana::config('sprout.site_title');

        return $ancestors[0]->getNavigationName();
    }


    /**
    * Returns the node in the page tree which matched the specified page_node_matcher.
    * Returns NULL if no node is found, or if the matcher does not exist.
    **/
    static public function matchedNode()
    {
        if (! self::$root_node) self::loadPageTree();
        if (! self::$page_node_matcher) return null;

        return self::$root_node->findNode(self::$page_node_matcher);
    }

    /**
    * Alias for Navigation::matchedNode()
    **/
    static public function getMatchedNode()
    {
        return self::matchedNode();
    }


    /**
    * Returns a simple menu
    * @return array [id, friendly-url, menu-name, childs array]
    **/
    public static function simpleMenu()
    {
        if (! self::$root_node) self::loadPageTree();

        self::$root_node->filterChildren(new TreenodeInMenuMatcher());
        self::$pmm2_nav_id = 0;

        $pmm_max_depth = (int) Kohana::config('sprout.pmm2_depth');
        $pmm_nav_limit = (int) Kohana::config('sprout.nav_limit');

        if ($pmm_nav_limit == 0) $pmm_nav_limit = 999;
        if ($pmm_max_depth == 0) $pmm_max_depth = 2;


        echo '<ul id="frankenmenu-list" class="-clearfix">';

        // Home page
        if (Kohana::config('sprout.nav_home')) {
            $pmm_nav_limit--;
            $home_url = Url::base();
            if (Url::current() == 'home_page' || Url::current() == null) {
                echo "<li class=\"menu-item menu-item-depth1 menu-home-page menu-current-item\"><a href=\"{$home_url}\">Home</a></li>";
            } else {
                echo "<li class=\"menu-item menu-item-depth1 menu-home-page\"><a href=\"{$home_url}\">Home</a></li>";
            }
        }

        // All the other pages
        foreach (self::$root_node->children as $page) {
            $pmm_nav_limit--;
            self::simpleMenuDrawnode ($page, 1, $pmm_max_depth);
            if ($pmm_nav_limit == 0) break;
        }

        echo '</ul>', PHP_EOL;

        self::$root_node->removeFilter();
    }

    /**
    * Private node drawing function for SuperFish menu.
    **/
    static private function simpleMenuDrawnode($node, $depth, $pmm_max_depth)
    {
        $anc = array();
        $page_node = null;

        if (self::$page_node_matcher != null) {
            $page_node = self::$root_node->findNode(self::$page_node_matcher);
            if ($page_node) {
                $anc = $page_node->findAncestors();
            }
        }

        // Determine if this node has children
        $has_children = ($depth < $pmm_max_depth and count($node->children) > 0 ? true : false);

        // Determine classes to be added to child
        $item_classes = "";
        if($has_children) $item_classes .= " menu-item-has-children";

        // If the page is the current item
        if (Url::current() === $node->getFriendlyUrlNoprefix()) {
            $item_classes .= ' menu-current-item';
        } else if (in_array($node, $anc, true)) {
            $item_classes .= " menu-current-item-ancestor";
        }


        // All other levels
        echo "<li class=\"menu-item menu-item-depth{$depth}{$item_classes}\">";
        echo "<a href=\"{$node->getFriendlyUrl()}\">", Enc::html($node->getNavigationName()), "</a>";

        // Draw its children
        if ($has_children) {
            echo "<ul class=\"sub-menu sub-menu-depth{$depth}\">";
            foreach ($node->children as $node) {
                self::simpleMenuDrawnode($node, $depth + 1, $pmm_max_depth);
            }
            echo '</ul>';
        }

        echo '</li>';
    }

}
