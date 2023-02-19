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
use Sprout\Helpers\Lnk;
use Sprout\Helpers\Register;


class LnkTest extends TestCase
{

    public function setUp()
    {
        Register::linkspec('\\Sprout\\Helpers\\LinkSpecExternal', 'External URL');
    }

    public function testExternal()
    {
        $spec = '{ "class":"\\\\Sprout\\\\Helpers\\\\LinkSpecExternal", "data":"http://www.chaoticrage.com" }';

        $this->assertTrue(Lnk::url($spec) === 'http://www.chaoticrage.com');
        $this->assertTrue(Lnk::atag($spec) === '<a href="http://www.chaoticrage.com" target="_blank">');
    }

    public function dataAtag()
    {
        return array(
            array(
                '{ "class":"\\\\Sprout\\\\Helpers\\\\LinkSpecExternal", "data":"http://www.chaoticrage.com" }',
                null,
                '<a href="http://www.chaoticrage.com" target="_blank">',
            ),
            array(
                '{ "class":"\\\\Sprout\\\\Helpers\\\\LinkSpecExternal", "data":"http://www.chaoticrage.com" }',
                array(),
                '<a href="http://www.chaoticrage.com" target="_blank">',
            ),
            array(
                '{ "class":"\\\\Sprout\\\\Helpers\\\\LinkSpecExternal", "data":"http://www.chaoticrage.com" }',
                array('class' => 'red'),
                '<a href="http://www.chaoticrage.com" class="red" target="_blank">',
            ),
            array(
                '{ "class":"\\\\Sprout\\\\Helpers\\\\LinkSpecExternal", "data":"http://www.chaoticrage.com" }',
                array('target' => 'aaa'),
                '<a href="http://www.chaoticrage.com" target="aaa">',
            ),
            array(
                '{ "class":"\\\\Sprout\\\\Helpers\\\\LinkSpecExternal", "data":"http://www.chaoticrage.com" }',
                array('target' => 'aaa', 'class' => 'red'),
                '<a href="http://www.chaoticrage.com" class="red" target="aaa">',
            ),
            array(
                '{ "class":"\\\\Sprout\\\\Helpers\\\\LinkSpecExternal", "data":"http://www.chaoticrage.com" }',
                array('target' => ''),
                '<a href="http://www.chaoticrage.com">',
            ),
            array(
                '{ "class":"\\\\Sprout\\\\Helpers\\\\LinkSpecExternal", "data":"http://www.chaoticrage.com" }',
                array('target' => null),
                '<a href="http://www.chaoticrage.com">',
            ),
            array(
                '{ "class":"\\\\Sprout\\\\Helpers\\\\LinkSpecExternal", "data":"http://www.chaoticrage.com" }',
                array('target' => '', 'class' => 'red'),
                '<a href="http://www.chaoticrage.com" class="red">',
            ),
        );
    }

    /**
    * @dataProvider dataAtag
    **/
    public function testAtag($spec, $attrs, $expected)
    {
        $this->assertEquals($expected, Lnk::atag($spec, $attrs));
    }


}
