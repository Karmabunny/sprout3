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

use Sprout\Helpers\Constants;
use Sprout\Helpers\Enc;


/**
* Shows a box for searching documents
**/
class AdvancedSearchFormWidget extends Widget
{
    protected $friendly_name = "Advanced Search Form";
    protected $friendly_desc = 'An advanced search form for the site search';


    /**
    * Return the front-end view of this widget
    *
    * @param int $orientation The orientation of the widget.
    **/
    public function render($orientation)
    {

        $out = '<form action="SITE/advanced_search" method="get">';

        // Keyword
        $out .= '<p><b>Keyword:</b>';
        $out .= '<br><input type="text" name="q" value="' . Enc::html($_GET['q']) . '" class="textbox"></p>';

        // Tag
        $out .= '<p><b>Tag:</b>';
        $out .= '<br><input type="text" name="tag" value="' . Enc::html($_GET['tag']) . '" class="textbox"></p>';

        // Date
        $out .= '<p><b>Date:</b>';
        $out .= '<br><select name="date">';
        $out .= '<option value="">- Select -</option>';
        foreach (Constants::$relative_dates as $idx => $name) {
            $name = Enc::html($name);
            $sel = ($idx == $_GET['date'] ? ' selected' : '');
            $out .= "<option value=\"{$idx}\"{$sel}>{$name}</option>";
        }
        $out .= '</select></p>';

        $out .= '<p><input type="submit" value="Search" class="button"></p>';

        $out .= '</form>';

        return $out;
    }

}


