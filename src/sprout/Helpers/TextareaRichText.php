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
* @package Admin
**/

/**
* Interface the display of a richtext field
**/
class TextareaRichText extends RichText
{

    /**
    * Shows a richtext field. Should output content directly
    *
    * @param string $field_name The field name
    * @param string $content The content of the richtext field, in HTML
    * @param int $width The width of the field, in pixels
    * @param int $height The height of the field, in pixels
    * @param string $config_group Unused in this class
    **/
    protected function drawInternal($field_name, $content, $width = 600, $height = 300, $config_group = null)
    {
        $field_name = Enc::html($field_name);
        $content = Enc::html($content);

        echo "<p><small>Enter HTML into the box below.</small></p>\n";
        echo "<textarea name=\"{$field_name}\" style=\"width: {$width}px; height: {$height}px;\">{$content}</textarea>\n";
    }

}


