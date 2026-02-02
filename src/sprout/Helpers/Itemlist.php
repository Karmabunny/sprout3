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

use InvalidArgumentException;
use PDOStatement;
use Closure;


/**
 * Used to generate HTML for a table of database records.
 * This is usually used for the admin/contents/* route which provides the main
 * UI to operators for a given {@see ManagedAdminController}
 */
class Itemlist
{
    public $items;
    public $main_columns;
    public $aggregate = [];
    public $checkboxes;
    public $ordering;
    public $table_class = 'main-list';

    private $row_classes_func = null;

    private $actions = array();
    private $actions_func = null;
    private $actions_classes = 'actions--link';


    public function __toString()
    {
        return (string) $this->render();
    }

    public function render(): string
    {
        $_GET['order'] = $_GET['order'] ?? null;
        $_GET['dir'] = $_GET['dir'] ?? null;

        if (empty($this->main_columns)) {
            throw new InvalidArgumentException('No main columns defined');
        }

        if ($this->items instanceof PDOStatement) {
            if ($this->items->rowCount() == 0) {
                return '';
            }
        } else {
            if (count($this->items) == 0) {
                return '';
            }
        }

        if (isset($this->actions['edit'])) {
            $edit_action = $this->actions['edit'];
            unset($this->actions['edit']);
        } else {
            $edit_action = null;
        }

        if ($this->ordering) {
            $base_url = Url::withoutArgs('order', 'dir', 'page');
        }

        // All the (raw) values for aggregate columns are stored so the aggregation can process them
        $aggregate_vals = [];
        foreach ($this->aggregate as $title => $agg_defn) {
            $aggregate_vals[$title] = [];
        }

        $val = "<table class=\"" . Enc::html($this->table_class) . "\">\n";

        $val .= "<thead>\n";
        $val .= "<tr>";

        if ($this->checkboxes) {
            $val .=  '<th class="selection-all">';
                $val .=  '<div class="field-element field-element--white field-element--checkbox field-element--checkbox--no-label">';
                    $val .=  '<div class="field-element__input-set">';
                        $val .=  '<div class="fieldset-input">';
                            $val .=  '<input id="itemList-select-all" type="checkbox">';
                            $val .=  '<label for="itemList-select-all"><span class="-vis-hidden">Select all</span></label>';
                        $val .=  '</div>';
                    $val .=  '</div>';
                $val .=  '</div>';
            $val .=  '</th>';
        }

        foreach ($this->main_columns as $title => $col_name) {
            if (
                $this->ordering
                and
                (
                    is_string($col_name)
                    or
                    (is_array($col_name) and $col_name[0] instanceof SortedColModifier)
                )
            ) {
                $val .= "<th class=\"table-sort-th\">";

                $field_name = is_array($col_name) ? $col_name[1] : $col_name;

                if ($_GET['order'] == $field_name and $_GET['dir'] == 'asc') {
                    $val .= "<a class=\"icon-after icon-keyboard_arrow_up table-sort\" href=\"{$base_url}order={$field_name}&dir=desc\" title=\"Data is currently sorted by this column\">";
                    $val .= $title;
                    $val .= "</a>";

                } else if ($_GET['order'] == $field_name and $_GET['dir'] == 'desc') {
                    $val .= "<a class=\"icon-after icon-keyboard_arrow_down table-sort\" href=\"{$base_url}order={$field_name}&dir=asc\" title=\"Data is currently sorted by this column (backwards)\">";
                    $val .= $title;
                    $val .= "</a>";

                } else {
                    $val .= "<a class=\"table-sort\" href=\"{$base_url}order={$field_name}\" title=\"Click to sort by this column\">";
                    $val .= $title;
                    $val .= "</a>";
                }

                $val .= "</th>";

            } else {
                $val .= "<th>";
                $val .= $title;
                $val .= "</th>";
            }
        }

        if (count($this->actions) or $this->actions_func) $val .=  "<th>&nbsp;</th>";

        $val .= "</tr>\n";
        $val .= "</thead>\n";

        $val .=  "<tbody>\n";
        foreach ($this->items as $item) {
            $classes = '';
            if (isset($this->row_classes_func)) {
                $func = $this->row_classes_func;
                $classes = $func($item);
            }

            // Fetch aggregate values from row ($item) and load into the temporary array
            // This is done on the raw values, rather than processed values, so callback columns won't work
            foreach ($this->aggregate as $title => $agg_defn) {
                $col_defn = $this->main_columns[$title];
                if (is_string($col_defn)) {
                    $aggregate_vals[$title][] = $item[$col_defn];
                } elseif (is_array($col_defn)) {
                    $aggregate_vals[$title][] = $item[$col_defn[1]];
                }
            }

            if ($classes) {
                $val .= '<tr class="' . Enc::html($classes) . '">';
            } else {
                $val .= "<tr>";
            }

            if ($this->checkboxes) {
                $val .= "<td class=\"selection\">";

                $val .=  '<div class="field-element field-element--white field-element--checkbox field-element--checkbox--no-label">';
                    $val .=  '<div class="field-element__input-set">';
                        $val .=  '<div class="fieldset-input">';
                            $val .=  "<input type=\"checkbox\" id=\"itemList-checkbox-{$item['id']}\" name=\"ids[]\" value=\"{$item['id']}\">";
                            $val .=  "<label for=\"itemList-checkbox-{$item['id']}\"><span class=\"-vis-hidden\">Select row</span></label>";
                        $val .=  '</div>';
                    $val .=  '</div>';
                $val .=  '</div>';
                $val .= "</td>";
            }

            $i = 0;
            foreach ($this->main_columns as $title => $defn) {
                if (is_array($defn) and !is_string($defn[1])) {
                    throw new InvalidArgumentException('Main column must either be a string, or an array with 0: ColModifier, 1: string');
                }
                $value = self::renderItem($defn, $item);

                if ($i++ == 0 and $edit_action) {
                    $url = $this->urlReplace($edit_action['url'], $item);

                    $url = Enc::html($url);
                    $val .=  "<td><a href=\"{$url}\">{$value}</a></td>";
                    continue;
                }

                $val .=  "<td>{$value}</td>";
            }

            if (count($this->actions) or $this->actions_func) {
                $val .=  "<td class=\"actions\">";

                foreach ($this->actions as $name => $details) {
                    $show = $details['show_func'];
                    if ($show and is_callable($show)) {
                        $result = $show($item);
                        if ($result == false) continue;
                    }

                    $url = $this->urlReplace($details['url'], $item);
                    $name = ucfirst($name);

                    $name = Enc::html($name);
                    $url = Enc::html($url);
                    $class = Enc::html(trim($this->actions_classes . ' ' . $details['classes']));
                    $val .= "<a href=\"{$url}\" class=\"{$class}\">{$name}</a> ";
                }

                if ($this->actions_func) {
                    $func = $this->actions_func;
                    $val .= $func($item);
                }

                $val .=  "</td>";
            }

            $val .=  "</tr>\n";
        }

        if (!empty($this->aggregate)) {
            $val .= "<tr class=\"main-list--aggregate\">\n";

            if ($this->checkboxes) {
                $val .= '<td></td>';
            }

            foreach ($this->main_columns as $title => $col_defn) {
                if (empty($this->aggregate[$title])) {
                    $value = '';
                } else {
                    $agg_defn = $this->aggregate[$title];

                    if (isset($agg_defn['value'])) {
                        $value = $agg_defn['value'];
                    } else {
                        $value = self::calculateAggregateColumn($agg_defn['operation'], $aggregate_vals[$title]);
                    }

                    if (($agg_defn['modifier'] ?? null) instanceof ColModifier) {
                        $value = $agg_defn['modifier']->modify($value, '', $item ?? []);
                    }

                    // Escape value, except if it was processed by an UnescapedColModifier
                    if (empty($agg_defn['modifier']) or !($agg_defn['modifier'] instanceof UnescapedColModifier)) {
                        $value = Enc::html($value);
                    }
                }

                $val .= "<td>{$value}</td>";
            }

            if (count($this->actions) or $this->actions_func) {
                $val .= '<td></td>';
            }

            $val .= "</tr>\n";
        }

        $val .= "</tbody>\n";
        $val .= "</table>\n";

        return $val;
    }


