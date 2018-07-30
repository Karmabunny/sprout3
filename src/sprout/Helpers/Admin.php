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

use Sprout\Exceptions\QueryException;


/**
* Sorter for widgets
**/
function _widgetSort($a, $b) {
    $a = Widgets::instantiate($a);
    $b = Widgets::instantiate($b);
    return strcmp($a->getFriendlyName(), $b->getFriendlyName());
}

/**
* Useful functions for the admin
**/
class Admin
{
    static private $error_msgs = array(
        'required' => 'This field is required',
        'length\[0,([0-9]+)\]' => 'Too long, max length is $1 characters',
        'length\[([0-9]+),([0-9]+)\]' => 'Incorrect length, must be between $1 and $2 characters',
        'length\[([0-9]+)\]' => 'Incorrect length, must be exactly $1 characters',
        'alphaDash' => 'Field can only contain letters, numbers, underscores (_) and dashes (-)',
        'emailUnique' => 'Email address is not unique',
        'email' => 'Email address is not valid',
        'matches\[password.*' => 'Password fields do not match',
        'numMin\[([-0-9]+)\]' => 'Number must be at least $1',
        'numMax\[([-0-9]+)\]' => 'Number must be at most $1',
        'numBetween\[([-0-9]+),([-0-9]+)\]' => 'Number must be between $1 and $2 inclusive',
        'check_redirect' => 'URL must begin with http:// (external url) or / (internal url)',
        'exclusive\[(.*)\]' => 'These fields cannot be used together, only one of them may be used',
        'check_controller_entrance\[.*\]' => 'Only one page may reference any given module',

        'raw\[(.+)\]' => '$1',
        '.+' => 'Incorrect value provided',
    );
    private static $cat_tablename;
    private static $cat_singlecat = false;

    /**
    * Finds an appropriate error message for the specified error code
    **/
    public static function lookupErrMsg($err)
    {
        $message = null;

        if ($err instanceof AdminError) {
            return $err->getMessage();
        }

        foreach (self::$error_msgs as $search => $replace) {
            $count = 0;
            $message = preg_replace('/^'.$search.'$/', $replace, $err, 1, $count);
            if ($count > 0) break;
        }

        if (! $message) $message = 'Invalid value provided.';

        return $message;
    }


    /**
    * Shows a per-field error message
    *
    * @param string $field_name The name of the field to show the error message for.
    **/
    public static function fieldError($field_name, $scope = 'admin')
    {
        if (empty($_SESSION[$scope]['field_errors'][$field_name])) return;

        $error = self::lookupErrMsg($_SESSION[$scope]['field_errors'][$field_name]);

        $class = Enc::id('field-error-' . $field_name);

        $out = "<span class=\"field-error {$class}\">";
        $out .= str_replace(' ', '&nbsp;', Enc::html($error));
        $out .= '</span>';

        return $out;
    }


    /**
    * Clears all pre-field error messages
    **/
    public static function clearFieldErrors($scope = 'admin')
    {
        unset ($_SESSION[$scope]['field_errors']);
    }


