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
use Sprout\Helpers\Sprout;


/**
* Test suite
**/
class SproutTest extends TestCase
{

    public function testIndexDotPhp()
    {
        $this->assertTrue(constant('IN_PRODUCTION') !== null);
        $this->assertTrue(constant('DOCROOT') !== null);
        $this->assertTrue(constant('APPPATH') !== null);
    }

    public function testCheckRedirect()
    {
        $this->assertTrue(Sprout::checkRedirect("http://www.google.com.au"));
        $this->assertTrue(Sprout::checkRedirect("http://maps.google.com.au"));
        $this->assertTrue(Sprout::checkRedirect("/about_us"));

        $this->assertFalse(Sprout::checkRedirect("//"));
        $this->assertFalse(Sprout::checkRedirect("http://"));
        $this->assertFalse(Sprout::checkRedirect("maps.google.com.au"));

        $this->assertFalse(Sprout::checkRedirect(array()));
    }

    public function testAbsRoot()
    {
        $this->assertTrue(preg_match('!http://!', Sprout::absRoot()) !== false);
        $this->assertTrue(preg_match('!http://!', Sprout::absRoot('http')) !== false);
        $this->assertTrue(preg_match('!https://!', Sprout::absRoot('https')) !== false);

        $result = Sprout::absRoot();
        $this->assertContains('http://', $result);
        $this->assertNotContains('http:///', $result);
        $this->assertContains(Kohana::config('config.site_domain'), $result);
        $this->assertNotFalse(preg_match('!/$!', $result));
    }

    public function testRandStr()
    {
        $rand = Sprout::randStr();
        $this->assertTrue(strlen($rand) == 16);

        $rand = Sprout::randStr(10);
        $this->assertTrue(strlen($rand) == 10);

        $rand = Sprout::randStr(30, 'ab');
        $this->assertEquals(30, strlen($rand));
        $this->assertContains('a', $rand);
        $this->assertContains('b', $rand);
        $this->assertNotContains('c', $rand);
    }

    public function testTimeAgo()
    {
        $this->assertTrue(Sprout::timeAgo(0.35) == 'Just now');
        $this->assertTrue(Sprout::timeAgo(1.999) == 'Just now');
        $this->assertTrue(Sprout::timeAgo(0) == 'Just now');
        $this->assertTrue(Sprout::timeAgo(1) == 'Just now');
        $this->assertTrue(Sprout::timeAgo(2) == '2 seconds ago');
        $this->assertTrue(Sprout::timeAgo(59) == '59 seconds ago');
        $this->assertTrue(Sprout::timeAgo(60) == '1 minute ago');
        $this->assertTrue(Sprout::timeAgo(61) == '1 minute ago');
        $this->assertTrue(Sprout::timeAgo(60 + 59) == '1 minute ago');
        $this->assertTrue(Sprout::timeAgo(60 * 2) == '2 minutes ago');
        $this->assertTrue(Sprout::timeAgo(60 * 3) == '3 minutes ago');
        $this->assertTrue(Sprout::timeAgo(60 * 60 - 1) == '59 minutes ago');
        $this->assertTrue(Sprout::timeAgo(60 * 60) == '1 hour ago');
        $this->assertTrue(Sprout::timeAgo(60 * 60 + 1) == '1 hour ago');
        $this->assertTrue(Sprout::timeAgo(60 * 60 * 2) == '2 hours ago');
        $this->assertTrue(Sprout::timeAgo(60 * 60 * 3) == '3 hours ago');
        $this->assertTrue(Sprout::timeAgo(60 * 60 * 23) == '23 hours ago');
        $this->assertTrue(Sprout::timeAgo(60 * 60 * 23 + 1) == '23 hours ago');
        $this->assertTrue(Sprout::timeAgo(60 * 60 * 24 - 1) == '23 hours ago');
        $this->assertTrue(Sprout::timeAgo(60 * 60 * 24) == '1 day ago');
        $this->assertTrue(Sprout::timeAgo(60 * 60 * 24 + 1) == '1 day ago');
        $this->assertTrue(Sprout::timeAgo(60 * 60 * 24 * 2) == '2 days ago');

        $this->assertTrue(Sprout::timeAgo(array()) == 'Just now');
    }


