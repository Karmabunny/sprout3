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

class bootstrapConfigTest extends PHPUnit_Framework_TestCase
{

    public function setUp() {
        require_once DOCROOT . 'config/_bootstrap_config.php';
    }

    public function dataInProductionHttpHost()
    {
        return [
            // Genuine local domains
            ['local', false],
            ['localhost', false],
            ['sprout.local', false],
            ['sprout.localhost', false],

            // Genuine live domains
            ['example.com', true],
            ['localheros.com', true],

            // Possible hacking attempts; should be PROD domains
            ['local.example.com', true],
            ['localhost.example.com', true],
            ['localhostXexample.com', true],
        ];
    }

    /**
     * @dataProvider dataInProductionHttpHost
     */
    public function testInProductionHttpHost($http_host, $expect)
    {
        $_SERVER['HTTP_HOST'] = $http_host;
        $this->assertEquals($expect, BootstrapConfig::isInProduction());
    }

}