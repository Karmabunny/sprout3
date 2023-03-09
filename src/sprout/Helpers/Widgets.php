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
use Sprout\Exceptions\FileMissingException;
use Sprout\Helpers\BaseView;
use Sprout\Helpers\Enc;
use Sprout\Helpers\PhpView;
use Sprout\Widgets\PageColumnsWidget;


/**
* Provided functions for the display of widgets
**/
class Widgets
{
    private static $widget_areas = array();

    /**
    * Add a widget to the list of widgets for a specific area
    *
    * @param int $area_id The widget area to add the widget to
    * @param string $name The name of the widget to add
    * @param array $settings The widget settings to use
    * @param string $heading HTML H2 rendered front-end within widget
    * @param string $template Optional wrapping template name
    * @param string $columns
    **/
    public static function add($area_id, $name, $settings, $heading = '', $template = '', $columns = null)
    {
        if (! preg_match('/^[0-9]+$/', $area_id)) {
            $area = WidgetArea::findAreaByName($area_id);
            if (! $area) return;
            $area_id = $area->getIndex();
        }

        self::$widget_areas[$area_id][] = array($name, $settings, $heading, $template, $columns);
    }

    /**
    * Remove a widget to the list of widgets for a specific area
    *
    * @param int $area_id The widget area to add the widget to
    * @param string $name The name of the widget to remove
    **/
    public static function remove($area_id, $name)
    {
        if (! preg_match('/^[0-9]+$/', $area_id)) {
            $area = WidgetArea::findAreaByName($area_id);
            if (! $area) return;
            $area_id = $area->getIndex();
        }

        foreach (self::$widget_areas[$area_id] as $idx => $def) {
            if ($def[0] === $name) unset (self::$widget_areas[$area_id][$idx]);
        }
    }

    /**
    * Create an instance of a specific widget in memory
    *
    * @param string $name The name of the widget to instantiate.
    * @return Widget The instance
    **/
    public static function instantiate($name)
    {
        $class = $name;
        if (substr($class, -6) != 'Widget') $class .= 'Widget';
        if (strpos($class, '\\') === false) {
            $class = 'Sprout\\Widgets\\' . $class;
        }
        if (!class_exists($class)) {
            throw new Exception("Unknown widget {$name}");
        }

        return new $class();
    }

    /**
     * Instantiate, import settings, and render
     * @param int|string $orientation ORIENTATION constant or name {@see WidgetArea}
     * @param string $name Class name of the widget
     * @param array $settings Widget settings (keys and values vary with widget subclass)
     * @param string $pre_html HTML to go before the rendered widget
     * @param string $post_html HTML to go after the rendered widget
     * @param string $heading String Optional HTML H2 rendered on front-end of given widget
     * @param string $template String Optional wrapping template name
     * @return string Front-end HTML of widget
     */
    public static function render($orientation, $name, array $settings, $pre_html = null, $post_html = null, $heading = null, $template = null)
    {
        $inst = self::instantiate($name);
        if ($inst == null) return null;

        $orientation = WidgetArea::parseOrientation($orientation);

        $inst->importSettings($settings);
        $inst->setTitle($heading);

        // Search for override templates in the skin.
        $override = Kohana::config('sprout.widget_override_templates');

        if (!empty($override)) {
            $override .= '/' . str_replace('Sprout\\Widgets\\', '', get_class($inst));

            try {
                $view = BaseView::create($override, [
                    'widget' => $inst,
                    'orientation' => $orientation,
                ]);
                $html = $view->render();

            } catch (FileMissingException $e) {}
        }

        // Use the builtin renderer.
        if (!isset($html)) {
            $html = $inst->render($orientation);
        }

        if ($html == null) return null;

        if ($orientation != WidgetArea::ORIENTATION_EMAIL and AdminAuth::isLoggedIn()) {
            $infobox = true;
        }

        if (! $pre_html) {
            $class = 'widget widget-' . Enc::id(str_replace('\\', '-', (isset($inst->classname) ? $inst->classname : $name)));
            if (!empty($infobox)) $class .= ' widget-hasinfobox';
            $class .= ' orientation-' . WidgetArea::$orientation_classes[$orientation];

            $pre_html = "<div class=\"{$class}\">";
        }

        if (! $post_html) $post_html = "</div>";

        $ret = '';
        $ret .= $pre_html;

        $title = $inst->getTitle();
        if (!empty($title)) {
            $heading_html = '<h2 class="widget-title">TITLE</h2>';

            if (!empty(Kohana::config('sprout.widget_title'))) {
                $heading_html = Kohana::config('sprout.widget_title');
            }

            $ret .= str_replace('TITLE', Enc::html($title), $heading_html);
        }

        $ret .= $html;

        if (!empty($infobox)) {
            $ret .= self::infobox($inst);
        }

        $ret .= $post_html;

        // Wrap widget HTML within template snippet
        if (!empty($template)) {
            $view = new PhpView($template);
            $ret = str_replace('{{widget}}', $ret, $view->render());
        }

        return $ret;
    }

