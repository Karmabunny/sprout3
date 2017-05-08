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
require_once DOCROOT . 'sprout/php_webdriver/PHPWebDriver/WebDriver.php';

use Exception;

use PHPUnit_Framework_TestCase;
use PHPWebDriver_WebDriver;

class PHPUnit_Sprout_Selenium2Testcase extends PHPUnit_Framework_TestCase
{
    protected $driver;
    protected $session;


    /**
    * Called before each test
    **/
    public function setUp()
    {
        if (!defined('BASE_URL')) {
            throw new Exception('No \'BASE_URL\' defined. This needs to be defined in your phpunit.xml');
        }

        try {
            $this->driver = new PHPWebDriver_WebDriver();
            $this->session = $this->driver->session();
        } catch (\PHPWebDriver_WebDriverCurlException $ex) {
            $this->markTestSkipped($ex->getMessage());
        }

        parent::setUp();
    }


    /**
    * Called after each test
    **/
    public function tearDown()
    {
        if ($this->session) {
            $this->session->close();
        }

        parent::tearDown();
    }


    /**
    * Open a given URL in the browser.
    * Does some clever stuff with base path
    **/
    public function openURL($url)
    {
        $url = trim($url, '/');
        $this->session->open(BASE_URL . $url);
    }

}
