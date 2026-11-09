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

use PHPUnit\Framework\TestCase;
use Sprout\Helpers\HttpReq;
use Sprout\SproutVisorTrait;

class SessionTest extends TestCase
{
    use SproutVisorTrait;


    public function testSessionOk()
    {
        $random = uniqid();

        $url = self::$server->getHostUrl() . "/test/session/instance";

        $res = HttpReq::req($url . '?set=' . $random, []);
        $this->assertEquals($random, $res);

        $headers = HttpReq::getLastreqHeaders();
        $cookie = $headers['set-cookie'][0];

        // Random variable is preserved.
        $res = HttpReq::req($url, ['headers' => ['cookie' => $cookie]]);
        $this->assertEquals($random, $res);
    }


    public function testSessionWakeupBug()
    {
        $random = uniqid();

        $url = self::$server->getHostUrl() . "/test/session/instance";
        $res = HttpReq::req($url . '?set=' . $random, []);
        $this->assertEquals($random, $res);

        $headers = HttpReq::getLastreqHeaders();
        $cookie = $headers['set-cookie'][0];

        // This is the first serialisation of the wakeup bug.
        $url = self::$server->getHostUrl() . "/test/session/wakeup-bug";
        $res = HttpReq::req($url, ['headers' => ['cookie' => $cookie]]);
        $this->assertEquals($random, $res);

        // This is the deserialisation interrupted by the session init during wakeup.
        $res = HttpReq::req($url, ['headers' => ['cookie' => $cookie]]);
        $this->assertEquals($random, $res);
    }

}
