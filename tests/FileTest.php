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
use Sprout\Helpers\File;


class FileTest extends TestCase
{

    public function testMimetype()
    {
        $this->assertEquals('image/jpeg', File::mimetype('test.jpg'));
        $this->assertEquals('image/jpeg', File::mimetype('test.jpeg'));
        $this->assertEquals('application/octet-stream', File::mimetype('test.poo'));
    }

    public function testParseSizeStringSimple()
    {
        $this->assertEquals(array('c', 100, 100, 'center', 'center', null), File::parseSizeString('c100x100'));
        $this->assertEquals(array('c', 100, 150, 'center', 'center', null), File::parseSizeString('c100x150'));
    }

    public function testParseSizeStringPosition()
    {
        $this->assertEquals(array('c', 100, 100, 'left', 'top', null), File::parseSizeString('c100x100-lt'));
        $this->assertEquals(array('c', 100, 150, 'left', 'top', null), File::parseSizeString('c100x150-lt'));
        $this->assertEquals(array('c', 100, 150, 'right', 'top', null), File::parseSizeString('c100x150-rt'));
    }

    public function testParseSizeStringQuality()
    {
        $this->assertEquals(array('c', 100, 150, 'left', 'top', 10), File::parseSizeString('c100x150-lt~10'));
        $this->assertEquals(array('c', 100, 150, 'right', 'top', 100), File::parseSizeString('c100x150-rt~100'));
        $this->assertEquals(array('c', 100, 150, 'center', 'center', 10), File::parseSizeString('c100x150~10'));
        $this->assertEquals(array('c', 100, 150, 'center', 'center', 100), File::parseSizeString('c100x150~100'));
    }
}
