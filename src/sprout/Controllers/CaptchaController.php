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

namespace Sprout\Controllers;

use Sprout\Helpers\BaseView;
use Sprout\Helpers\Security;
use Sprout\Helpers\Session;
use Sprout\Helpers\View;


/**
 * Used for generating CAPTCHA images and explanatory text
 */
class CaptchaController extends Controller
{

    /**
     * Render a CAPTCHA image
     * See https://en.wikipedia.org/wiki/CAPTCHA for information about CAPTCHAs
     * @param int $num Number used to differentiate between multiple CAPTCHA codes held in session
     * @return void Outputs image content after setting the appropriate content-type header
     */
    public function image($num)
    {
        Session::instance();

        $num = (int) $num;

        $captcha_code = Security::randStr(mt_rand(8,10), 'QWERTYUOPASDFGHJKLZXCVBNMqwertyupasdfghjkzxcvbnm');

        $_SESSION['captcha'][$num] = $captcha_code;


        $width = 200;
        $height = 50;

        $my_image = imagecreatetruecolor($width, $height);

        imagefill($my_image, 0, 0, 0x000000);

        // add noise
        for ($c = 0; $c < 10; $c++) {
            $x = mt_rand(-20, $width + 20);
            $y = mt_rand(-20, $height + 20);
            $x2 = mt_rand(-20, $width + 20);
            $y2 = mt_rand(-20, $height + 20);
            imageline($my_image, $x, $y, $x2, $y2, 0x333333);
        }

        // Background text
        for ($i = 0; $i < 4; $i++) {
            $x = mt_rand(5, 100);
            $y = mt_rand(10, 40);
            $angle = mt_rand(-30, 30);
            imagettftext ($my_image, 12, $angle, $x, $y, 0x777777, DOCROOT . 'media/fonts/DejaVuSans.ttf', Security::randStr(10));
        }

        // Real text
        $x = mt_rand(15, 35);
        $y = mt_rand(30, 35);
        $angle = mt_rand(-10, 10);
        imagettftext ($my_image, 14, $angle, $x, $y, 0xFFFFFF, DOCROOT . 'media/fonts/DejaVuSans.ttf', $captcha_code);


        header('Content-type: image/jpeg');
        imagejpeg($my_image);

        imagedestroy($my_image);
    }



    /**
     * Output info about CAPTCHAs; should be displayed in a popup
     * @return void Outputs HTML directly
     */
    public function about()
    {
        $text = '<p>A captcha is a puzzle which is easy for humans to solve, but hard for computers to solve.</p>';
        $text .= '<p>They are designed to stop spam-bots from attacking the website, because the computer program
            which the spam-bot is running cannot solve the captcha.</p>';
        $text .= '<p>When entering the captcha, letter case is not important.</p>';
        $text .= '<p>If you cannot read the captcha, you can generate a new one by clicking on the "Refresh" icon.</p>';

        $page_view = BaseView::create('skin/popup');
        $page_view->page_title = 'What is a captcha?';
        $page_view->main_content = $text;
        echo $page_view->render();
    }

}
