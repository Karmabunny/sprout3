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

use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\WidgetArea;
use Sprout\Helpers\Widgets;


/**
* Shows a list of pages that are related to this one
**/
class ImageGalleryWidget extends Widget
{
    protected string $friendly_name = "Image Gallery";
    protected string $friendly_desc = 'A gallery of images from your media repository';
    protected array $default_settings = [
        'limit' => 100,
        'captions' => 1,
        'order' => 1,
    ];
    public string $classname = 'ImageGallery';

    private array $order_opts = [
        1 => 'Date (most recent at top)',
        2 => 'Date (oldest at top)',
        3 => 'Alphabetical by name',
        4 => 'Alphabetical (reverse)',
        5 => 'Manual (in category options)',
        6 => 'Stable random',
        7 => 'True random',
    ];

    // Thumbnail cropping directions
    private array $crop_opts = [
        'lt' => 'Top left',
        'ct' => 'Top center',
        'rt' => 'Top right',
        'lc' => 'Middle left',
        'cc' => 'Middle center',
        'rc' => 'Middle right',
        'lb' => 'Bottom left',
        'cb' => 'Bottom center',
        'rb' => 'Bottom right',
    ];

    // Whether this widget displays as grid or slider
    private array $display_opts = [
        'grid' => 'Gallery',
        'slider' => 'Slider',
    ];


    /**
    * Validate and cleanup the settings fields
    * @return bool True on success false on failure
    **/
    private function validateSettings()
    {
        $this->settings['category'] = (int) ($this->settings['category'] ?? 0);
        if ($this->settings['category'] <= 0) return false;

        $this->settings['limit'] = (int) ($this->settings['limit'] ?? 0);
        if ($this->settings['limit'] <= 0) $this->settings['limit'] = 100;

        $this->settings['captions'] = (bool) ($this->settings['captions'] ?? false);

        $this->settings['order'] = (int) ($this->settings['order']) ?? 0;
        if ($this->settings['order'] <= 0) $this->settings['order'] = 1;

        $this->settings['thumb_rows'] = (int) ($this->settings['thumb_rows'] ?? 0);
        if ($this->settings['thumb_rows'] < 2) $this->settings['thumb_rows'] = 5;

        $this->settings['num_images'] = (int) ($this->settings['num_images'] ?? 0);
        if ($this->settings['num_images'] < 1) $this->settings['num_images'] = 1;

        if (empty($this->settings['cropping']) or !array_key_exists($this->settings['cropping'], $this->crop_opts)) {
            $this->settings['cropping'] = 'cc';
        }

        if (empty($this->settings['display_opts']) or !array_key_exists($this->settings['display_opts'], $this->display_opts)) {
            $this->settings['display_opts'] = 'grid';
        }

        $this->settings['slider_dots'] = (int) ($this->settings['slider_dots'] ?? 0);
        $this->settings['slider_arrows'] = (int) ($this->settings['slider_arrows'] ?? 0);
        $this->settings['slider_autoplay'] = (int) ($this->settings['slider_autoplay'] ?? 0);
        $this->settings['slider_speed'] = (int) ($this->settings['slider_speed'] ?? 0);

        if ($this->settings['slider_speed'] <= 0) {
            $this->settings['slider_speed'] = 3;
        }

        return true;
    }


    /**
    * Does the front-end rendering of this widget
    *
    * @param int $orientation The orientation of the widget
    **/
    public function render($orientation)
    {
        if (!$this->validateSettings()) return '';

        if ($this->settings['limit'] > 0) {
            $limit = 'LIMIT ' . $this->settings['limit'];
        } else {
            $limit = 'LIMIT 100';
        }

        // Load the files from the database
        $order = $this->orderSql($this->settings['order']);
        $q = "SELECT
                files.name,
                files.filename,
                files.description
            FROM ~files AS files
            INNER JOIN ~files_cat_join AS joiner
                ON joiner.file_id = files.id
            WHERE joiner.cat_id = ?
            ORDER BY {$order}
            {$limit}";
        $res = Pdb::query($q, [$this->settings['category']], 'arr');

        // Filter to allow only jpg files
        $images = array();
        foreach ($res as $row) {
            if (strpos($row['filename'], 'jpg') !== false or strpos($row['filename'], 'jpeg') !== false) {
                $images[] = $row;
            }
        }

        // Load configuration details from sprout skin config if present
        $config = Kohana::config('sprout.image_gallery') ?? [];
        $config += [
            'thumb_size_2' => 'c600x600',
            'thumb_size_3' => 'c400x400',
            'thumb_size_4' => 'c300x300',
            'thumb_size_5' => 'c220x220',
            'full_size' => 'm800x600',
            'slider_size' => 'c800x450',
        ];

        if ($orientation == WidgetArea::ORIENTATION_TALL) {
            $view = new PhpView('sprout/image_gallery_tall_' . $this->settings['display_opts']);
        } else {
            $view = new PhpView('sprout/image_gallery_wide_' . $this->settings['display_opts']);
        }

        $view->config = $config;
        $view->captions = $this->settings['captions'];
        $view->num_thumbs = $this->settings['limit'];
        $view->images = $images;
        $view->idx = 1;
        $view->cropping = $this->settings['cropping'];
        $view->row_count = $this->settings['thumb_rows'];
        $view->slider_dots = $this->settings['slider_dots'];
        $view->slider_arrows = $this->settings['slider_arrows'];
        $view->slider_autoplay = $this->settings['slider_autoplay'];
        $view->slider_speed = $this->settings['slider_speed'];
        $view->slider_images = $this->settings['num_images'];

        return $view->render();
    }


