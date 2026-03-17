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

use Sprout\Helpers\ContentReplace;
use Sprout\Helpers\Form;


/**
* Spits out HTML code
**/
class RichTextWidget extends Widget
{
    protected string $friendly_name = "Text block";
    protected string $friendly_desc = 'HTML text content which can include links and images';


    /**
     * Return the front-end view of this widget
     *
     * @param int $orientation The orientation of the widget; see e.g. {@see WidgetArea::ORIENTATION_WIDE}
     * @return string HTML to be displayed
     */
    public function render($orientation)
    {
        return ContentReplace::html($this->settings['text']) . '<div class="clear"></div>';
    }


    /**
     * Return the settings form for this widget
     *
     * @return string a richtext input field; its type is config-specified, with TinyMCE4 the default
     */
    public function getSettingsForm()
    {
        $richtext_width = Kohana::config('sprout.admin_richtext_width');
        $richtext_height = Kohana::config('sprout.admin_richtext_height');

        return Form::richtext('text', ['width' => $richtext_width, 'height' => $richtext_height]);
    }
}