    /**
    * Render extra widget info for admins
    **/
    private static function infobox($inst)
    {
        $ret = '<div class="widget-infobox">';
        $ret .= '<i>' . Enc::html($inst->getFriendlyName()) . ' Addon</i> &nbsp; ';

        $info_lab = $inst->getInfoLabels();
        if ($info_lab) {
            foreach ($info_lab as $name => $val) {
                $ret .= '<b>' . Enc::html($name) . ':</b> ' . Enc::html($val) . ' &nbsp; ';
            }
        }

        $edit_url = $inst->getEditUrl();
        if ($edit_url) {
            $ret .= '<a href="SITE/' . $edit_url . '" target="_blank">Edit content</a>';
        }

        $ret .= '</div>';

        return $ret;
    }


    /**
     * Draw the widgets for a specific area
     *
     * The available widget areas are defined in the {@link /config/sprout.php} file.
     * Typically there are two areas defined, 'sidebar' and 'embedded'.
     *
     * @param string $area_name The name of the widget area to draw.
     * @param bool $enable_columns Enable grouping widgets into visual columns. Default of false
     * @return string HTML representing the rendered widgets
     */
    public static function renderArea($area_name, $enable_columns = false)
    {
        $area = WidgetArea::findAreaByName($area_name);
        if ($area == null) {
            return;
        }

        $area_id = $area->getIndex();
        if (empty(self::$widget_areas[$area_id])) {
            return;
        }

        // Group widgets into visual columns, if enabled
        $col_type = null;
        $col_first = [];
        $col_second = [];
        $col_third = [];

        $out = '';
        foreach (self::$widget_areas[$area_id] as $widget_details) {
            list($name, $settings, $heading, $template, $columns) = $widget_details;
            $widget = self::render($area->getOrientation(), $name, $settings, null, null, $heading, $template);

            // Render widget not within columns - area not configured
            if (!$enable_columns) {
                $out .= $widget;
                continue;
            }

            // Fallback column styles
            $settings['style_col1'] = !empty($settings['style_col1']) ? $settings['style_col1'] : '';
            $settings['style_col2'] = !empty($settings['style_col2']) ? $settings['style_col2'] : '';
            $settings['style_col3'] = !empty($settings['style_col3']) ? $settings['style_col3'] : '';

            // Column widget; Setup new columns
            if ($name == 'PageColumns')
            {
                // Close previous row/container
                if (!empty($col_type))
                {
                    // Close column(s)
                    switch ($col_type)
                    {
                        case PageColumnsWidget::$cols[0]:
                            $col_first[] = '</div></div>';
                            break;

                        case PageColumnsWidget::$cols[1]:
                            $col_first[] = '</div></div>';
                            $col_second[] = '</div></div>';
                            break;

                        case PageColumnsWidget::$cols[2]:
                            $col_first[] = '</div></div>';
                            $col_second[] = '</div></div>';
                            break;

                        case PageColumnsWidget::$cols[3]:
                            $col_first[] = '</div></div>';
                            $col_second[] = '</div></div>';
                            break;

                        case PageColumnsWidget::$cols[4]:
                            $col_first[] = '</div></div>';
                            $col_second[] = '</div></div>';
                            $col_third[] = '</div></div>';
                            break;
                    }

                    // Render columns(s)
                    foreach ($col_first as $col) {
                        $out .= $col;
                    }

                    foreach ($col_second as $col) {
                        $out .= $col;
                    }

                    foreach ($col_third as $col) {
                        $out .= $col;
                    }

                    // Close row/container
                    $out .= '</div>';

                    // Reset columns
                    $col_first = [];
                    $col_second = [];
                    $col_third = [];
                }

                // Setup column(s)
                switch ($settings['column'])
                {
                    case PageColumnsWidget::$cols[0]:
                        $col_type = $settings['column'];
                        $col_first[] = sprintf('<div class="col-xs-12"><div class="%s">', Enc::html($settings['style_col1']));
                        break;

                    case PageColumnsWidget::$cols[1]:
                        $col_type = $settings['column'];
                        $col_first[] = sprintf('<div class="col-xs-12 col-md-6"><div class="%s">', Enc::html($settings['style_col1']));
                        $col_second[] = sprintf('<div class="col-xs-12 col-md-6"><div class="%s">', Enc::html($settings['style_col2']));
                        break;

                    case PageColumnsWidget::$cols[2]:
                        $col_type = $settings['column'];
                        $col_first[] = sprintf('<div class="col-xs-12 col-md-7"><div class="%s">', Enc::html($settings['style_col1']));
                        $col_second[] = sprintf('<div class="col-xs-12 col-md-5"><div class="%s">', Enc::html($settings['style_col2']));
                        break;

                    case PageColumnsWidget::$cols[3]:
                        $col_type = $settings['column'];
                        $col_first[] = sprintf('<div class="col-xs-12 col-md-5"><div class="%s">', Enc::html($settings['style_col1']));
                        $col_second[] = sprintf('<div class="col-xs-12 col-md-7"><div class="%s">', Enc::html($settings['style_col2']));
                        break;

                    case PageColumnsWidget::$cols[4]:
                        $col_type = $settings['column'];
                        $col_first[] = sprintf('<div class="col-xs-12 col-md-4"><div class="%s">', Enc::html($settings['style_col1']));
                        $col_second[] = sprintf('<div class="col-xs-12 col-md-4"><div class="%s">', Enc::html($settings['style_col2']));
                        $col_third[] = sprintf('<div class="col-xs-12 col-md-4"><div class="%s">', Enc::html($settings['style_col3']));
                        break;

                    default:
                        $col_type = null;
                        break;
                }

                // Start row/container
                if (!empty($col_type)) $out .= '<div class="row row-gap--medium row--vertical-gutters">';
            }
            // Render widget within its selected column
            else if (!empty($col_type))
            {
                switch ($columns)
                {
                    case '2nd':
                        $col_second[] = $widget;
                        break;

                    case '3rd':
                        $col_third[] = $widget;
                        break;

                    case '1st':
                    default:
                        $col_first[] = $widget;
                }
            }
            // Render widget not within any columns
            else
            {
                $out .= $widget;
            }
        }

        // Close previous row/container
        if (!empty($col_type))
        {
            // Close column(s)
            switch ($col_type)
            {
                case PageColumnsWidget::$cols[0]:
                    $col_first[] = '</div></div>';
                    break;

                case PageColumnsWidget::$cols[1]:
                    $col_first[] = '</div></div>';
                    $col_second[] = '</div></div>';
                    break;

                case PageColumnsWidget::$cols[2]:
                    $col_first[] = '</div></div>';
                    $col_second[] = '</div></div>';
                    break;

                case PageColumnsWidget::$cols[3]:
                    $col_first[] = '</div></div>';
                    $col_second[] = '</div></div>';
                    break;

                case PageColumnsWidget::$cols[4]:
                    $col_first[] = '</div></div>';
                    $col_second[] = '</div></div>';
                    $col_third[] = '</div></div>';
                    break;
            }

            // Render columns(s)
            foreach ($col_first as $col) {
                $out .= $col;
            }

            foreach ($col_second as $col) {
                $out .= $col;
            }

            foreach ($col_third as $col) {
                $out .= $col;
            }

            // Close row/container
            $out .= '</div>';
        }

        return $out;
    }


