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
 * Base class for widgets included in a {@see RefineBar}
 */
abstract class RefineWidget {
    protected $name;
    protected $label;


    public function __construct($name, $label)
    {
        $this->name = $name;
        $this->label = $label;
    }

    public final function setName ($name) {
        $this->name = $name;
    }

    public final function getName () {
        return $this->name;
    }


    public final function setLabel ($label) {
        $this->label = $label;
    }

    public final function getLabel () {
        return $this->label;
    }



    /**
    * Returns the current value for this refine widget
    **/
    public final function getValue() {
        return @$_GET[$this->name];
    }

    /**
    * Draws the widget.
    * @return string The HTML for the widget.
    **/
    public abstract function render();

}


