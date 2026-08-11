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

use Sprout\Helpers\EmbedVideo;
use Sprout\Helpers\Form;


/**
* Basically just a test widget
**/
class VideoWidget extends Widget
{
    protected string $friendly_name = "Video player";
    protected string $friendly_desc = 'A video player for videos hosted on YouTube or Vimeo';
    protected array $default_settings = [
        'width' => 600,
        'height' => 340,
    ];


    /**
    * Set values for any missing settings fields
    **/
    public function cleanupSettings()
    {
        if (!isset($this->settings['video_id'])) $this->settings['video_id'] = '';
        if (!isset($this->settings['title'])) $this->settings['title'] = '';
        if (!isset($this->settings['width'])) $this->settings['width'] = 600;
        if (!isset($this->settings['height'])) $this->settings['height'] = 340;
    }


    /**
    * Return the front-end view of this widget
    *
    * @param int $orientation The orientation of the widget.
    **/
    public function render($orientation)
    {
        $options = array(
            'title' => $this->settings['title'],
        );

        return EmbedVideo::renderEmbed(
            $this->settings['video_id'],
            $this->settings['width'],
            $this->settings['height'],
            $options
        );
    }


    /**
    * Return the settings form for this widget
    **/
    public function getSettingsForm()
    {
        $out = '';

        Form::nextFieldDetails('Video URL', true);
        $out .= Form::text('video_id');

        Form::nextFieldDetails('Video title', true);
        $out .= Form::text('title');

        Form::nextFieldDetails('Width', true);
        $out .= Form::text('width');

        Form::nextFieldDetails('Height', true);
        $out .= Form::text('height');

        return $out;
    }

}
