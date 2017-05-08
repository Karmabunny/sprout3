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

use Kohana;

use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;

/**
* Displays a google map
**/
class MapWidget extends Widget
{
    protected $friendly_name = "Map";
    protected $friendly_desc = 'A static Google map of a specific address';
    protected $default_settings = [
        'width' => 800,
        'height' => 300,
        'type' => 'roadmap',
    ];

    /** N.B. these are the exact types that Google allows */
    protected $map_types = [
        'roadmap' => 'Road',
        'satellite' => 'Satellite',
        'hybrid' => 'Hybrid',
    ];


    /**
     * Ensure settings are sane
     */
    public function cleanupSettings()
    {
        if (empty($this->settings['width'])) $this->settings['width'] = 500;
        if (empty($this->settings['height'])) $this->settings['height'] = 400;
        if (empty($this->settings['zoom'])) $this->settings['zoom'] = 15;
        if (empty($this->settings['align'])) $this->settings['align'] = '';

        $this->settings['width'] = (int) $this->settings['width'];
        $this->settings['height'] = (int) $this->settings['height'];
        $this->settings['zoom'] = (int) $this->settings['zoom'];
    }


    /**
    * Return the front-end view of this widget
    *
    * @param int $orientation The orientation of the widget.
    **/
    public function render($orientation)
    {
        if (!empty($this->settings['lat']) and !empty($this->settings['lng'])) {
            $q = Enc::url($this->settings['lat'] . ',' . $this->settings['lng']);
        } else if (!empty($this->settings['address'])) {
            $q = Enc::url($this->settings['address']);
        } else {
            return null;
        }

        $lnkurl = 'https://maps.google.com.au/maps?q=' . $q;

        $imgurl = 'https://maps.googleapis.com/maps/api/staticmap'
            . '?size=' . $this->settings['width'] . 'x' . $this->settings['height']
            . '&zoom=' . $this->settings['zoom']
            . '&key=' . Enc::url(Kohana::config('sprout.google_maps_key'))
            . '&markers=' . $q . '&sensor=false';
        if ($this->settings['width'] > 500) $imgurl .= '&scale=2';
        if (isset($this->map_types[$this->settings['type']])) {
            $imgurl .= '&maptype=' . $this->settings['type'];
        } else {
            $imgurl .= '&maptype=roadmap';
        }

        $out = '<a href="' . Enc::html($lnkurl) . '"';
        if (!empty($this->settings['new_window'])) $out .= ' target="_blank"';
        $out .= '><img';
        $out .= ' width="' . $this->settings['width'] . '"';
        $out .= ' height="' . $this->settings['height'] . '"';
        $out .= ' src="' . Enc::html($imgurl) . '" ';
        $out .= ' class="' . Enc::html($this->settings['align']) . '" alt="Visit Google Maps">';
        $out .= '</a>';

        return $out;
    }


    /**
    * Return the settings form for this widget
    **/
    public function getSettingsForm()
    {
        if (empty($this->settings['type'])) $this->settings['type'] = 'Road';

        $out = '';

        Form::nextFieldDetails('Map type', false);
        $out .= Form::dropdown('type', [], $this->map_types);

        Form::nextFieldDetails('Location', false);
        $out .= Form::googleMap('lat,lng,zoom');

        Form::nextFieldDetails('Width', false);
        $out .= Form::text('width');

        Form::nextFieldDetails('Height', false);
        $out .= Form::text('height');

        Form::nextFieldDetails('Align', false);
        $out .= Form::dropdown('align', [], ['left' => 'Left', 'right' => 'Right']);

        Form::nextFieldDetails('Options', false);
        $out .= Form::checkboxList(['new_window' => 'Open in a new window when clicked']);

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

