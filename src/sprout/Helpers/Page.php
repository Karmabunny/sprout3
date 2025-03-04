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

use Exception;
use InvalidArgumentException;

use karmabunny\pdb\Exceptions\QueryException;
use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Controllers\Controller;

/**
 * Methods for working with CMS pages
 */
class Page
{

    /**
    * Get the page id for a given URL
    **/
    public static function lookupUrl($url)
    {
        $root = Navigation::getRootNode();
        if (! $root) return null;

        $matcher = new TreenodePathMatcher($url);
        $node = $root->findNode($matcher);
        if (! $node) return null;

        return $node['id'];
    }


    /**
    * Return the URL for a page, when given the page id
    * The URLs are relative
    *
    * @param int $id The page id to return the URL for
    * @return string|null The friendly URL for the page or null if not found
    **/
    public static function url($id)
    {
        $id = (int) $id;
        if (! $id) return null;

        $root = Navigation::getRootNode();

        $node = $root->findNodeValue('id', $id);
        if (! $node) {
            return 'page/view_by_id/' . $id;
        }

        return $node->getFriendlyUrlNoprefix();
    }


    /**
     * Fetches the URL for a tool page for a specific controller entrance method
     * @param string|Controller $class Class
     * @param string $method Method
     * @return string The URL to access the tool page
     * @throws Exception if there's no matching tool page in the {@see Navigation} tree
     */
    public static function toolUrl($class, $method)
    {
        if (is_object($class)) $class = get_class($class);
        $matcher = new TreenodeFrontendMatcher($class, $method);
        $node = Navigation::getRootNode()->findNode($matcher);
        if (!$node) throw new Exception('Page for controller entrance not found');
        return $node->getFriendlyUrl();
    }


    /**
     * Set up metadata and social metadata for a tool page
     *
     * @return array|null $page Page record from database or null Current URL is not a matched node
     */
    public static function setupToolPage()
    {
        $node = Navigation::getMatchedNode();
        if (!$node) return null;

        $page = Pdb::get('pages', $node['id']);

        static::loadPageMeta($page);
        static::loadPageSocial($page, $node);

        return $page;
    }


    /**
     * Load page metadata - description and keywords
     *
     * @param array $page Page record from database
     */
    public static function loadPageMeta(array $page)
    {
        if (!empty($page['meta_description'])) {
            Needs::addMetaName('description', $page['meta_description']);
        }
        if (!empty($page['meta_keywords'])) {
            Needs::addMetaName('keywords', $page['meta_keywords']);
        }

        CustomHeadTags::addHeadTags('pages', $page['id']);
    }


    /**
     * Load page social - title, image, description, url
     *
     * @param array $page Page record from database
     * @param Pagenode|null $node Node, for generating the URL; optional
     * @return void
     */
    public static function loadPageSocial(array $page, ?Pagenode $node = null)
    {
        SocialMeta::setTitle($page['name']);

        if (!empty($page['gallery_thumb'])) {
            SocialMeta::setImage($page['gallery_thumb']);
        } else if (!empty($page['banner'])) {
            SocialMeta::setImage($page['banner']);
        }

        if (!empty($page['meta_description'])) {
            SocialMeta::setDescription($page['meta_description']);
        }

        if ($node !== null) {
            SocialMeta::setUrl($node->getFriendlyUrlNoPrefix());
        }
    }


    /**
     * Get the canonical URL for a page either from the Custom Meta or from the page URL
     *
     * @param int $page_id Page ID
     *
     * @return string Canonical URL
     */
    public static function canonicalUrl(int $page_id)
    {
        $page_url = Sprout::absRoot() . Page::url($page_id);
        $custom_url = CustomHeadTags::getCanonicalURL('pages', $page_id);
        $canonical = $custom_url ?: $page_url;

        return $canonical;
    }


    /**
     * Inject page details -- title and browser title -- into a skin view
     *
     * @param BaseView $skin Skin view to inject details into
     * @param array $page Page to pull details from
     */
    public static function injectPageSkin(BaseView $skin, array $page)
    {
        if (!empty($page['name'])) {
            $skin->page_title = $page['name'];
        }
        if (!empty($page['alt_browser_title'])) {
            $skin->browser_title = $page['alt_browser_title'];
        }
    }


