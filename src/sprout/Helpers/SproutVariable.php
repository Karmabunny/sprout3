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

/**
 *
 */
class SproutVariable
{

    public $url;
    public $request;
    public $skin;
    public $enc;


    public function __construct()
    {
        $this->url = new Url();
        $this->request = new Request();
        $this->skin = new Skin();
        $this->enc = new Enc();
    }


    public function getParams()
    {
        return $_GET;
    }


    public function getParam($name)
    {
        return $_GET[$name] ?? '';
    }


    public function include($name, $data = [])
    {
        return View::include($name, $data);
    }


    public function config($name, $slash = false, $required = true)
    {
        return Kohana::config($name, $slash, $required);
    }
}
