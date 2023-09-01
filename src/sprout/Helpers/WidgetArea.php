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
use Kohana;

/**
* Stores information about an individual widget area, including the names of all widgets
* that are allowed to be in this area.
**/
class WidgetArea
{
    private $name;
    private $nice_name;
    private $index;
    private $widgets;
    private $orientation;
    private $embed;

    const ORIENTATION_TALL = 1;
    const ORIENTATION_WIDE = 2;
    const ORIENTATION_EMAIL = 3;

    /**
    * Class names which are output in the wrapper div
    **/
    public static $orientation_classes = array(
        self::ORIENTATION_TALL => 'tall',
        self::ORIENTATION_WIDE => 'wide',
        self::ORIENTATION_EMAIL => 'email',
    );


    /**
    * Constructor
    *
    * @param string $name The name of the widget area
    * @param int $index The numerical index of the widget area in the config file
    **/
    public function __construct($name, $index)
    {
        $this->name = $name;
        $this->nice_name = $name;
        $this->index = $index;
        $this->widgets = array();
        $this->embed = false;
    }

    /**
    * Returns the name of this widget area
    **/
    public function getName()
    {
        return $this->name;
    }

    /**
    * Returns the nice name of this widget area
    **/
    public function getNiceName()
    {
        return Enc::html($this->nice_name);
    }


    /**
    * Sets the nice name of this widget area
    *
    * @param string $name The nice name of the widget
    **/
    public function setNiceName($name)
    {
        $this->nice_name = $name;
    }

    /**
    * Sets the orientation of the widget area
    *
    * @param int $value The new orientation
    **/
    public function setOrientation($value)
    {
        $this->orientation = $value;
    }

    /**
    * If true, the widget area is an 'embedding' area.
    **/
    public function setEmbed($embed)
    {
        $this->embed = $embed;
    }

    /**
    * If the widget area is an embedding area
    **/
    public function isEmbed()
    {
        return $this->embed;
    }

    /**
    * Gets the orientation of the widget area
    **/
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
    * Returns the index of this widget area
    **/
    public function getIndex()
    {
        return $this->index;
    }

    /**
    * Adds a widget to the list of widgets allowed for this area
    *
    * @param string $widget_name The name of the widget to add
    **/
    public function addWidget($widget_name)
    {
        if (! in_array($widget_name, $this->widgets)) {
            $this->widgets[] = $widget_name;
        }
    }

    /**
    * Gets the list of allowed widgets
    **/
    public function getWidgets()
    {
        return $this->widgets;
    }


    /**
    * Adds a widget to the list of widgets allowed for this area
    *
    * @param string $widget_name The name of the widget to add
    **/
    public function addDefault($widget_name, $settings)
    {
        Widgets::addOnce($this->index, $widget_name, $settings);
    }


    /**
    * Returns the widget area which has the specified name.
    * Uses the widget areas defined in the sprout config.
    *
    * @param string $name The name of the widget area to use.
    * @return static|null
    **/
    public static function findAreaByName($name)
    {
        $areas = Kohana::config('sprout.widget_areas');

        foreach ($areas as $index => $area) {
            if ($area->name == $name) return $area;
        }

        return null;
    }


    /**
     * Normalize a constant or orientation name (tall|wide|email) into a constant.
     *
     * @param int|string $orientation name or constant
     * @return int an ORIENTATION constant
     * @throws InvalidArgumentException if the orientation is not valid
     */
    public static function parseOrientation($orientation)
    {
        if (is_numeric($orientation)) {
            if (isset(self::$orientation_classes[$orientation])) {
                return $orientation;
            }
        } else {
            $orientation = array_search($orientation, self::$orientation_classes);
            if ($orientation !== false) {
                return $orientation;
            }
        }

        throw new InvalidArgumentException("Invalid orientation: {$orientation}");
    }
}