    /**
    * Set a function which should return the classes to use on the row.
    *
    *    string function mycallable(array $row)
    *
    * The return value should be a string of class names
    *
    * @example
    *    $itemlist->setRowClassesFunc(function($row){
    *        if ($row['id'] == 42) return 'ultimate';
    *        return '';
    *    });
    *
    * @param callable $func
    **/
    public function setRowClassesFunc($func)
    {
        $this->row_classes_func = $func;
    }


    /**
    * Adds an action to this itemlist.
    *
    * The special action 'edit' is used when the row is clicked.
    *
    * @param string $name Link label
    * @param string $url Link URL. This URL is processed by {@see Itemlist::urlReplace} during rendering
    * @param string $classes Additional classes for the A element
    * @param callable $show_func Function called for each row to show or hide this action for that row
    **/
    public function addAction($name, $url, $classes = '', ?callable $show_func = null)
    {
        $this->actions[$name] = ['url' => $url, 'classes' => $classes, 'show_func' => $show_func];
    }


    /**
     * Set link classes common for all actions
     * The default is "actions--link".
     *
     * @example
     *    $itemlist->setActionsClasses('button')
     *
     * @param string $classes Classes for the A element
     */
    public function setActionsClasses($classes)
    {
        $this->actions_classes = $classes;
    }