    /**
     * Gets the embedded widgets (i.e. content blocks) for a page
     * @param int $rev_id Page revision ID from database (page_revisions.id)
     * @param string $include 'active' to only include active widgets,
     *        'all' to include disabled widgets as well
     * @return array Rows extracted from the database
     */
    public static function getContentWidgets($rev_id, $include)
    {
        $rev_id = (int) $rev_id;
        if (!in_array($include, ['active', 'all'])) {
            throw new InvalidArgumentException("\$include must be 'active' or 'all'");
        }

        $active = ($include == 'active' ? 'AND active = 1' : '');

        $q = "SELECT id, type, settings, active, heading, template
            FROM ~page_widgets
            WHERE page_revision_id = ? AND area_id = 1 {$active}
            ORDER BY record_order";
        return Pdb::q($q, [$rev_id], 'map-arr');
    }


    /**
     * Get the page text for a page id, in HTML format, with widgets and everything, ready to go
     *
     * @param int $page_id Page ID from database
     * @param int $rev_id If specified, that revision will be used.
     *        Otherwise, the current live revision will be used.
     *        N.B. if specified, it must be a valid revision for the specified page
     * @param int $subsite_id Optional subsite ID - this is required for admins editing a sub-site
     *          that's different from the domain
     * @return string HTML
     * @throws QueryException
     **/
    public static function getText($page_id, $rev_id = 0, $subsite_id = null)
    {
        $page_id = (int) $page_id;
        $rev_id = (int) $rev_id;
        $subsite_id = (int) $subsite_id;

        if ($subsite_id < 1) $subsite_id = SubsiteSelector::$content_id;

        $params = [
            'id' => $page_id,
            'subsite_id' => $subsite_id,
        ];
        if ($rev_id > 0) {
            $clause = "rev.id = :rev_id";
            $params['rev_id'] = $rev_id;
        } else {
            $clause = "rev.status = :live";
            $params['live'] = 'live';
        }
        $q = "SELECT rev.id
            FROM ~pages AS page
            INNER JOIN ~page_revisions AS rev ON page.id = rev.page_id AND {$clause}
            WHERE page.id = :id AND page.subsite_id = :subsite_id
            LIMIT 1";
        try {
            $rev_id = Pdb::query($q, $params, 'val');

        // No live revision means this is a new, blank page
        } catch (RowMissingException $ex) {
            return '';
        }

        $text = '';
        $widgets = self::getContentWidgets($rev_id, 'active');
        foreach ($widgets as $widget) {
            $inst = Widgets::instantiate($widget['type']);
            $inst->importSettings(json_decode($widget['settings'], true));
            $inst->setTitle($widget['heading']);
            $widget_text = $inst->render(WidgetArea::ORIENTATION_WIDE);
            if (!$widget_text) continue;

            // Prevent lack of whitespace between final and initial sentences of adjacent blocks
            if ($text) $text .= "\n";

            $text .= $widget_text;
        }

        return ContentReplace::html($text);
    }


    /**
    * Returns an array of key-value pairs of all attributes for a page
    **/
    public static function attrs($id)
    {
        $id = (int) $id;

        $attrs = array();

        try {
            $q = "SELECT name, value FROM ~page_attributes WHERE page_id = ?";
            $attrs = Pdb::q($q, [$id], 'map');
        } catch (Exception $ex) {}

        return $attrs;
    }


    /**
    * Returns an array pages which contain a given attribute.
    *
    * @param string $attr_name Attribute name to search for
    * @param string $attr_value If set, require the attribute to be this value
    * @return array Page IDs
    * @throws QueryException
    **/
    public static function pagesWithAttr($attr_name, $attr_value = null)
    {
        $conditions = array();
        $conditions['name'] = $attr_name;
        if ($attr_value) $conditions['value'] = $attr_value;

        $params = array();
        $where = Pdb::buildClause($conditions, $params);

        $q = "SELECT page_id FROM ~page_attributes WHERE {$where} ORDER BY page_id";
        return Pdb::query($q, $params, 'col');
    }


    /**
     * Find pages with a given widget, and optionally the specified settings.
     * Only widgets on live revisions of active pages are checked.
     * @param string $widget_name The class of the desired widget.
     * @param array $settings The settings to look for; all of the specified settings must match.
     * @return array Page IDs
     * @throws QueryException
     */
    public static function pagesWithWidget($widget_name, array $settings = [])
    {
        $q = "SELECT page.id, widget.settings
            FROM ~page_widgets AS widget
            INNER JOIN ~page_revisions AS rev ON widget.page_revision_id = rev.id AND rev.status = 'live'
            INNER JOIN ~pages AS page ON rev.page_id = page.id AND page.active = 1
            WHERE widget.type = ?";
        if (count($settings) == 0) {
            return Pdb::query($q, [$widget_name], 'col');
        }

        $res = Pdb::query($q, [$widget_name], 'pdo');
        $pages = [];
        foreach ($res as $row) {
            $widget_settings = json_decode($row['settings'], true);
            $diff = array_diff_assoc($settings, $widget_settings);
            if (count($diff) == 0) {
                $pages[] = $row['id'];
            }
        }
        $res->closeCursor();

        return $pages;
    }


