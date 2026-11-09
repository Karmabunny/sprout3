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

namespace Sprout\TestModules\TestModule\Controllers;

use karmabunny\router\Route;
use Sprout\Controllers\Controller;
use Sprout\Helpers\BaseView;
use Sprout\Helpers\Cron;
use Sprout\Helpers\Session;
use Sprout\TestModules\TestModule\Helpers\SessionWakeup;

class TestController extends Controller
{
    public function home()
    {
        $view = BaseView::create('skin/wide');
        $view->page_title = 'Test';
        $view->main_content = 'testy test test';
        echo $view->render();
    }


    public function cronTest()
    {
        Cron::start('Test Cron');

        for ($i = 0; $i < 10; $i++) {
            Cron::message("Processing item {$i}");
            usleep(100 * 1000);
        }

        Cron::message('Finished');
        Cron::success();
    }


    #[Route('session/instance')]
    public function sessionTestInstance()
    {
        Session::instance();

        if (isset($_GET['set'])) {
            $_SESSION['random'] = $_GET['set'];
        }

        echo $_SESSION['random'] ?? 'fail';
    }


    #[Route('session/wakeup-bug')]
    public function sessionTestWakeupBug()
    {
        Session::instance();

        $_SESSION['wakeup'] = new SessionWakeup;
        echo $_SESSION['random'] ?? 'fail';
    }
}
