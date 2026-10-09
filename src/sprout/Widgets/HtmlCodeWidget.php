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


/**
* Spits out HTML code
**/
class HtmlCodeWidget extends Widget
{
    protected string $friendly_name = "HTML Code";
    protected string $friendly_desc = 'Arbitrary HTML code';


    /**
    * Return the front-end view of this widget
    *
    * @param int $orientation The orientation of the widget.
    **/
    public function render($orientation)
    {
        return $this->settings['code'] ?? '';
    }


    /**
    * Return the settings form for this widget
    **/
    public function getSettingsForm()
    {
        Form::nextFieldDetails('HTML', false);
        return Form::multiline('code');
    }
}