    /**
    * Returns the settings HTML
    **/
    public function getSettingsForm()
    {
        // Get categories
        $q = "SELECT cat.id, CONCAT(cat.name, ' (', COUNT(file.id), ')')
            FROM ~files_cat_list AS cat
            LEFT JOIN ~files_cat_join AS joiner ON cat.id = joiner.cat_id
            LEFT JOIN ~files AS file ON joiner.file_id = file.id AND file.type = ?
                AND (file.filename LIKE '%.jpg' OR file.filename LIKE '%.jpeg')
            GROUP BY cat.id
            ORDER BY cat.name";
        $cats = Pdb::query($q, [FileConstants::TYPE_IMAGE], 'map');

        $view = new PhpView('sprout/gallery_widget_settings');
        $view->cats = $cats;
        $view->ordering = $this->order_opts;
        $view->cropping = $this->crop_opts;
        $view->display = $this->display_opts;

        return $view->render();
    }


    /**
    * Returns the SQL which should be used for ordering the images
    *
    * @param $order One of the $this->order_opts order types
    **/
    private function orderSql($order)
    {
        switch ($order) {
            case 1:
                return 'files.date_file_modified DESC';

            case 2:
                return 'files.date_file_modified ASC';

            case 3:
                return 'files.name ASC';

            case 4:
                return 'files.name DESC';

            case 5:
                return 'joiner.record_order ASC';

            case 6:
                return 'MD5(CONCAT(name, filename, date_file_modified)) ASC';

            case 7:
                return 'RAND()';

            default:
                return 'files.id ASC';
        }
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
            'Order' => $this->order_opts[$this->settings['order']],
        );
    }


    /**
    * Is there sufficent content to add this addon at the moment?
    * If this widget is not available, a string message should be returned
    * If this widget *is* available, NULL should be returned.
    **/
    public function getNotAvailableReason()
    {
        $cats = Pdb::lookup('files_cat_list');
        if (count($cats) == 0) {
            return 'No media repository categories found';
        }
        return NULL;
    }


    /**
     * Registered content replacement for embedded gallery via the editor
     *
     * @param string $html HTML content
     * @return string Modified HTML content
     */
    public static function contentReplace($html)
    {
        if (empty($html)) return '';

        $pattern = '<div class="sprout-editor--widget sprout-editor--gallery"';
        $pattern .= ' data-id="([0-9]+)"';
        $pattern .= ' data-max="([0-9]+)"';
        $pattern .= ' data-captions="([0-9]+)"';
        $pattern .= ' data-crop="([a-z]+)"';
        $pattern .= ' data-thumbs="([0-9]+)"';
        $pattern .= ' data-type="([a-z]+)"';
        $pattern .= ' data-ordering="([0-9]+)"';
        $pattern .= ' data-slider-dots="([0-9]+)"';
        $pattern .= ' data-slider-arrows="([0-9]+)"';
        $pattern .= ' data-slider-autoplay="([0-9]+)"';
        $pattern .= ' data-slider-speed="([0-9]+)"';
        $pattern .= '>';

        preg_match_all('/' . $pattern . '/s', $html, $matches, PREG_PATTERN_ORDER);
        list($title, $id, $max, $captions, $crop, $thumbs, $type, $ordering, $slider_dots, $slider_arrows, $slider_autoplay, $slider_speed) = $matches;

        if (!empty($id)) {
            for ($i = 0; $i < count($id); $i++) {
                $widget = Widgets::render(WidgetArea::ORIENTATION_EMAIL, 'Sprout\Widgets\ImageGalleryWidget', [
                    'category' => (int) $id[$i],
                    'captions' => (int) $captions[$i],
                    'thumb_rows' => (int) $thumbs[$i],
                    'cropping' => $crop[$i],
                    'limit' => (int) $max[$i],
                    'order' => (int) $ordering[$i],
                    'display_opts' => $type[$i],
                    'slider_dots' => $slider_dots[$i],
                    'slider_arrows' => $slider_arrows[$i],
                    'slider_autoplay' => $slider_autoplay[$i],
                    'slider_speed' => $slider_speed[$i],
                ]);

                $html = preg_replace('/<div class="sprout-editor--widget sprout-editor--gallery" (.*?)<\/div>/s', $widget, $html, 1);
            }
        }

        return $html;
    }
}
