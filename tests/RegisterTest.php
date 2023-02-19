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
use Sprout\Helpers\Register;

class RegisterTest extends TestCase
{

    public function testModerator()
    {
        Register::moderator('Abc');
        $mods = Register::getModerators();

        $this->assertInternalType('array', $mods);
        $this->assertTrue(in_array('Abc', $mods));
    }


    public function testExtrapage()
    {
        Register::extraPage('abc', 'Abc');
        $extra = Register::getExtraPages();

        $this->assertInternalType('array', $extra);
        $this->assertTrue($extra['abc'] == 'Abc');
    }


    public function testPageattr()
    {
        Register::pageattr('abc', 'Abc');
        Register::pageattr('def', 'Def', 'Sprout\\Helpers\\AttrEditorImage');

        $attrs = Register::getPageattrs();

        $this->assertInternalType('array', $attrs);
        $this->assertEquals(array('Abc', 'Sprout\\Helpers\\AttrEditorTextbox'), $attrs['abc']);
        $this->assertEquals(array('Def', 'Sprout\\Helpers\\AttrEditorImage'), $attrs['def']);
    }
}
