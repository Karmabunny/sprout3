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

use karmabunny\pdb\Exceptions\ConnectionException;
use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\WorkerLinkChecker;

class WorkerLinkCheckerTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        try {
            Pdb::query("SELECT 1", [], 'null');
        } catch (ConnectionException $ex) {
            self::markTestSkipped('mysql is not available right now');
        }
    }

    /**
    * URLs which are okay
    **/
    public static function dataCheckOkay()
    {
        return array(
            array('http://www.google.com/'),
            array('http://www.google.com'),
            array('https://www.google.com'),
            array('mailto:bob@example.com'),
            array('ftp://mirror.internode.on.net/pub/'),
            array('http://www.google.com/intl/en/policies/terms'),
            array('http://www.google.com/intl/en/policies/terms/'),
            array('https://www.google.com/intl/en/policies/terms'),
            array('https://www.google.com/intl/en/policies/terms/'),
        );
    }


    /**
    * URLs which are okay
    * @dataProvider dataCheckOkay
    **/
    public function testCheckOkay($url)
    {
        $obj = new WorkerLinkChecker();
        $this->assertTrue($obj->checkUrl($url));
    }


    /**
    * URLs which are not okay
    **/
    public static function dataCheckBad()
    {
        return array(
            array('http://www.google.com/asdfghjklasdfghjkladfghjk'),
            array('http://'),
            array('htt p : // thejosh.info'),
            array('gdskfsfnsafnsknf://'),
        );
    }


    /**
    * URLs which are not okay
    * @dataProvider dataCheckBad
    **/
    public function testCheckBad($url)
    {
        $obj = new WorkerLinkChecker();
        $this->assertNotTrue($obj->checkUrl($url));
    }

}
