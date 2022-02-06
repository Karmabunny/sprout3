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

use ReflectionClass;
use Sprout\Helpers\Form;


/**
 * Provides the search refinement options in the admin UI
 */
class RefineBar
{
    private $widgets = array();
    private $field_ops = [];
    private $controller_name = null;


    public function __construct($controller_name = null)
    {
        $this->$controller_name = $controller_name;
    }


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
        if (!empty($operator)) $this->field_ops[$widget->getName()] = $operator;
    }


    /**
     * Set refine bar group
     *
     * @deprecated
     * @param string $name
     * @return void
     */
    public function setGroup($name)
    {
    }


    /**
     * Generate Refine bar HTML
     *
     * @return string HTML
     */
    public function get()
    {
        $fields = [];

        foreach ($this->widgets as $widget)
        {
            $fields[$widget->getName()] = $widget->getLabel();
        }

        $view = new View('sprout/admin/refine_bar');
        $view->fields = $fields;
        $view->controller_name = $this->controller_name;

        return $view->render();
    }

    /**
     * Generate Refine bar HTML
     * @see RefineBar->get()
     *
     * @return void Echos HTML directly
     */
    public function render()
    {
        echo $this->get();
    }


    /**
    * Returns true if any of the refine bar widgets refer to the specified field
    **/
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
     * @return string Operator, e.g. 'CONTAINS'
     * @return null Operator should be auto-detected
     */
    public function getOperator($field_name)
    {
        return @$this->field_ops[$field_name];
    }


    /**
     * Return a list of search widgets, in groups
     *
     * @deprecated
     * @return array
     */
    public function getGroups()
    {
        return [];
    }


    /**
     * Return list of widgets
     * @return array [op => (array) operators]
     */
    public function getField($field)
    {
        foreach ($this->widgets as $widget)
        {
            if ($widget->getName() != $field) continue;

            $reflect = new ReflectionClass($widget);

            switch ($reflect->getShortName())
            {
                case 'RefineWidgetSelect':
                    return [
                        'op' => Form::dropdown('op', [], [
                            '=' => 'Is',
                            '!=' => 'Is not',
                        ]),
                        'val' =>  Form::dropdown('val', [], $widget->items),
                    ];

                case 'RefineWidgetDatepicker':
                    return [
                        'op' => Form::dropdown('op', [], [
                            '=' => 'Is',
                            '!=' => 'Is not',
                            '>' => 'After',
                            '<' => 'Before',
                        ]),
                        'val' => Form::datepicker('val'),
                    ];

                case 'RefineWidgetNumber':
                    return [
                        'op' => Form::dropdown('op', [], [
                            '=' => 'Is',
                            '!=' => 'Is not',
                            '>' => 'Greater than',
                            '<' => 'Less than',
                        ]),
                        'val' => Form::number('val'),
                    ];

                case 'RefineWidgetAutocomplete':
                    return [
                        'op' => Form::dropdown('op', [], [
                            '=' => 'Is',
                            '!=' => 'Is not',
                        ]),
                        'val' => Form::autocomplete('val', [], $widget->options),
                    ];

                case 'RefineWidgetTextbox':
                default:
                    return [
                        'op' => Form::dropdown('op', [], [
                            '=' => 'Is',
                            '!=' => 'Is not',
                            'contains' => 'Contains',
                        ]),
                        'val' => Form::text('val'),
                    ];
            }
        }

        return ['op' => $field, 'val' => null];
    }


    /**
     * Set controller for defining the conditions
     *
     * @param string $controller_name
     * @return void
     */
    public function setController($controller_name)
    {
        $this->controller_name = $controller_name;
    }


    /**
     * Return controller name that's using this refine bar
     *
     * @return string|null
     */
    public function getController()
    {
        return $this->controller_name;
    }
}
