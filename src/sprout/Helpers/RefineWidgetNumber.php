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

/**
* A number field widget
**/
class RefineWidgetNumber extends RefineWidget
{

    /**
    * Draws the widget.
    * @return string The HTML for the widget.
    **/
    public function render()
    {
        $name = Enc::html($this->name);
        $label = Enc::html($this->label);
        $value = Enc::html($this->getValue());

        $out = "<div class=\"field-element field-element--text field-element--small field-element--white refine-bar-{$name}\">";
            $out .= "<div class=\"field-label\">";
                $out .= "<label for=\"field1\">{$label}</label>";
            $out .= "</div>";
            $out .= "<div class=\"field-input\">";
                $out .= "<input type=\"number\" class=\"textbox\" name=\"{$name}\" value=\"{$value}\">";
            $out .= "</div>";
        $out .= "</div>";

        return $out;
    }

}