    /**
    * Shows a UI for the list of widgets, for editing
    *
    * @param string $field_name Name of the field to store the final value when the form is submitted
    * @param WidgetArea $area The area that is being edited
    * @param array $curr_widgets A list of the widgets currently being used, in order, as db rows with keys:
    *          type       string    Class name, e.g. 'RichText'
    *          settings   string    Opaque JSON string
    *          conditions string    Opaque JSON string
    *          active     int       1 for active, 0 for inactive
    *          heading    string    HTML H2 rendered front-end within widget
    * @param boolean $enable_all Toggle whether all the widgets are enabled by default (defaults to true)
    **/
    public static function widgetList($field_name, WidgetArea $area, $curr_widgets, $enable_all = true)
    {
        Needs::module('widget_list');

        if ($curr_widgets == null) {
            $curr_widgets = [];
        }

        $widget_list_id = 'wl-' . Enc::id($field_name);

        echo '<script type="text/javascript">';
        echo "$(document).ready(function() {\n";
        echo "    var list = new widget_list('", Enc::js($field_name), "');\n";
        foreach ($curr_widgets as $widget) {
            $inst = Widgets::instantiate($widget['type']);
            $eng_name = Enc::js($inst->getFriendlyName());

            if (!$enable_all) $widget['active'] = 0;

            $add_opts = [
                'type' => $widget['type'],
                'label' => $eng_name,
                'settings' => $widget['settings'],
                'conditions' => $widget['conditions'],
                'active' => (bool)$widget['active'],
                'heading' => @$widget['heading'],
            ];

            echo "    list.add_widget(", json_encode($add_opts), ");\n";
        }
        echo "\n";
        echo "    $('#{$widget_list_id}').bind('add-widget', function(e, widget_name, english_name) {\n";
        echo "        list.add_widget({ type: widget_name, label: english_name, settings: '', active: true });\n";
        echo "        return false;\n";
        echo "    });\n";
        echo "});\n";
        echo '</script>';

        if ($enable_all) {
            echo "<div id=\"{$widget_list_id}\" class=\"widget-list\">\n";
        } else {
            echo "<div id=\"{$widget_list_id}\" class=\"widget-list all-collapsed\">\n";
        }
        echo '<div class="widgets-sel"></div>';
        echo '<div class="widgets-empty">This area does not have any content blocks. Click the button below to add a content block:</div>';
        echo '<div class="content-block-button-wrap">';
        echo "<input type=\"checkbox\" id=\"{$widget_list_id}--add-btn\" class=\"giant-popup-checkbox -vis-hidden\">";

        if ($enable_all) {
            $add_tooltip = 'Add a block of content.';
            if ($field_name == "embedded") {
                $add_tooltip = 'Add a block of content to the page.';
            } else if ($field_name == "sidebar") {
                $add_tooltip = 'Add a block of content to the sidebar.';
            } else if ($field_name == "email") {
                $add_tooltip = 'Add a block of content to the email.';
            }
            echo "<label for=\"{$widget_list_id}--add-btn\" class=\"button button-green button-regular button-block add-content-block-button\">Add content block";
            echo '<span class="tooltip-wrapper">';
            echo '<span class="tooltip-trigger">';
            echo '<span class="tooltip-trigger-icon icon-before icon-live_help"></span>';
            echo '</span>';
            echo '<span class="tooltip-content">';
            echo Enc::html($add_tooltip);
            echo '</span>';
            echo '</span>';
            echo "</label>";
        }


        echo '<div class="giant-popup-outer">';
        echo '<div class="giant-popup-wrapper">';
        echo '<div class="giant-popup-inner">';

        echo "<label for=\"{$widget_list_id}--add-btn\" class=\"giant-popup-close-button icon-before icon-close\">Close</label>";
        echo '<h2 class="giant-popup-title">Content blocks</h2>';
        echo '<ul class="giant-popup-content columns -clearfix">';

        $tiles = Register::getWidgetTiles($area->getName());
        ksort($tiles);

        // For the "embedded" area, sort the 'Content' tile to the start and the 'Advanced' tile to the end
        if ($area->getName() === 'embedded') {
            $content = $tiles['Text blocks'];
            $collections = $tiles['Collections'];
            $advanced = $tiles['Advanced'];
            unset($tiles['Text blocks'], $tiles['Collections'], $tiles['Advanced']);
            array_unshift($tiles, $collections);
            array_unshift($tiles, $content);
            array_push($tiles, $advanced);
        }

        $index = 0;
        foreach ($tiles as $tile) {

            if ((++$index) % 4 == 0) {
                echo '<li class="giant-popup-item column column-3 column-last">';
            } else {
                echo '<li class="giant-popup-item column column-3">';
            }
            echo '<div class="giant-popup-item-inner">';

            echo '<div class="giant-popup-item-title-wrapper">';
            echo '<div class="giant-popup-item-title-icon icon-before icon-', Enc::html($tile['icon']), '"></div>';
            echo '<h3 class="giant-popup-item-title">', Enc::html($tile['name']), '</h3>';
            echo '</div>';

            echo '<div class="giant-popup-item-content -clearfix">';
            echo '<div class="giant-popup-item-links">';
            echo '<ul class="list-style-1">';
            foreach ($tile['widgets'] as $widg => $name) {

                $inst = Widgets::instantiate($widg);
                $desc = $inst->getFriendlyDesc();

                echo '<li><a class="widget-list-new-widget" href="javascript:;" data-name="', Enc::html($widg), '" title="' . Enc::html($desc) . '">', Enc::html($name), '</a></li>';
            }
            echo '</ul>';
            echo '</div>';
            echo '</div>';

            echo '</div>';
            echo '</li>';
        }

        echo '</ul>';

        echo '</div>';
        echo '</div>';
        echo '</div>';

        echo "</div>\n";
        echo "</div>\n";
    }