    /**
    * Special file links which should be updated
    **/
    public static function dataSpecialFileLinksWorking()
    {
        return array(
            array('<p><a href="files/531_ted303_kis_strategy_overview_fa.pdf" title="A Strategy">A Strategy</a></p>'),
            array('<p><a href="files/531.pdf">A Strategy</a></p>'),
            array('<p><a href="files/hey_ya.pdf">A Strategy</a></p>'),
            array('<p><a href="files/hey ya.pdf">A Strategy</a></p>'),
            array('<p><a href="/files/hey_ya.pdf">A Strategy</a></p>'),
            array('<p><a href="files/hey_ya.pdf">A Strategy</a></p> <p><a href="files/hey_ya.pdf">A Strategy</a></p>'),
            array('<p><a href="files/hey ya.doc">A Strategy</a></p>'),
            array('<p><a href="files/hey ya.doc" class="button">A Strategy</a></p>'),
            array('<p><a title="whee" href="files/531.pdf">A Strategy</a></p>'),
        );
    }

    /**
    * Special file links which should be updated
    * @dataProvider dataSpecialFileLinksWorking
    **/
    public function testSpecialFileLinksWorking($html)
    {
        $html = Sprout::specialFileLinks($html);
        $this->assertContains('class="document', $html);
        $this->assertContains('target="_blank"', $html);
        $this->assertContains('onclick="ga(', $html);
    }

    /**
    * Remote file links which shouldn't be updated
    **/
    public function testRemoteSpecialFileLinksWorking()
    {
        $html = Sprout::specialFileLinks('<p><a href="http://www.southaustralia.biz/files/something_remote.pdf">Remote files R grate</a></p>');
        $this->assertContains('href="http://www.southaustralia.biz/files', $html);
    }

    /**
    * Special file links which have a class in the original HTML
    **/
    public function testSpecialFileLinksClass()
    {
        $html = Sprout::specialFileLinks('<p><a href="files/hey ya.doc" class="button">A Strategy</a></p>');
        $this->assertContains('class="document document-doc button"', $html);
        $this->assertContains('target="_blank"', $html);
        $this->assertContains('onclick="ga(', $html);
        $this->assertContains('>A Strategy<', $html);
    }

    /**
    * File name containing quote to be properly escaped in JS
    **/
    public function testSpecialFileLinksJSQuote()
    {
        $html = Sprout::specialFileLinks('<p><a href="files/hey\'ya.doc">A Strategy</a></p>');
        $this->assertContains('onclick="ga(', $html);
        $this->assertContains("'hey\'ya.doc'", $html);
    }

    /**
    * File name or link content containing HTML entities
    **/
    public function testSpecialFileLinksHTMLEntities()
    {
        $html = Sprout::specialFileLinks('<p><a href="files/hey&amp;ya.doc" class="test&amp;test">A &amp; Strategy</a></p>');
        $this->assertContains('hey&amp;ya.doc', $html);
        $this->assertContains('A &amp; Strategy', $html);
        $this->assertContains('class="document document-doc test&amp;test"', $html);
    }

    /**
    * Special file links which shouldn't be updated
    **/
    public static function dataSpecialFileLinksNotWorking()
    {
        return array(
            array('<p><a href="files/blah.pdf">A Strategy<br>Of things</a></p>'),
            array('<p><a href="files/blah.pdf"><img src="aaa"></a></p>'),
            array('<p><a href="files/blah.pdf">aaa <img src="aaa"> aaa</a></p>'),
            array('<p><a href="library/blah.pdf">aaa</a></p>'),
            array('<p><a href="blah.pdf">aaa</a></p>'),
            array('<p>aaaa</p>'),
            array('<p>files/blah.pdf</p>'),
            array('<p>blah.pdf</p>'),
        );
    }

    /**
    * Special file links which shouldn't be updated
    * @dataProvider dataSpecialFileLinksNotWorking
    **/
    public function testSpecialFileLinksNotWorking($html)
    {
        $html = Sprout::specialFileLinks($html);
        $this->assertFalse(strpos($html, 'class="document'));
    }


    public function testIpaddressPlain()
    {
        $this->assertTrue(Sprout::ipaddressInArray('192.168.1.1', array('192.168.1.1')));
        $this->assertFalse(Sprout::ipaddressInArray('192.168.1.1', array('192.168.1.2')));
        $this->assertTrue(Sprout::ipaddressInArray('192.168.1.1', array('192.168.1.2', '192.168.1.1')));
        $this->assertTrue(Sprout::ipaddressInArray('192.168.1.1', array('192.168.1.1', '192.168.1.2')));
        $this->assertFalse(Sprout::ipaddressInArray('192.168.1.1', array('192.168.1.2', '192.168.1.3')));
    }