    /**
    * Return the last-modified date of the specified page.
    * Returns NULL on error.
    *
    * If you don't provide a page-id, uses the id of the
    * Navigation::matchedNode()
    *
    * The date is formatted using the php date function.
    * The default date format is "d/m/Y".
    *
    * @param int $page_id The page to get the last-modified date of
    * @param string $date_format The date format to return the date in
    * @return string|null Date or null If page could not be found
    **/
    public static function lastModified($page_id = null, $date_format = 'd/m/Y')
    {
        if ($page_id === null) {
            $node = Navigation::matchedNode();
            if ($node === null) return null;
            $page_id = $node['id'];
        }

        try {
            $q = "SELECT date_modified
                FROM ~pages
                WHERE id = ?
                ORDER BY date_modified DESC
                LIMIT 1";
            $date = Pdb::query($q, [$page_id], 'val');
            return date($date_format, strtotime($date));

        } catch (QueryException $ex) {
            return null;
        }
    }


    /**
     * Makes a particular revision live, and changes the status of the previous live revision to 'old'.
     * Should be run inside a transaction.
     * @param $rev_id ID of revision to make live
     * @return void
     */
    public static function activateRevision($rev_id) {
        $rev_id = (int) $rev_id;

        $q = "SELECT page_id FROM ~page_revisions WHERE id = ?";
        $page_id = Pdb::q($q, [$rev_id], 'val');

        $old_clause = ['page_id' => $page_id, 'status' => 'live'];
        Pdb::update('page_revisions', ['status' => 'old'], $old_clause);

        Pdb::update('page_revisions', ['status' => 'live'], ['id' => $rev_id]);
    }


    /**
     * Return list of related links for given page
     *
     * @param int $page_id
     * @return array [href, text] pairs
     */
    public static function determineRelatedLinks($page_id)
    {
        $list = [];
        $root_node = Navigation::getRootNode();
        if ($root_node == null) return $list;


        $matcher = new TreenodeIdsMatcher([$page_id]);
        if ($matcher == null) return $list;

        $page_node = $root_node->findNode($matcher);
        if ($page_node == null) return $list;

        $ancestors = $page_node->findAncestors();
        $top_anc = $ancestors[0];

        $top_anc->filterChildren(new TreenodeInMenuMatcher());

        if (count($top_anc->children) == 0) {
            $top_anc->removeFilter();
            return $list;
        }

        foreach ($top_anc->children as $page) {
            $list = array_merge($list, self::determineNodeLinks($page, 0));
        }

        $top_anc->removeFilter();

        return $list;
    }


    /**
     * Traverse page node to extract page links
     *
     * @param Pagenode $node The node to traverse
     * @param int $depth
     * @return array [href, text] pairs
     */
    private static function determineNodeLinks($node, $depth)
    {
        $list = [];
        $new_depth = $depth + 1;

        $list[] = ['href' => $node->getFriendlyUrl(), 'text' => $node->getNavigationName()];

        if ($new_depth <= 10 and count($node->children)) {
            foreach ($node->children as $node) {
                $list = array_merge($list, self::determineNodeLinks($node, $new_depth));
            }
        }

        return $list;
    }


    /**
     * Return details for current page
     *
     * @return array|null DB row
     */
    public static function current() {
        $node = Navigation::matchedNode();
        if (!$node) return null;

        $page = [];
        $page['id'] = $node['id'];
        $page['parent_id'] = $node['parent_id'];
        $page['subsite_id'] = SubsiteSelector::$subsite_id;
        $page['slug'] = $node['slug'];
        $page['name'] = $node['name'];
        $page['menu_group'] = $node['menu_group'];
        $page['show_in_nav'] = $node['show_in_nav'];
        $page['alt_nav_title'] = $node['alt_nav_title'];
        $page['banner'] = $node['banner'];
        $page['controller_entrance'] = $node['controller_entrance'];
        $page['controller_argument'] = $node['controller_argument'];

        return $page;
    }
}