    /**
    * Deprecated wrapper around {@see Fb::pageDropdown}
    *
    * @param string $field_name The name of the field
    * @param int $selected The id of the page to select
    * @param int $exclude The id of the page to exclude from the list
    * @param int $subsite_id The subsite of pages to show. Defaults to the current admin subsite
    * @param string $top_text Text for the top (id 0) item
    **/
    public static function pageDropdown($field_name, $selected = null, $exclude = null, $subsite_id = null, $top_text = 'None (top level page)')
    {
        echo Fb::pageDropdown($field_name, [], [
            'exclude' => [$exclude],
        ]);
    }


    /**
    * Render a tree of nodes for the navigation area of the admin
    * Used by the pages module
    *
    * @param Treenode $node The node to render
    * @param array $actions Additional links to show in the cog icon menu; keys: url, class, name
    * @param int $depth How deep in the tree the rendering is, starts at 1 for the top-level
    * @return void Outputs HTML directly
    **/
    public static function navigationTreeNode($node, array $actions, $depth = 1)
    {
        $admin_perms = AdminPerms::checkPermissionsTree('pages', $node['id']);

        $name = Enc::html(Text::limitChars($node['name'], 35, '...'));

        $class = "node depth{$depth}";
        $class .= ($admin_perms ? ' allow-access' : ' no-access');

        if (count($node->children) > 0) {
            $class .= ' has-children collapsed';
        }

        if (self::getControllerSlug() === 'page') {
            if (self::getRecordId() == $node['id']) {
                $class .= ' active-node';
            } else if ($node->findNodeValue('id', self::getRecordId())) {
                $class .= ' active-parent-node';
                $class = str_replace(' collapsed', '', $class);
            }
        }

        // Render node
        echo "<li class=\"{$class}\" data-id=\"{$node['id']}\">";
        echo "<div>";

        $action = reset($actions);
        $url = str_replace('%%', $node['id'], $action['url']);
        echo "<a class=\"node-link\" href=\"{$url}\">{$name}</a>";

        if (count($node->children) > 0) {
            echo "<button class=\"tree-list-expand-button icon-before icon-keyboard_arrow_right\" type=\"button\">Expand</button>";
        }
        echo "<button class=\"tree-list-settings-button icon-before icon-settings\" type=\"button\">Settings</button>";

        echo "<div class=\"tree-list-settings-dropdown dropdown-box\">";
        echo "<ul class=\"tree-list-settings-dropdown-list list-style-2\">";
        foreach ($actions as $action) {
            $url = str_replace('%%', $node['id'], $action['url']);
            $class = trim('tree-list-settings-dropdown-list-item ' . @$action['class']);
            echo "<li class=\"{$class}\"><a href=\"", Enc::html($url), "\">", Enc::html($action['name']), "</a></li>";
        }
        echo "</ul>";
        echo "</div>";
        echo "</div>";


        // Render children
        if (count($node->children) > 0 and $admin_perms) {
            echo "<ul class=\"node-children-list\">";

            $depth++;

            foreach ($node->children as $child) {
                self::navigationTreeNode($child, $actions, $depth);
            }

            echo "</ul>";

        }

        echo "</li>";
    }


    /**
    * For a set of multi-edit fields, loads the data into a much more usable array
    *
    * Input:
    *   [phone] = ('1234 1234', '4321 4321')
    *   [type] = ('Mobile', 'Home')
    *
    * Output:
    *   [1] = ('phone' => '1234 1234', 'type' => 'Mobile')
    *   [2] = ('phone' => '4321 4321', 'type' => 'Home')
    *
    * @param array $dataset The original data to use
    * @param array $field_names An array of the names of the fields to use
    **/
    public static function multieditBuild($dataset, $field_names)
    {
        $records = array();

        $primary = array_shift($field_names);

        foreach ($dataset[$primary] as $idx => $value) {
            $row[$primary] = $value;
            foreach ($field_names as $name) {
                $row[$name] = $dataset[$name][$idx];
            }

            $has_data = false;
            foreach ($row as $val) {
                if ($val != '') {
                    $has_data = true;
                    break;
                }
            }

            if ($has_data) $records[] = $row;
        }

        array_pop($records);    // the last one is a dummy

        return $records;
    }


