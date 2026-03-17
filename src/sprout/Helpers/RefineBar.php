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
 * Provides the search refinement options in the admin UI
 */
class RefineBar
{
    /** @var RefineWidget[] */
    private $widgets = array();

    /** @var string */
    private $curr_group = 'General';

    /** @var array<string,RefineWidget[]> */
    private $groups = array();

    /** @var array<string,string|null> */
    private $field_ops = [];


    /**
     * Add a refine widget
     *
     * @param RefineWidget $widget Refinement widget to add
     * @param string $operator Operator for WHERE clause, e.g. '=' or 'CONTAINS'
     *     The available values are the operators for {@see Pdb::buildClause}
     *     The value null indicates "auto-guess", which is CONTAINS for strings and = for integers
     * @return void
     */
    public function addWidget(RefineWidget $widget, $operator = null)
    {
        $this->widgets[] = $widget;
        $this->groups[$this->curr_group][] = $widget;
        if (!empty($operator)) {
            $this->field_ops[$widget->getName()] = $operator;
        }
    }

    /**
     * Set the current group
     *
     * @param string $name Group name, e.g. 'General'
     * @return void
     */
    public function setGroup($name)
    {
        $this->curr_group = Enc::html($name);
    }


    /**
     * Gets the bar
     *
     * @return string HTML
     */
    public function get()
    {
        $out = '';

        $get_fields = $_GET;
        unset ($get_fields['page']);
        foreach ($this->widgets as $widget) {
            unset ($get_fields[$widget->getName()]);
        }

        foreach ($get_fields as $name => $val) {
            $name = Enc::html($name);
            $val = Enc::html($val);
            $out .= "<input type=\"hidden\" name=\"{$name}\" value=\"{$val}\">";
        }

        $out .= "<div class=\"refine-bar -clearfix\">";
            $out .= "<form action=\"\" method=\"get\">";
                $out .= "<h3>Search</h3>";

                $out .= "<div class=\"refine-list -clearfix\">";
                    $index = 0;
                    foreach ($this->widgets as $widget) {
                        $html = $widget->render();
                        $label = Enc::html($widget->getLabel());

                        if ($html && $index < 4) {
                            $filterLevel = "main";
                            $out .= '<div class="refine-list-item refine-list-main">';
                            $out .= $html;
                            $out .= '</div>';
                        } else if($html) {
                            $out .= '<div class="refine-list-item refine-list-advanced">';
                            $out .= $html;
                            $out .= '</div>';
                        }
                        $index++;
                    }
                $out .= "</div>";

                $out .= "<div class=\"refine-submit\">";
                    $out .= "<button type=\"submit\" class=\"refine-submit button button-green button-small\">Update</button>";
                $out .= "</div>";

                if($index >= 4) {
                    $out .= "<button type=\"button\" class=\"refine-advanced-button icon-link-button icon-before icon-keyboard_arrow_down\">Advanced</button>";
                }

            $out .= "</form>";
        $out .= "</div>";

        return $out;
    }

    /**
    * Renders the bar
    *
    * @return void echoes HTML
    */
    public function render()
    {
        echo $this->get();
    }


    /**
     * Returns true if any of the refine bar widgets refer to the specified field
     *
     * @param string $field_name Field name, e.g. 'first_name'
     * @return bool True if the field is referenced by a widget
     */
    public function hasField($field_name)
    {
        foreach ($this->widgets as $w) {
            if ($w->getName() == $field_name) return true;
        }

        return false;
    }


    /**
     * Return the operator to use in WHERE clauses, for a given field
     *
     * @param string $field_name Field, e.g. 'first_name'
     * @return string|null Operator e.g. 'CONTAINS' or null to auto-detect
     */
    public function getOperator($field_name)
    {
        return $this->field_ops[$field_name] ?? null;
    }


    /**
     * Return a list of search widgets, in groups
     *
     * @return array<string,RefineWidget[]>
     */
    public function getGroups()
    {
        return $this->groups;
    }
}


