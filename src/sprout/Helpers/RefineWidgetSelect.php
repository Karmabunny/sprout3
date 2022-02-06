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
* A textbox widget
**/
class RefineWidgetSelect extends RefineWidget
{
    public $items;

    public function __construct($name, $label, $items)
    {
        parent::__construct($name, $label);
        $this->items = $items;
    }

    /**
    * Draws the widget.
    * @return string The HTML for the widget.
    **/
    public function render()
    {
        $name = Enc::html($this->name);
        $label = Enc::html($this->label);

        $out = "<div class=\"field-element field-element--white field-element--small field-element--select refine-bar-{$name}\">";
            $out .= "<div class=\"field-label\">";
                $out .= "<label for=\"field1\">{$label}</label>";
            $out .= "</div>";
            $out .= "<div class=\"field-input\">";
                $out .= "<select name=\"{$name}\">";
                $out .= "<option value=\"\">- Select -</option>";

                $selected_key = Enc::html($this->getValue());
                foreach ($this->items as $key => $val) {
                    $key = Enc::html($key);
                    $val = Enc::html($val);

                    if ($key == $selected_key) {
                        $out .= "<option value=\"{$key}\" selected>{$val}</option>";
                    } else {
                        $out .= "<option value=\"{$key}\">{$val}</option>";
                    }
                }

                $out .= "</select>";
            $out .= "</div>";
        $out .= "</div>";



        return $out;
    }

}