    /**
    * Outputs a list of checkboxes.
    *
    * If the $field parameter is provided, multiple checkboxes with the same field name will be rendered.
    *    $data should be a key-value pair, with the keys being the value of the checkbox, and the value being the label.
    *    $selected should be an array of selected checkbox ids.
    *
    * If the $field parameter is omitted (null), multiple checkboxes widh different field names will be rendered.
    * The checkboxes will be binary checkboxes with a value of 1.
    *    $data should be a key-value pair, with the keys being the field name, and the value being the label.
    *    $selected should be a key-value pair, with the keys being the field name, and the value being 1 or 0.
    **/
    public static function checkboxList($field, $data, $selected)
    {
        echo "<div class=\"checkbox-list-wrapper\">\n";
            echo "<div class=\"checkbox-list\">\n";

            $common_field = false;
            if ($field != null) {
                $common_field = true;
                if (! preg_match('/\[\]$/', $field)) $field .= '[]';
            }

            echo "<div class=\"field-element field-element--white field-element--checkbox'\">";
                echo "<fieldset class=\"fieldset--checkboxboollist\">";
                    echo "<legend class=\"fieldset__legend\">Categories</legend>";
                    echo "<div class=\"field-element__input-set\">";

                        $val = '';
                        foreach ($data as $id => $name) {
                            if ($common_field) {
                                $val = $id;
                                $html_id = Enc::id($field . $val);
                                $checked = @in_array($id, $selected);

                            } else {
                                $field = $id;
                                $html_id = Enc::id($field . $val);
                                $val = 1;
                                $checked = (bool) @$selected[$id];
                            }

                            echo "<div class=\"fieldset-input\">";
                                if ($checked) {
                                    echo '<input type="checkbox" name="', Enc::html($field), '" value="', Enc::html($val), '" id="', $html_id, '" checked>';
                                } else {
                                    echo '<input type="checkbox" name="', Enc::html($field), '" value="', Enc::html($val), '" id="', $html_id, '">';
                                }
                                echo "<label for=\"{$html_id}\">";
                                echo Enc::html($name);
                                echo "</label>";
                            echo "</div>";
                        }

                        echo "</div>";
                    echo "</fieldset>";
                echo "</div>";

            echo "</div>\n";
        echo "</div>\n";
    }


    public static function setCategoryTablename($name)
    {
        self::$cat_tablename = $name;
    }

    public static function setCategorySinglecat($value)
    {
        self::$cat_singlecat = $value;
    }

