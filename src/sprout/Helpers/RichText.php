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

use Exception;

use Kohana;


/**
* Interface the display of a richtext field
**/
abstract class RichText {

    /**
    * Draw a rich text field
    *
    * Field type should be a class name sans 'RichText', e.g. 'TinyMCE4' for the 'TinyMCE4RichText' class.
    * Optionally, the name can be bollowed by : and a config group name, e.g. 'TinyMCE4:Lite' for the 'Lite' config.
    *
    * @param $field_name The name of the field to draw.
    * @param $content The content of the field, in HTML, but not escaped in any way.
    * @param $width The width of the field, in pixels. Default = 600
    * @param $height The height of the field, in pixels. Default = 300
    * @param $type Override the field type. Default as per configuration and $_GET/$_SESSION overrides
    **/
    static public function draw($field_name, $content, $width = 600, $height = 300, $type = null)
    {
        if ($type == null) $type = trim($_GET['_richtext'] ?? '');
        if ($type == null) $type = Kohana::config('sprout.rich_text_type');

        // Parse config group from 'type' var
        $matches = array();
        if (preg_match('/^([_a-zA-Z0-9]+):([-_a-zA-Z0-9]+)$/', $type, $matches)) {
            $type = $matches[1];
            $config_group = $matches[2];
        } else {
            $config_group = null;
        }

        $class_name = $type . 'RichText';
        if (strpos($class_name, '\\') === false) {
            $class_name = 'Sprout\\Helpers\\' . $class_name;
        }

        if (! class_exists($class_name)) {
            throw new Exception ("Unknown rich text type '{$type}'.");
        }

        $rich = new $class_name;
        return $rich->drawInternal($field_name, $content, $width, $height, $config_group);
    }


    /**
    * Shows a richtext field. Should output content directly
    *
    * @param string $field_name The name of the field
    * @param string $content The content of the richtext field, in HTML
    * @param int $width The width of the field, in pixels
    * @param int $height The height of the field, in pixels
    * @param string $config_group Specific configuration to use instead of the default
    **/
    abstract protected function drawInternal($field_name, $content, $width = 600, $height = 300, $config_group = null);

}


