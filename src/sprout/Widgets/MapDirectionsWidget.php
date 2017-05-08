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

use Sprout\Helpers\Form;
use Sprout\Helpers\View;


/**
* Displays a google map
**/
class MapDirectionsWidget extends Widget
{
    protected $friendly_name = "Map w/ Directions";
    protected $friendly_desc = 'A dynamic Google map, with directions';


    /**
    * Return the front-end view of this widget
    *
    * @param int $orientation The orientation of the widget.
    **/
    public function render($orientation)
    {
        $view = new View('sprout/map_directions');
        if (!empty($this->settings['address'])) {
            $view->address = $this->settings['address'];
        } else if (!empty($this->settings['latitude']) and !empty($this->settings['longitude'])) {
            $view->address = $this->settings['latitude'] . ' ' . $this->settings['longitude'];
        } else {
            return null;
        }

        if (!empty($this->settings['zoom'])) {
            $view->zoom = $this->settings['zoom'];
        } else {
            $view->zoom = 5;
        }

        return $view->render();
    }


    /**
    * Return the settings form for this widget
    **/
    public function getSettingsForm()
    {
        $out = '';

        Form::nextFieldDetails('Address', false);
        $out .= Form::text('address');

        Form::nextFieldDetails('Or choose an exact point on the map', false);
        $out .= Form::googleMap('latitude,longitude,zoom');

        return $out;
    }


    /**
    * Returns a label which describes the contents of this widget
    * See {@link Widget::get_info_label} for full documentation
    **/
    public function getInfoLabels()
    {
        return array(
            'Address' => @$this->settings['address'],
        );
    }

}

