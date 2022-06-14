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

use Sprout\Helpers\Form;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\TreenodeInMenuMatcher;
use Sprout\Helpers\TreenodeValueMatcher;
use Sprout\Helpers\PhpView;


/**
* Shows a list of pages that are related to this one
**/
class ChildrenGalleryWidget extends Widget
{
    protected $friendly_name = "Children page gallery";
    protected $friendly_desc = 'A list of the children pages of this page, in a friendly gallery format';


    /**
    * Does the front-end rendering of this widget
    *
    * @param int $orientation The orientation of the widget
    **/
    public function render($orientation)
    {
        $this->settings['max_depth'] = (int) @$this->settings['max_depth'];
        if ($this->settings['max_depth'] <= 0) $this->settings['max_depth'] = 1;

        $this->settings['thumb_rows'] = (int) @$this->settings['thumb_rows'];
        if ($this->settings['thumb_rows'] < 2) $this->settings['thumb_rows'] = 4;

        $this->settings['hide_blanks'] = (int) @$this->settings['hide_blanks'];

        $root_node = Navigation::getRootNode();
        if ($root_node == null) return;

        $this->settings['parent'] = (int) @$this->settings['parent'];
        if ($this->settings['parent'] == 0) {
            $matcher = Navigation::getPageNodeMatcher();
            if ($matcher == null) return;

        } else {
            $matcher = new TreenodeValueMatcher('id', $this->settings['parent']);

        }

        $page_node = $root_node->findNode($matcher);
        if ($page_node == null) return;

        $page_node->filterChildren(new TreenodeInMenuMatcher());

        if (count($page_node->children) == 0) {
            $page_node->removeFilter();
            return null;
        }

        switch ($this->settings['thumb_rows']) {
            case 2:
                $image_resize = 'c1080x888';
                break;

            case 3:
                $image_resize = 'c1080x888';
                break;

            case 5:
                $image_resize = 'c810x666';
                break;

            default:
                $image_resize = 'c1080x888';
                break;
        }

        $view = new PhpView('sprout/children_page_gallery');
        $view->page_node = $page_node;
        $view->hide_blanks = $this->settings['hide_blanks'];
        $view->idx = 0;
        $view->thumb_rows = $this->settings['thumb_rows'];
        $view->image_resize = $image_resize;

        $html = $view->render();

        $page_node->removeFilter();

        return $html;
    }



    /**
    * Returns the settings for the article list widget
    * See {@link Widget::getSettingsForm} for full documentation
    **/
    public function getSettingsForm()
    {
        $out = '';

        $q = "SELECT id, name FROM ~subsites ORDER BY record_order";
        $res = Pdb::query($q, [], 'arr');

        $pages = [];

        foreach ($res as $row) {
            $root = Navigation::loadPageTree($row['id'], true, false);
            $children = $root->getAllChildren();

            $pages[$row['name']] = $children;
        }

        Form::nextFieldDetails('Parent Page', false);

        if (count($pages) > 1) {
            $out .= "<p><strong>Note:</strong> Pages are ordered by subsite, all pages for all subsites are available by scrolling down the option list.</p><br>";
            $out .= Form::dropdown('parent', [], $pages);
        } else {
            $out .= Form::dropdown('parent', [], reset($pages));
        }

        $out .= '<div class="field-group-wrap -clearfix">';
        $out .= '<div class="field-group-item col col--one-half">';

        Form::nextFieldDetails('Options', false);
        $out .= Form::checkboxList(['hide_blanks' => 'Hide pages with no gallery image']);

        $out .= '</div>';
        $out .= '<div class="field-group-item col col--one-half">';

        Form::nextFieldDetails('Thumbnails per row', false);
        $out .= Form::dropdown('thumb_rows', [], ['2'=> '2', '3' => '3', '4' => '4', '5' => '5']);

        $out .= '</div></div>';

        return $out;
    }

}



