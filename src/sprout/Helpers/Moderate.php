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
 * A base class for moderation.
 *
 * Implement getList() approve() and delete() for a basic moderation component.
 *
 * @package Sprout\Helpers
 */
abstract class Moderate implements ModerateInterface
{
    protected $friendly_name = '- No name -';


    public function __construct()
    {
    }


    /**
     * Return the 'friendly' name of this item
     *
     * @return string
     */
    public final function getFriendlyName()
    {
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


    /** @inheritdoc */
    public function setData($id, array $data): bool
    {
        return true;
    }


    /**
    * Return an array of one or more items which need moderating.
    *
    * The array should have the following format:
    * [] = array('id' => 'html')
    *      id      record identifier
    *      html    record preview html
    *
    * @return string[]|null [ id => html ]
    **/
    public function getList()
    {
        return NULL;
    }


    /**
     * Overwritable render function for a given approval row.
     *
     * This decorates the HTML snippet given the `getList()` method.
     *
     * @param int $id
     * @param string $html inner html
     * @param string $default_action pre-checked approval action
     *   - 'app' = approve
     *   - 'del' = delete
     *   - '' = do nothing
     * @return string
     */
    public function getRowHtml($id, $html, $default_action)
    {
        $out = '<tr>';
        $out .= '<td>' . $html . '</td>';

        $checked = $default_action == 'app' ? ' checked' : '';
        $out .= "<td class=\"mod mod--approve\"><input type=\"radio\" name=\"{$id}[action]\" value=\"app\" {$checked}></td>";

        $checked = $default_action == 'del' ? ' checked' : '';
        $out .= "<td class=\"mod mod--reject\"><input type=\"radio\" name=\"{$id}[action]\" value=\"del\" {$checked}></td>";

        $checked = $default_action == '' ? ' checked' : '';
        $out .= "<td class=\"mod mod--do-nothing\"><input type=\"radio\" name=\"{$id}[action]\" value=\"\" {$checked}></td>";

        $out .= '</tr>';

        return $out;
    }


    /** @inheritdoc */
    public function render(): string
    {
        $out = '<h3>' . Enc::html($this->getFriendlyName()) . '</h3>';

        $list = $this->getList();

        if ($list === null) {
            $out .= '<p><i>Error: Unable to load record list for moderation.</i></p>';
            return $out;
        }

        if (count($list) == 0) {
            $out .= '<p><i>Nothing needs moderation.</i></p>';
            return $out;
        }

        $css_name = $this->getCssClassName();

        $out .= '<table class="main-list main-list-no-js moderation ' . $css_name . '">';
        $out .= '<thead>';
        $out .= '<tr><th>Item details</th><th class="mod">Approve</th><th class="mod">Delete</th><th class="mod">Do nothing</th></tr>';
        $out .= '</thead><tbody>';

        foreach ($list as $id => $html) {
            $out .= $this->getRowHtml($id, $html, 'app');
        }

        $out .= '</tbody></table>';
        return $out;
    }
}