    /**
    * Outputs an interface for selecting multiple categories
    *
    * @param $field The field name
    * @param $data An array of key-value pairs, with the keys being the id of the category, and the value being the name.
    * @param $selected An array of selected category ids.
    **/
    public static function categorySelection($field, $data, $selected)
    {
        if (! self::$cat_tablename) {
            echo '<p>ERROR: <code>Admin::setCategoryTablename()</code> has not been called.</p>';
            return;
        }

        if (! preg_match('/\[\]$/', $field)) $field .= '[]';

        if (self::$cat_singlecat and @count($selected) > 1) {
            $selected = array_slice($selected, 0, 1, true);
        }

        $type = (self::$cat_singlecat ? 'radio' : 'checkbox');

        echo "<div class=\"onthefly-catadd-wrapper\">\n";

        echo "<div class=\"onthefly-catadd-table-wrapper\">\n";
            if(!empty($data)){
                echo "<div class=\"checkbox-list-wrapper\">\n";
                    echo "<div class=\"checkbox-list category-selection\">\n";

                        echo "<div class=\"field-element field-element--{$type}'\">";
                            echo "<fieldset class=\"fieldset--{$type}boollist\">";
                                echo "<legend class=\"fieldset__legend\">Categories</legend>";
                                echo "<div class=\"field-element__input-set\">";

                                    foreach ($data as $id => $name) {
                                        $html_id = Enc::id($field . $id);
                                        $checked = @in_array($id, $selected);

                                        echo "<div class=\"fieldset-input\">";
                                            if ($checked) {
                                                echo '<input type="', $type, '" name="', Enc::html($field), '" value="', Enc::html($id), '" id="', $html_id, '" checked>';
                                            } else {
                                                echo '<input type="', $type, '" name="', Enc::html($field), '" value="', Enc::html($id), '" id="', $html_id, '">';
                                            }
                                            echo "<label for=\"{$html_id}\">";
                                            echo Enc::html($name);
                                            echo "</label>";
                                        echo "</div>";
                                    }

                                echo "</div>";
                            echo "</fieldset>";
                        echo "</div>";

                    echo "</div>\n";
                echo "</div>\n";
            }
        echo "</div>\n";

        // Show category quickadd, if allowed
        $controller = Inflector::singular(Category::tableCat2main(self::$cat_tablename));
        if (AdminPerms::controllerAccess($controller, 'categories')) {
            echo '<div class="onthefly-catadd -clearfix" data-tablename="' . self::$cat_tablename . '" data-singlecat="' . ((int)self::$cat_singlecat) . '" data-field="' . $field . '">';
            echo Csrf::token();
            echo '<div class="field-element field-element--white field-element--text field-element--small">';
            echo '<div class="field-label"><label for="onthefly-catadd">Add a new category</label></div>';
            echo '<div class="field-input"><input type="text" id="onthefly-catadd" spellcheck="true" class="textbox" placeholder="Category name"></div>';
            echo "</div>\n";
            echo '<div class="field-element field-element--white field-element--button field-element--small">';
            echo '<button id="onthefly-catadd-button" type="button" class="button button-green button-small onthefly-catadd icon-after icon-add">Add</button>';
            echo "</div>\n";
            echo "</div>\n";
        }

        echo "</div>\n";

    }

    /**
    * Outputs a list of radiobuttons, which will all use the same field name
    *
    * @param string $field The field name.
    * @param array $data The data. Key is the radiobutton value, Value is used in the label for the radiobutton.
    * @param array $selected The selected item
    **/
    public static function radioList($field, $data, $selected)
    {
        $field_id = Enc::id($field);
        $error = self::fieldError($field);

        echo "<div class=\"checkbox-list-wrapper\">\n";
            echo "<div class=\"checkbox-list\">\n";

                echo "<div class=\"field-element field-element--white field-element--radio'\">";
                    echo "<fieldset class=\"fieldset--checkboxboollist\">";
                        echo "<legend class=\"fieldset__legend\">Categories</legend>";
                        echo "<div class=\"field-element__input-set\">";

                            foreach ($data as $id => $name) {
                                echo "<div class=\"fieldset-input\">";
                                    if ($id == $selected) {
                                        echo '<input type="radio" name="', Enc::html($field), '" value="', Enc::html($id), '" id="', $field_id, $id, '" checked>';
                                    } else {
                                        echo '<input type="radio" name="', Enc::html($field), '" value="', Enc::html($id), '" id="', $field_id, $id, '">';
                                    }
                                    echo "<label for=\"{$field_id}{$id}\">";
                                    echo Enc::html($name);
                                    echo "</label>";
                                echo "</div>";
                            }

                        echo "</div>";
                    echo "</fieldset>";
                echo "</div>";

            echo "</div>\n";
        echo "</div>\n";

        if ($error) echo "{$error}";

    }


