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

use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Form;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\View;
use Sprout\Helpers\WidgetArea;


/**
* doc list widget
**/
class FileListWidget extends Widget
{
    protected $friendly_name = "File list";
    protected $friendly_desc = "Displays a list of up to 50 files";
    protected $default_settings = ['order' => FileConstants::ORDER_NAME];


    /**
    * Returns the output of the doc list widget
    * See {@link Widget::render} for full documentation
    *
    * @param int $orientation The orientation of the widget.
    **/
    public function render($orientation)
    {
        $this->settings['category'] = (int) @$this->settings['category'];
        if ($this->settings['category'] == 0) return;

        // Load the docs from the database
        $q = "SELECT files.*
            FROM ~files AS files
            INNER JOIN ~files_cat_join AS joiner ON joiner.file_id = files.id
            WHERE joiner.cat_id = ?
            ORDER BY {$this->getOrderSql()}
            LIMIT 50";
        $res = Pdb::query($q, [$this->settings['category']], 'arr');

        if ($orientation == WidgetArea::ORIENTATION_TALL) {
            $view = new View('sprout/filelist_tall');
        } else if ($orientation == WidgetArea::ORIENTATION_WIDE) {
            $view = new View('sprout/filelist_wide');
        }

        $view->res = $res;

        return $view->render();
    }

    /**
    * Returns the settings for the doc list widget
    * See {@link Widget::getSettingsForm} for full documentation
    **/
    public function getSettingsForm()
    {
        $out = '';

        $q = "SELECT cat.id, CONCAT(cat.name, ' (', COUNT(file.id), ')')
            FROM ~files_cat_list AS cat
            LEFT JOIN ~files_cat_join AS joiner ON cat.id = joiner.cat_id
            LEFT JOIN ~files AS file ON joiner.file_id = file.id
            GROUP BY cat.id
            ORDER BY cat.name";
        $cats = Pdb::query($q, [], 'map');

        Form::nextFieldDetails('Category', true);
        $out .= Form::dropdown('category', [], $cats);

        Form::nextFieldDetails('Display order', true);
        $out .= Form::dropdown('order', [], FileConstants::$order_names);

        return $out;
    }


    /**
    * Returns a URL for editing the contents of this widget
    * See {@link Widget::getEditUrl} for full documentation
    **/
    public function getEditUrl()
    {
        if (empty($this->settings['category'])) return null;

        return 'admin/contents/file?_category_id=' . $this->settings['category'];
    }


    /**
    * Returns a label which describes the contents of this widget
    * See {@link Widget::get_info_label} for full documentation
    **/
    public function getInfoLabels()
    {
        if (empty($this->settings['category'])) return null;

        $cats = Pdb::lookup('files_cat_list');

        return array(
            'Category' => $cats[$this->settings['category']],
            'Order' => FileConstants::$order_names[$this->settings['order']],
        );
    }


    /**
    * Returns the SQL which should be used for ordering the articles
    **/
    private function getOrderSql()
    {
        switch ($this->settings['order']) {
            case FileConstants::ORDER_NAME:
                return 'files.name ASC';

            case FileConstants::ORDER_MANUAL:
                return 'joiner.record_order ASC';

            case FileConstants::ORDER_OLDEST:
                return 'IFNULL(files.date_published, files.date_added) ASC';

            case FileConstants::ORDER_NEWEST:
                return 'IFNULL(files.date_published, files.date_added) DESC';

            default:
                return 'id ASC';
        }
    }

}
