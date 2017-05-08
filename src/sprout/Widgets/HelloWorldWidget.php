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

use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\WidgetArea;


/**
* Basically just a test widget
**/
class HelloWorldWidget extends Widget
{
    protected $friendly_name = "Hello world";
    protected $friendly_desc = "This is a test widget";


    /**
    * Return the front-end view of this widget
    *
    * @param int $orientation The orientation of the widget.
    **/
    public function render($orientation)
    {
        $out = '<p>' . Enc::html($this->settings['message']);

        if ($orientation == WidgetArea::ORIENTATION_TALL) {
            $out .= '<br>Orientation: Tall</p>';

        } else if ($orientation == WidgetArea::ORIENTATION_WIDE) {
            $out .= '<br>Orientation: Wide</p>';
        }

        return $out;
    }


    /**
    * Return the settings form for this widget
    **/
    public function getSettingsForm()
    {
        return Form::text('message');
    }

}
