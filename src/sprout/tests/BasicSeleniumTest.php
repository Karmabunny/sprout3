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

use Sprout\Helpers\PHPUnit_Sprout_Selenium2Testcase;


class BasicSeleniumTest extends PHPUnit_Sprout_Selenium2Testcase
{

    /**
    * Just load the home page and check there is at least a single H1 on the page
    **/
    public function testHomePage()
    {
        $this->openURL('');
        $e = $this->session->elements('css selector', 'h1');
        $this->assertNotEquals(0, count($e));
    }


    /**
    * Load the admin and check it looks valid
    **/
    public function testAdminLogin()
    {
        $this->openURL('admin');
        $e = $this->session->elements('css selector', '#main-heading h2');
        $this->assertEquals(1, count($e));
        if ($e[0]) {
            $this->assertEquals('Login', $e[0]->text());
        }
    }
}

