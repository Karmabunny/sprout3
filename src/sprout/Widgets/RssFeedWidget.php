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

use Exception;

use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\RssFeed;
use Sprout\Helpers\View;


/**
* Spits out HTML code
**/
class RssFeedWidget extends Widget
{
    protected $friendly_name = 'RSS Feed';
    protected $friendly_desc = 'Display an RSS feed';


    /**
    * Return the front-end view of this widget
    *
    * @param int $orientation The orientation of the widget.
    **/
    public function render($orientation)
    {
        if (empty($this->settings['url'])) return null;

        try {
            $items = RssFeed::parse($this->settings['url']);
        } catch (Exception $ex) {
            return '<p>Unable to load news feed:<br>' . Enc::html($ex->getMessage()) . '</p>';
        }

        if (!empty($this->settings['limit'])) {
            $items = array_slice($items, 0, $this->settings['limit']);
        }

        $view = new View('sprout/rss_feed');
        $view->items = $items;

        return $view->render();
    }


    /**
    * Return the settings form for this widget
    **/
    public function getSettingsForm()
    {
        $out = '';

        Form::nextFieldDetails('URL', true);
        $out .= Form::text('url');

        Form::nextFieldDetails('Max number of posts', false);
        $out .= Form::text('limit');

        return $out;
    }
}