    /**
    * Renders an interface for editing attributes
    * Uses a multiedit
    **/
    public static function attrEditor($current_attrs)
    {
        $attrs = Register::getPageattrs();
        asort($attrs);

        // Load any required needs for all registered attrs
        $classes = array();
        foreach ($attrs as $val) {
            $classes[$val[1]] = $val[1];
        }
        foreach ($classes as $class_name) {
            $inst = new $class_name;
            if ($inst instanceof AttrEditor) $inst->needs();
        }
        ?>

        <div id="multiedit-attrs">
            <div class="field-element field-element--dropdown field-element--white">
                <div class="field-input">
                    <select name="m_name" id="custom-attribute" class="dropdown">
                        <option value="">Select a custom attribute</option>
                        <?php
                        foreach ($attrs as $key => $val) {
                            $val[0] = Enc::html($val[0]);
                            echo "<option value=\"{$key}\">{$val[0]}</option>";
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="value">
                <input type="hidden" name="m_value" value="">
            </div>

        </div>

        <script>
        function attribute_editor($div, data, idx) {
            $div.find('select#custom-attribute').change(function() {
                if ($(this).val() === '') {
                    $div.find('.value').html('<input type="hidden" name="multiedit_attrs[' + idx + '][value]" value="">');
                    return;
                }
                var val = $div.find('.value [name^="multiedit_attrs"]').val();
                $.post(SITE + 'admin_ajax/attr_editor', {val:val, attr_name:$(this).val()}, function(data) {
                    var $outer = $div.find('.value');

                    $outer.html(data.html);
                    $outer.find('[name=value]').attr('name', 'multiedit_attrs[' + idx + '][value]');
                    if (data.js !== '') {
                        (function($outer,script){ eval(script); })($outer, data.js);
                    }
                }, 'json');
            });
            if (typeof(data) !== 'undefined') {
                $div.find('.dropdown').change();
            }
        }
        </script>

        <?php
        MultiEdit::setPostAddJavaScriptFunc('attribute_editor');
        MultiEdit::itemName('Attribute');
        MultiEdit::display('attrs', $current_attrs);
    }


    /**
     * Return HTML for the top nav tabs
     *
     * @param string $selected_controller
     * @return string HTML
     */
    public static function topNav($selected_controller)
    {
        if (!AdminAuth::isLoggedIn()) return;

        echo '<ul class="-clearfix">';

        if (AdminPerms::controllerAccess('page', 'contents')) {
            $dashboard_url = Enc::html(Kohana::config('sprout.admin_intro'));
            if ($selected_controller == '_dashboard') {
                echo '<li class="home depth-1 on"><a href="', $dashboard_url, '">Home</a></li>';
            } else {
                echo '<li class="home depth-1"><a href="', $dashboard_url, '">Home</a></li>';
            }

            if ($selected_controller == 'page') {
                echo '<li class="depth-1 on"><a href="admin/intro/page">Pages</a></li>';
            } else {
                echo '<li class="depth-1"><a href="admin/intro/page">Pages</a></li>';
            }
        }

        if (AdminPerms::controllerAccess('file', 'contents')) {
            if ($selected_controller == 'file') {
                echo '<li class="depth-1 on"><a href="admin/intro/file">Media</a></li>';
            } else {
                echo '<li class="depth-1"><a href="admin/intro/file">Media</a></li>';
            }
        }

        if (Register::hasFeature('users') and AdminPerms::controllerAccess('user', 'contents')) {
            if ($selected_controller == 'user') {
                echo '<li class="depth-1 on"><a href="admin/intro/user">Users</a></li>';
            } else {
                echo '<li class="depth-1"><a href="admin/intro/user">Users</a></li>';
            }
        }

        $tiles = Register::getAdminTiles();
        $tiles = AdminPerms::filterAdminTiles($tiles);

        if (count($tiles)) {
            $on = false;
            foreach ($tiles as $tile) {
                foreach ($tile['controllers'] as $ctlr => $name) {
                    if ($ctlr == $selected_controller) {
                        $on = true;
                        break;
                    }
                }
            }

            if ($on) {
                echo '<li class="depth-1 has-sub-nav on">';
            } else {
                echo '<li class="depth-1 has-sub-nav">';
            }
            echo '<input type="checkbox" id="open-sub-nav-1" class="giant-popup-checkbox -vis-hidden">';
            echo '<label for="open-sub-nav-1" class="giant-popup-link">Modules</label>';
            echo '<div class="giant-popup-outer">';
            echo '<div class="giant-popup-wrapper">';
            echo '<div class="giant-popup-inner">';
            echo '<label for="open-sub-nav-1" class="giant-popup-close-button icon-before icon-close">Close</label>';
            echo '<h2 class="giant-popup-title">Modules</h2>';
            echo '<ul class="sub-nav giant-popup-content columns -clearfix">';

            $index = 0;
            ksort($tiles, SORT_NATURAL);
            foreach ($tiles as $tile) {
                if ((++$index) % 4 == 0) {
                    echo '<li class="giant-popup-item column column-3 column-last">';
                } else {
                    echo '<li class="giant-popup-item column column-3">';
                }
                echo '<div class="giant-popup-item-inner">';
                echo '<div class="giant-popup-item-title-wrapper">';
                echo '<div class="giant-popup-item-title-icon icon-before icon-', Enc::html($tile['icon']), '"></div>';
                echo '<h3 class="giant-popup-item-title">', Enc::html($tile['name']), '</h3>';
                echo '</div>';
                echo '<div class="giant-popup-item-content -clearfix">';
                echo '<div class="giant-popup-item-links">';
                echo '<ul class="list-style-1">';
                foreach ($tile['controllers'] as $ctlr => $name) {
                    echo '<li><a href="admin/intro/', Enc::html($ctlr), '">', Enc::html($name), '</a></li>';
                }
                echo '</ul>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</li>';
            }

            echo '</ul>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</li>';
        }

        echo '</ul>';
    }


    /**
     * When in the admin, return the slug of the controller being used, e.g. 'page' or 'blog_post'
     *
     * @return string
     */
    public static function getControllerSlug()
    {
        if (isset(Router::$arguments[0])) {
            return Router::$arguments[0];
        } else {
            return null;
        }
    }


    /**
    * When in the admin, return the record id being added or edited.
    * If it's not an add or an edit, returns null.
    * If used outside of the admin, behaviour is undefined
    **/
    public static function getRecordId()
    {
        if (Router::$method === 'edit' or Router::$method === 'delete') {
            return Router::$arguments[1];
        }
        return null;
    }


    /**
     * Has a given JavaScript tour been completed?
     *
     * @param string $tour_name Internal name for the tour, e.g. "page_edit"
     * @return bool True if it's been completed, false if it hasn't
     */
    public static function isTourCompleted($tour_name)
    {
        $op_id = AdminAuth::getLocalId();
        if ($op_id === 0) return true;

        $q = "SELECT completed_tours FROM ~operators WHERE id = ?";
        $op = Pdb::query($q, [$op_id], 'row');
        $completed_tours = explode(",", $op['completed_tours']);

        return in_array($tour_name, $completed_tours);
    }


    /**
     * Set a given JavaScript tour as being "completed", preventing it from being shown again.
     *
     * @param string $tour_name Internal name for the tour, e.g. "page_edit"
     */
    public static function setTourCompleted($tour_name)
    {
        $op_id = AdminAuth::getLocalId();
        if ($op_id === 0) return;

        $q = "UPDATE ~operators SET completed_tours = TRIM(',' FROM CONCAT(completed_tours, ',', ?)) WHERE id = ?";
        Pdb::query($q, [$tour_name, $op_id], 'null');
    }


    /**
    * Is admin locks enabled?
    *
    * @return boolean True if they are, false if they aren't
    **/
    public static function locksEnabled()
    {
        $conf = Kohana::config('sprout.admin_locks');
        if ($conf === null) return true;
        return (bool) $conf;
    }


    /**
     * Gets the lock details for a given record
     * @param string $ctlr Controller name
     * @param int $record_id DB record ID
     * @return array If locked; has keys 'id', 'operator_name', 'lock_key', 'date_modified'
     * @return null If not locked
     **/
    public static function getLock($ctlr, $record_id)
    {
        $record_id = (int) $record_id;

        $q = "SELECT id, operator_name, lock_key, date_modified
            FROM ~admin_locks
            WHERE ctlr = ? AND record_id = ?
            LIMIT 1";
        try {
            $row = Pdb::q($q, [$ctlr, $record_id], 'row');
        } catch (QueryException $ex) {
            return null;
        }

        // If it's too old (10 mins), force it to unlock
        if (strtotime($row['date_modified']) + Constants::LOCK_AGE < time()) {
            Admin::forceUnlock($row['id']);
            return null;
        }

        return $row;
    }


    /**
     * Locks the given record for the current user
     * @param string $ctlr The controller responsible for the record
     * @param int $record_id The record ID
     * @throws Exception If the lock fails to acquire
     * @return int Lock id
     */
    public static function lock($ctlr, $record_id)
    {
        $op = AdminAuth::getDetails();

        if (! $_SESSION['admin']['lock_key']) {
            $_SESSION['admin']['lock_key'] = Admin::createLockKey();
        }

        $update_data = [];
        $update_data['ctlr'] = $ctlr;
        $update_data['record_id'] = (int) $record_id;
        $update_data['operator_name'] = $op['name'];
        $update_data['ip_address'] = bin2hex(inet_pton(trim(Request::userIp())));
        $update_data['user_agent'] = (string) $_SERVER['HTTP_USER_AGENT'];
        $update_data['lock_key'] = $_SESSION['admin']['lock_key'];
        $update_data['date_added'] = Pdb::now();
        $update_data['date_modified'] = Pdb::now();

        try {
            $lock_id = Pdb::insert('admin_locks', $update_data);
        } catch (Exception $ex) {
            throw new Exception('Failed to acquire edit lock.');
        }

        return $lock_id;
    }


    /**
     * Updates the timestamp for a given lock record to prevent it from timing out
     * @param int $lock_id
     * @return void
     */
    public static function pingLock($lock_id)
    {
        $lock_id = (int) $lock_id;

        $update_data = ['date_modified' => Pdb::now()];
        Pdb::update('admin_locks', $update_data, ['id' => $lock_id]);
    }


    /**
    * Nuke a lock
    **/
    public static function forceUnlock($lock_id)
    {
        Pdb::delete('admin_locks', ['id' => (int) $lock_id]);
    }


    /**
    * Removes locks for the current user
    *
    * @param string $ctlr Will refine lock removal by controller
    * @param int $record_id Will refine lock removal by record id
    **/
    public static function unlock($ctlr = null, $record_id = null)
    {
        if (empty($_SESSION['admin']['lock_key'])) {
            return;
        }

        $where = ['lock_key' => $_SESSION['admin']['lock_key']];
        if ($ctlr) {
            $where['ctlr'] = $ctlr;
            if ($record_id) $where['record_id'] = $record_id;
        }

        Pdb::delete('admin_locks', $where);
    }


    /**
    * Return a unique key for identifying a session.
    * This will be constant even if the session id changes, although it's nuked if you log out.
    **/
    public static function createLockKey()
    {
        return sha1(Request::userIp() . $_SERVER['HTTP_USER_AGENT'] . time());
    }


    /**
    * Remove all locks which are older than the allowed lock time.
    **/
    public static function clearOldLocks()
    {
        $q = "SELECT id, date_modified FROM ~admin_locks";
        $res = Pdb::query($q, [], 'pdo');

        foreach ($res as $row) {
            if (strtotime($row['date_modified']) + Constants::LOCK_AGE < time()) {
                Admin::forceUnlock($row['id']);
            }
        }

        $res->closeCursor();
    }


    /**
     * Gets an instance of a managed admin controller
     *
     * @param string $class_name A class name, or shorthand identifier
     *        e.g. 'Sprout\Controllers\Admin\AwesomeAdminController' or 'awesome'
     * @return ManagedAdminController
     * @throws Exception If the class is unknown
     */
    public static function getController($class_name)
    {
        if (strpos($class_name, '\\') !== false) {
            $full_name = $class_name;
        } else {
            // Use registered shorthand names for classes in modules
            try {
                $full_name = Register::getAdminController($class_name);

            // Auto-determine names of Sprout internal controllers
            } catch (Exception $ex) {
                $full_name = ucfirst(Inflector::camelize($class_name));
                if (substr($full_name, -15) != 'AdminController') {
                    $full_name .= 'AdminController';
                }
                $full_name = 'Sprout\\Controllers\\Admin\\' . $full_name;
            }
        }

        $inst = Sprout::instance(
            $full_name,
            'Sprout\\Controllers\\Admin\\ManagedAdminController'
        );

        return $inst;
    }


    /**
     * For a given URL, ensure it's absolute.
     * If it's not absolute, the current admin abs-root is prepended
     *
     * @param string $url Either relative or absolute
     * @return string Absolute URL
     */
    public static function ensureUrlAbsolute($url)
    {
        if (preg_match('!^https?://!', $url)) {
            return $url;
        } else {
            return Subsites::getAbsRoot($_SESSION['admin']['active_subsite']) . ltrim($url, '/');
        }
    }

}
