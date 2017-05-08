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

use Sprout\Helpers\File;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Form;
use Sprout\Helpers\View;


/**
* Basically just a test widget
**/
class AudioWidget extends Widget
{
    protected $friendly_name = "Audio player";
    protected $friendly_desc = "Places an audio player on the page";


    /**
    * Return the front-end view of this widget
    *
    * @param int $orientation The orientation of the widget.
    **/
    public function render($orientation)
    {
        $this->settings['filename'] = trim($this->settings['filename']);
        if ($this->settings['filename'] == '') return;

        $view = new View('sprout/audio_player');
        $view->filename = File::url($this->settings['filename']);

        return $view->render();
    }


    /**
    * Return the settings form for this widget
    **/
    public function getSettingsForm()
    {
        Form::nextFieldDetails('Audio file', true);
        return Form::fileselector('filename', [], ['filter' => FileConstants::TYPE_SOUND, 'required' => true]);
    }

}