    /**
     * Render a widget area, and output directly
     *
     * @deprecated Use {@see Widgets::renderArea} instead
     */
    public static function area($area_name)
    {
        echo self::renderArea($area_name);
    }


    /**
    * Does the specified widget area have widgets?
    *
    * @param string $area_name The name of the widget area to query.
    *
    * @tag api
    * @tag designer-api
    **/
    public static function hasWidgets($area_name)
    {
        $area = WidgetArea::findAreaByName($area_name);
        if ($area == null) return;
        $area_id = $area->getIndex();

        if (!empty(self::$widget_areas[$area_id])) return true;

        return false;
    }


    /**
     * Check whether the widget is allowed to be displayed
     *
     * @param array $env Calling environment, e.g. page id
     * @param array $conditions Conditions to check; each condition would be an array witj 'field', 'op', 'val' keys
     * @return bool True if conditions match and display is allowed, false otherwise
     */
    public static function checkDisplayConditions(array $env, array $conditions)
    {
        foreach ($conditions as $cond) {
            try {
                $inst = Sprout::instance($cond['field'], ['Sprout\\Helpers\\DisplayConditions\\DisplayCondition']);
            } catch (Exception $ex) {
                continue;
            }

            $result = $inst->match($env, $cond['op'], $cond['val']);
            if (!$result) {
                return false;
            }
        }

        return true;
    }

}