    public function testIpaddressCIDR()
    {
        $this->assertTrue(Sprout::ipaddressInArray('192.168.1.1', array('192.168.1.1/32')));
        $this->assertTrue(Sprout::ipaddressInArray('192.168.1.1', array('192.168.1.0/31')));
        $this->assertTrue(Sprout::ipaddressInArray('192.168.1.1', array('192.168.1.0/24')));
        $this->assertTrue(Sprout::ipaddressInArray('192.168.1.1', array('192.168.0.0/16')));
        $this->assertTrue(Sprout::ipaddressInArray('192.168.1.1', array('192.0.0.0/8')));
        $this->assertTrue(Sprout::ipaddressInArray('192.168.1.1', array('128.0.0.0/1')));
        $this->assertTrue(Sprout::ipaddressInArray('192.168.1.1', array('0.0.0.0/0')));

        $this->assertFalse(Sprout::ipaddressInArray('192.168.1.1', array('192.168.1.2/32')));
        $this->assertFalse(Sprout::ipaddressInArray('192.168.1.1', array('192.168.2.0/24')));
        $this->assertFalse(Sprout::ipaddressInArray('192.168.1.1', array('192.262.1.1/16')));
        $this->assertFalse(Sprout::ipaddressInArray('192.168.1.1', array('0.0.0.0/1')));
    }

    public function testInstanceSuccess()
    {
        $this->assertInstanceOf('Sprout\Helpers\Pdb', Sprout::instance('Sprout\Helpers\Pdb'));
    }

    /**
    * @expectedException InvalidArgumentException
    **/
    public function testInstanceAbstractClass()
    {
        Sprout::instance('Sprout\Helpers\WorkerBase');
    }

    /**
    * @expectedException InvalidArgumentException
    **/
    public function testInstanceMissingClass()
    {
        Sprout::instance('Sprout\Helpers\MissingClass');
    }


    public static function dataInstanceNotImplements()
    {
        return [
            ['Sprout\Helpers\Enc', 'Sprout\Controllers\Controller'],
            ['Sprout\Helpers\Enc', ['Sprout\Controllers\Controller']],
            ['Sprout\Helpers\Enc', 'Sprout\Helpers\FrontEndEntrance'],
            ['Sprout\Helpers\Enc', ['Sprout\Helpers\FrontEndEntrance']],
            ['Sprout\Helpers\Enc', ['Sprout\Controllers\Controller', 'Sprout\Helpers\FrontEndEntrance']],
        ];
    }

    /**
    * @dataProvider dataInstanceNotImplements
    * @expectedException InvalidArgumentException
    **/
    public function testInstanceNotImplements($class, $base_class)
    {
        Sprout::instance($class, $base_class);
    }

    public function testIterableFirst()
    {
        $this->assertEquals(Sprout::iterableFirst(['a' => 'b']), ['a', 'b']);
        $this->assertEquals(Sprout::iterableFirst([0 => 'b']), [0, 'b']);
        $this->assertEquals(Sprout::iterableFirst([1 => 2]), [1, 2]);
        $this->assertEquals(Sprout::iterableFirst(['a' => 'b', 'c' => 'd', 'e' => 'h']), ['a', 'b']);
        $this->assertEquals(Sprout::iterableFirst([]), null);
    }

    public function testIterableFirstKey()
    {
        $this->assertEquals(Sprout::iterableFirstKey(['a' => 'b']), 'a');
        $this->assertEquals(Sprout::iterableFirstKey([2 => 'test']), 2);
        $this->assertEquals(Sprout::iterableFirstKey(['a' => 'b', 'c' => 'd', 'e' => 'h']), 'a');
        $this->assertEquals(Sprout::iterableFirstKey([]), null);
    }

    public function testIterableFirstValue()
    {
        $this->assertEquals(Sprout::iterableFirstValue(['a' => 'b']), 'b');
        $this->assertEquals(Sprout::iterableFirstValue([2 => 'test']), 'test');
        $this->assertEquals(Sprout::iterableFirstValue(['a' => 'b', 'c' => 'd', 'e' => 'h']), 'b');
        $this->assertEquals(Sprout::iterableFirstValue([]), null);
    }
}

