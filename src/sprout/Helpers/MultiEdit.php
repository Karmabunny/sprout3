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
 * UI system for rapid adding/editing of any number of (sub-)records
 *
 * @example
 *     $images = MultiEdit::load('gallery_images', ['gallery_id' => $gallery_id], 'record_order');
 *     MultiEdit::itemName('Image');
 *     MultiEdit::reorder();
 *     MultiEdit::setPostAddJavaScriptFunc('load_thumbnails');
 *     MultiEdit::display('images', $images, @$_SESSION['gallery']['field_errors']['multiedit_images']);
 */
class MultiEdit
{
    private static $post_add_func = null;
    private static $reorder = false;
    private static $item_name = null;
    private static $init_item = true;


    /**
     * Returns an array of data, which should be put into a view, and passed into MultiEdit::display.
     * @param string $table Table to load data from
     * @param array $where Conditions for WHERE clause, as per {@see Pdb::buildClause}
     * @param string|array $order Column(s) to order records by
     * @return array<int, array<string, mixed>> Each element is a row
     */
    public static function load($table, array $where = [], $order = 'id')
    {
        Pdb::validateIdentifier($table);
        if (is_string($order)) {
            $order = preg_split('/,\s*/', $order);
        }
        foreach ($order as $col) {
            Pdb::validateIdentifier($col);
        }

        if (count($where) == 0) $where = [1];

        $vals = [];
        $where = Pdb::buildClause($where, $vals);
        $q = "SELECT * FROM ~{$table}
            WHERE {$where}
            ORDER BY " . implode(', ', $order);
        return Pdb::q($q, $vals, 'arr');
    }


    /**
     * Set the name of a javascript function to call after each multiedit item is added
     *
     * The JavaScript function is called with the following arguments:
     *     $div     jQuery element for the outer div
     *     data     Array of field data OR null for a new record
     *     idx      The record index
     *
     * @example
     *     // You can use bare functions
     *     MultiEdit::setPostAddJavaScriptFunc('alert')
     * @example
     *     // Or object functions (this is good for testing)
     *     MultiEdit::setPostAddJavaScriptFunc('console.log')
     * @example
     *     // If you're insane, you can put a whole blob of JS in there
     *     MultiEdit::setPostAddJavaScriptFunc('(function($div,data,idx){  console.log($div);  })')
     * @param string $function_name
     */
    public static function setPostAddJavaScriptFunc($function_name) {
        self::$post_add_func = $function_name;
    }


    /**
    * Enable multiedit reordering
    **/
    public static function reorder()
    {
        self::$reorder = true;
    }


    /**
     * Include a initial empty item.
     *
     * @param bool $set
     * @return void
     */
    public static function initItem($set)
    {
        self::$init_item = $set;
    }


    /**
    * Set the item name for a multiedit.
    * e.g. 'image'
    **/
    public static function itemName($item_name)
    {
        self::$item_name = $item_name;
    }


    /**
     * Displays a multiedit field
     * @param string $key Key to group records for this multiedit
     * @param array|null $data Records from DB or session
     * @param array $errors Error messages, in the format [ index => [field => msg] ]
     */
    public static function display($key, $data, array $errors = [])
    {
        if ($key == '') return;

        Needs::fileGroup('multiedit');
        Needs::fileGroup('fb');
        Lnk::editformNeeds();

        // Get input as an array, and get field keys
        if (!empty($data) and count($data)) {
            $first = Sprout::iterableFirstValue($data);

            if (is_object($first)) {
                $first = get_class_vars(get_class($first));
            }

            $field_names = array_keys($first);

        } else {
            $field_names = array();
            $data = array();
        }

        // Transpose error messages
        foreach ($errors as $idx => $errs) {
            foreach ($errs as $field => $error) {
                $data[$idx]['error'][$field] = $error;
            }
        }

        $opts = array();
        $opts['key'] = $key;
        $opts['item_name'] = self::$item_name ? self::$item_name : 'item';
        $opts['field_names'] = $field_names;
        $opts['reorder'] = self::$reorder;
        $opts['init_item'] = self::$init_item;

        // Do output
        echo "<script type=\"text/javascript\">\n";
        echo "$(document).ready(function() {\n";
        echo "    $('#multiedit-{$key}').multiedit(\n";
        echo '        ', (self::$post_add_func ?: 'null'), ",\n";
        echo '        ', json_encode($data), ",\n";
        echo '        ', json_encode($opts), "\n";
        echo "    );\n";
        echo "});\n";
        echo "</script>\n";

        self::$post_add_func = null;
        self::$reorder = false;
        self::$item_name = null;
        self::$init_item = true;
    }

    /**
     * Returns true if the multiedit record is empty, false otherwise
     *
     * @param array $record Collated [field => value] pairs which are to be saved with a multiedit record
     * @param array $defaults Default values as [field => value] pairs; any field which is set to its default value
     *        isn't counted as non-empty; i.e. if all fields are left as their default values, the record is considered
     *        empty.
     * @return boolean
     */
    public static function recordEmpty($record, array $defaults = [])
    {
        foreach ($record as $field => $val) {
            if ($val == '') continue;
            if ($val == @$defaults[$field]) continue;
            return false;
        }
        return true;
    }
}


