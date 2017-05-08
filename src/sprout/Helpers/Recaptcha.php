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

use Exception;

use Kohana;


/**
* Implementation of Google (No CAPTCHA) ReCAPTCHA
**/
class Recaptcha
{

    /**
    * Shows the captcha field
    **/
    public static function field()
    {
        $key = Kohana::config('sprout.recaptcha_public_key');
        if (!$key) throw new Exception('ReCAPTCHA key not found');

        Needs::addJavascriptInclude('https://www.google.com/recaptcha/api.js');
        echo '<div class="g-recaptcha" data-sitekey="' . Enc::html($key) . '"></div>';
    }

    /**
    * Checks the captcha field against the submitted text
    * @return boolean True on success
    **/
    public static function check()
    {
        if (empty($_POST['g-recaptcha-response'])) {
            // Obviously not a valid request if there's no captcha response.

            return false;
        }

        $key = Kohana::config('sprout.recaptcha_private_key');
        if (!$key) throw new Exception('ReCAPTCHA key not found');

        // prep data for post
        $data = array();
        $data['secret'] = $key;
        $data['response'] = $_POST['g-recaptcha-response'];
        $data['remoteip'] = Request::userIp();

        // post request and receive json answer
        $res = HttpReq::req('https://www.google.com/recaptcha/api/siteverify', array('method' => 'post'), $data);
        $res = json_decode($res, true);

        // hopefully return true or false
        if (! is_bool($res['success'])) throw new Exception('Invalid captcha return data');
        return $res['success'];
    }

}