    /**
    * Set a function which should return content for the actions column
    * The func should have this signature:
    *
    *    string function mycallable(array $row)
    *
    * The return value should be HTML with the links
    **/
    public function setActionsFunc($func)
    {
        $this->actions_func = $func;
    }


    /**
     * Add an aggregate which operates on the values of a column
     *
     * @throws InvalidArgumentException Unknown operation
     * @param string $title Column to aggregate values of
     * @param string $operation Aggregation operation, 'sum', 'count', 'avg'
     * @param ColModifier $modifier Column modifier applied after aggregation to format the result
     */
    public function addAggregateColumn($title, $operation, ?ColModifier $modifier = null)
    {
        static $ops = ['sum', 'count', 'avg'];
        if (in_array($operation, $ops)) {
            $this->aggregate[$title] = [
                'operation' => $operation,
                'modifier' => $modifier,
            ];
        } else {
            throw new InvalidArgumentException("Unknown operation '{$operation}'");
        }
    }


    /**
     * Add an aggregate which is just a single pre-computed value
     *
     * @param string $title Column to aggregate values of
     * @param string $value Value to output for this column; this will be HTML-encoded on output
     */
    public function addAggregateValue($title, $value)
    {
        $this->aggregate[$title] = [
            'value' => $value,
        ];
    }


    /**
     * Calculate the result of an aggregation
     *
     * @param string $operation Aggregation operation, 'sum', 'count', 'avg'
     * @param array $values Raw values, direct from the database
     * @return mixed Aggregation result; typically an integer or a float
     */
    protected static function calculateAggregateColumn($operation, array $values)
    {
        switch ($operation) {
            case 'sum':
                return array_sum($values);
            case 'count':
                return count($values);
            case 'avg':
                return array_sum($values) / count($values);
        }
    }


    /**
    * Does this itemlist support checkboxes?
    **/
    public function setCheckboxes($checkboxes)
    {
        $this->checkboxes = $checkboxes;
    }


    /**
    * Does this itemlist support ordering?
    **/
    public function setOrdering($ordering)
    {
        $this->ordering = $ordering;
    }


    /**
    * Does the parameter replacements on an action url
    *
    * Replaces %% with the id of the record.
    **/
    private function urlReplace($url, $item)
    {
        $url = str_replace('%%', Enc::url($item['id']), $url);
        $url = str_replace('%ne%', $item['id'], $url);

        return $url;
    }


    /**
    * Renders an itemlist definition
    *
    * Definition can be one of:
    *  - A field name
    *  - An array with two indexes, 0 => ColModifier, 1 => field name
    *  - A Closure, which will receive one argument of the entire row as an array,
    *    and must return a string of HTML
    *
    * The Closure result supports a subset of HTML, {@see Text::limitedSubsetHtml} for more details
    *
    * @param mixed $defn
    * @param array|object $item_data Result row
    * @return string
    **/
    protected static function renderItem($defn, $item_data)
    {
        if (is_array($defn)) {
            if (isset($defn[0]) and $defn[0] instanceof UnescapedColModifier) {
                $col_name = isset($defn[1]) ? $defn[1] : '';
                return $defn[0]->modify($item_data[$col_name] ?? null, $col_name, $item_data);
            } else if (isset($defn[0]) and $defn[0] instanceof ColModifier) {
                $col_name = isset($defn[1]) ? $defn[1] : '';
                return str_replace("\n", '<br>', Enc::html($defn[0]->modify($item_data[$col_name] ?? null, $col_name, $item_data)));
            }
            return '';

        } elseif ($defn instanceof Closure) {
            return Text::limitedSubsetHtml($defn($item_data));

        } else {
            return Enc::html($item_data[$defn]);
        }
    }

}
