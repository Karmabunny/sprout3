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

use Kohana;
use Twig\Markup;

/**
 *
 */
class SproutVariable
{

    public $url;
    public $request;
    public $skin;
    public $enc;
    public $session;
    public $navigation;
    public $notification;
    public $widgets;
    public $social;
    public $replace;

    public function __construct()
    {
        $this->url = new Url();
        $this->request = new Request();
        $this->skin = new Skin();
        $this->enc = new Enc();
        $this->session = new Session();
        $this->navigation = new Navigation();
        $this->notification = new Notification();
        $this->widgets = new Widgets();
        $this->social = [
            'meta' => new SocialMeta(),
            'networking' => new SocialNetworking(),
        ];
        $this->replace = new ContentReplace();
    }


    public function getParams()
    {
        return $_GET;
    }


    public function getParam($name)
    {
        return $_GET[$name] ?? '';
    }


    public function getQueryString()
    {
        return Router::$query_string;
    }


    public function getPath()
    {
        return Router::$current_uri;
    }


    public function include($name, $data = [])
    {
        return new Markup(View::include($name, $data), 'UTF-8');
    }


    public function config($name, $slash = false, $required = true)
    {
        return Kohana::config($name, $slash, $required);
    }
}
