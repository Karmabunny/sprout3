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

namespace Sprout\Widgets;



/**
* Base class for widgets
**/
abstract class Widget {

    /**
    * Settings - These get automatically set in the admin and loaded on the front-end
    **/
    protected $settings = [];


    /**
    * The friendly name, for display in the admin
    **/
    protected $friendly_name = 'Widget';


    /**
    * A description, for display in the admin
    **/
    protected $friendly_desc = '';


    /**
     * Array of default settings for new widgets
     */
    protected $default_settings = [];


    /**
     * Optional HTML H2 heading that's rendered on front-end view
     */
    protected $heading = '';


    /**
    * Imports the settings
    *
    * @param array $settings The widget settings
    **/
    public function importSettings(array $settings)
    {
        foreach ($this->default_settings as $key => $val) {
            if (!isset($settings[$key]) or $settings[$key] === '') {
                $settings[$key] = $val;
            }
        }
        $this->settings = $settings;
        $this->cleanupSettings();
    }


    /**
     * Return the currently loaded settings for the widget
     *
     * @return array The widget settings
     */
    public function getSettings()
    {
        return $this->settings;
    }


    /**
    * Gets the english name of this widget
    **/
    public function getFriendlyName()
    {
        return $this->friendly_name;
    }

    /**
    * Gets the english name of this widget
    **/
    public function getFriendlyDesc()
    {
        return $this->friendly_desc;
    }


    /**
     * Set widget title
     *
     * @param string $str Widget title (heading)
     * @return void
     */
    public function setTitle($str)
    {
        $this->heading = $str;
    }


    /**
    * Returns the title to use for the widget - should not contain HTML
    **/
    public function getTitle()
    {
        if (!empty($this->heading)) {
            return $this->heading;
        }
        return null;
    }

    /**
    * Returns the default settings for the widget
    **/
    public final function getDefaultSettings()
    {
        return $this->default_settings;
    }


    /**
     * Run after import of settings, allows extending widgets to clean up settings which may
     * have bad values for some reason
     *
     * @return void
     */
    public function cleanupSettings()
    {
    }


    /**
    * This function should return the content of the widget that is used on the front-end of the website.
    * The content should be returned as well-formed HTML.
    * The content will be contained within a DIV element,
    * which has the class 'widget', and the class 'widget-[WIDGETNAME]' applied to it.
    *
    * Styles and Javascript can be loaded with the {@see needs} helper, usually the {@see Needs::module} function is most useful.
    *
    * @param int $orientation The orientation of the widget.
    *     Will be one of the constants defined in the {@see WidgetArea} class.
    *     ORIENTATION_TALL - used for sidebars, and other mainly tall areas
    *     ORIENTATION_WIDE - used for wide areas, like the main content area
    *
    * @return string The output HTML.
    *
    * @tag api
    * @tag widget-api
    **/
    abstract public function render ($orientation);


    /**
    * This function should return the content of the widget settings form.
    * The content should be returned as well-formed HTML.
    * The content will be contained within a DIV element, which is inside a FORM element.
    *
    * This function will be called from an AJAX request, so {@see Needs} cannot be used.
    * Inline CSS and Javascript should be loaded correctly by the jQuery library.
    *
    * Form fields *must* be generated using the {@see Form} helper, to ensure correct field
    * prefix as well as reloading of field values
    *
    * @return string The output HTML.
    **/
    public function getSettingsForm()
    {
        return '<p><em>This add-on does not have any settings.</em></p>';
    }


    /**
    * This function should return a URL which can be used to edit the *content* which is
    * displayed by this widget.
    *
    * Most widgets show content using a category selection box; the URL should in that
    * case direct the user to the contents list for that category.
    *
    * Return NULL if there isn't an appropriate URL to use.
    **/
    public function getEditUrl()
    {
        return null;
    }


    /**
    * This function should return a summary of what this widget's *content* is.
    * Don't specify the widget name; we already know that!
    *
    * Most widgets show content using a category selection box; in that case
    * the most useful thing is probably the category name, perhaps also the
    * order and limit.
    *
    * Return NULL if there isn't an appropriate label to use.
    **/
    public function getInfoLabels()
    {
        return null;
    }


    /**
    * Is there sufficent content to add this addon at the moment?
    * If this widget is not available, a string message should be returned
    * If this widget *is* available, NULL should be returned.
    **/
    public function getNotAvailableReason()
    {
        return null;
    }

}
