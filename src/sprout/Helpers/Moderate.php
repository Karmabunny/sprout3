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

abstract class Moderate {
    protected $friendly_name = '- No name -';
    protected $db;



    public function __construct()
    {
    }


    /**
    * Return the 'friendly' name of this item
    **/
    public final function getFriendlyName() {
        return $this->friendly_name;
    }


    /**
     * Return the class name for this controller, expressed in CSS style, i.e. with dashes
     *
     * Example: When called from ModerateImages --> 'moderate-images'
     *
     * @return string Name of this PHP class, in a format suitable for use in CSS
     */
    public function getCssClassName(): string
    {
        $class_name = Sprout::removeNs(get_class($this));
        $class_name = Text::camel2lc($class_name);
        $class_name = str_replace('_', '-', $class_name);
        return $class_name;
    }


    /**
    * Return an array of one or more items which need moderating.
    *
    * The array should have the following format:
    * [] = array('id' => 'html')
    *      id      record identifier
    *      html    record preview html
    *
    * Return NULL on error
    **/
    public function getList()
    {
        return NULL;
    }


    /**
    * Approve the specified item.
    * This is called from within a transaction.
    **/
    public abstract function approve($id);


    /**
    * Delete the specified item.
    * Usually the best is to use the controller _deleteSave method.
    * This is called from within a transaction.
    **/
    public abstract function delete($id);


    /**
     * Overwritable render function for a given approval row
     *
     * @param int $id
     * @param int $idx
     * @param array|string $data
     *
     * @return string
     */
    public function renderListRow($id, $idx, $data)
    {
        $class = Enc::html(static::class);

        // Handle additional data being passed such as when implementing
        if ($this instanceof ModerateWithExtraDataInterface) {
            $html = $data['html'];
        } else {
            $html = $data;
        }

        // Set defaults if we're expecting them
        if ($this instanceof ModerateWithDefaultsInterface) {
            $default = $data['default'];
        } else {
            $default = 'app';
        }

        // Handle modified with notes where data is an array instead of an id => action map
        if ($this instanceof ModerateWithNotesInterface) {
            $field_action = "moderate[{$class}][{$id}][action]";
            $field_notes = '<tr style="border-bottom: 3px">';
            $field_notes = '<td colspan="4">';
            $field_notes .= $this->getNotesFieldHtml($id, $idx);
            $field_notes .= '</tr><tr><td colspan="4"><br></td></tr>';

        } else {
            $field_action = "moderate[{$class}][{$id}]";
            $field_notes = '';
        }

        $out = '<tr>';
        $out .= '<td>' . $html . '</td>';

        $checked = $default == 'app' ? ' checked' : '';
        $out .= "<td class=\"mod mod--approve\"><input type=\"radio\" name=\"{$field_action}\" value=\"app\" {$checked}></td>";

        $checked = $default == 'del' ? ' checked' : '';
        $out .= "<td class=\"mod mod--reject\"><input type=\"radio\" name=\"{$field_action}\" value=\"del\" {$checked}></td>";

        $checked = $default == '' ? ' checked' : '';
        $out .= "<td class=\"mod mod--do-nothing\"><input type=\"radio\" name=\"{$field_action}\" value=\"\" {$checked}></td>";

        $out .= '</tr>';

        $out .= $field_notes;

        return $out;
    }

}


