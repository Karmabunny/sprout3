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

use Kohana;


/**
* Represents a page in a page tree
**/
class Pagenode extends Treenode
{

    /**
    * Not valid
    **/
    public static function loadTree($table, array $where = ['1'], $order = 'record_order')
    {
        throw new Exception("Method not supported for page trees, use Navigation::loadPageTree instead.");
    }

    /**
    * Returns the friendly url for a node
    **/
    public function getFriendlyUrl()
    {
        if (!empty($this->data['redirect'])) {
            return Lnk::url($this->data['redirect']);
        }

        $anc = $this->findAncestors();

        $parts = array();
        foreach ($anc as $node) {
            $parts[] = $node->getUrlName();
        }

        return Kohana::config('core.site_domain', TRUE) . SubsiteSelector::$url_prefix . implode('/', $parts);
    }

    /**
    * Returns the friendly url for a node - but don't include a directory prefix
    **/
    public function getFriendlyUrlNoprefix()
    {
        $anc = $this->findAncestors();

        $parts = array();
        foreach ($anc as $node) {
            $parts[] = $node->getUrlName();
        }

        return implode('/', $parts);
    }

    /**
     * Returns the name that should be used to represent this node when rendering navigation elements
     * on the front-end
     * @return string N.B. NOT HTML-safe
     */
    public function getNavigationName()
    {
        $node_name = $this->data['name'];
        if ($this->data['alt_nav_title']) $node_name = $this->data['alt_nav_title'];

        return $node_name;
    }

    /**
    * Returns the name of this page only, when constructing a URL
    **/
    public function getUrlName()
    {
        if ($this->data['slug']) {
            $name = $this->data['slug'];
        } else {
            $name = Enc::urlname($this->data['name']);
        }

        if (in_array($name, Constants::$conflict_page_urls)) {
            return '_' . $name;
        } else {
            return $name;
        }
    }

    /**
    * Returns the filename to use for the banner image.
    * Returns NULL if no banner has been defined for this page and a generic banner should be used instead.
    **/
    public function getBanner()
    {
        if ($this->data['banner']) {
            return $this->data['banner'];
        }

        $anc = $this->findAncestors ();
        $anc = array_reverse ($anc);

        foreach ($anc as $node) {
            if ($node->data['banner']) {
                return $node->data['banner'];
            }
        }

        return null;
    }

}


