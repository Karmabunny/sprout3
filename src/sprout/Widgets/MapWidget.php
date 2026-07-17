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
use Sprout\Helpers\GeoSeach;
use Sprout\Helpers\Needs;
use Sprout\Helpers\PhpView;

/**
* Displays a google map
**/
class MapWidget extends Widget
{
    protected string $friendly_name = "Map";
    protected string $friendly_desc = 'Street map of a specific address';
    protected array $default_settings = [
        'width' => 800,
        'height' => 300,
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
            $latlng = ['lat' => $this->settings['lat'], 'lng' => $this->settings['lng']];
        } else if (!empty($this->settings['address'])) {
            $latlng = GeoSeach::getByQuery($this->settings['address']);
        } else {
            return '';
        }

        if (empty($latlng)) return '';

        $this->cleanupSettings();

        Needs::addCssInclude('https://unpkg.com/leaflet@1.5.1/dist/leaflet.css', ['integrity' => 'sha512-xwE/Az9zrjBIphAcBb3F6JVqxf46+CDLwfLMHloNu6KEQCAWi6HcDUbeOfBIptF7tcCzusKFjFw2yuvEpDL9wQ==', 'crossorigin' => ''], 'leaflet_css');
        Needs::addJavascriptInclude('https://unpkg.com/leaflet@1.5.1/dist/leaflet.js', ['integrity' => 'sha512-GffPMF3RvMeYyc1LWMHtK8EbPv0iNZ8/oTtHPx9/cc2ILxQ+u905qIwdpULaqDkyBKgOaB57QTMg7ztg8Jm2Og==', 'crossorigin' => ''], 'leaflet_js');
        Needs::fileGroup('sprout/map_widget');

        $view = new PhpView('sprout/map_widget');
        $view->width = $this->settings['width'];
        $view->height = $this->settings['height'];
        $view->unique = md5((string) microtime(true));
        $view->zoom = $this->settings['zoom'];
        $view->latlng = $latlng;
        $view->align = $this->settings['align'];

        return $view->render();
    }


    /**
    * Return the settings form for this widget
    **/
    public function getSettingsForm()
    {
        if (empty($this->settings['type'])) $this->settings['type'] = 'Road';

        $out = '';

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

