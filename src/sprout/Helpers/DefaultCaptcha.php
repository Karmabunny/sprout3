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
* Default Sprout CAPTCHA; doesn't need to talk to external servers
**/
class DefaultCaptcha
{

    /**
    * Shows a captcha field
    **/
    public static function field()
    {
        Session::instance();


        $num = mt_rand(0,99999);

        echo '<div class="captcha">';
        echo '    <div class="info">';
        echo '        <a href="javascript:;" onclick="$(\'img.captcha\').attr(\'src\', \'SITE/captcha/image/' . $num . '?x=\' + new Date().getTime());">';
        echo '            <img src="ROOT/media/images/icons-16x16/refresh.png" alt="Refresh" title="Refresh" width="16" height="16">';
        echo '        </a>';
        echo '        <a href="SITE/captcha/about" rel="facebox">';
        echo '            <img src="ROOT/media/images/icons-16x16/help.png" alt="Help" title="Help" width="16" height="16">';
        echo '        </a>';
        echo '    </div>';
        echo '    <img src="SITE/captcha/image/' . $num . '" alt="" width="200" height="50" class="captcha">';
        echo '    <input type="text" name="captcha_text" class="captcha" autocomplete="off">';
        echo '    <input type="hidden" name="captcha_num" value="' . $num . '">';
        echo '</div>';
    }

    /**
    * Checks the captcha field against the submitted text
    **/
    public static function check()
    {
        Session::instance();

        if (empty($_SESSION['captcha'])) return false;

        $_POST['captcha_num'] = (int) $_POST['captcha_num'];

        if ($_POST['captcha_num'] == 0) return false;
        if (! isset($_SESSION['captcha'][$_POST['captcha_num']])) return false;

        $exp = strtolower($_SESSION['captcha'][$_POST['captcha_num']]);
        $prov = strtolower($_POST['captcha_text']);

        unset ($_SESSION['captcha'][$_POST['captcha_num']]);

        if ($exp != $prov) {
            return false;
        }

        return true;
    }

}


