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

use Sprout\Helpers\Subsites;

class subsitesTest extends PHPUnit_Framework_TestCase
{

    /**
    * @expectedException InvalidArgumentException
    **/
    public function testGetAbsRootMissing()
    {
        Subsites::getAbsRoot(0);
    }

    public function testGetAbsRoot()
    {
        $result = Subsites::getAbsRoot(1);
        $this->assertNotNull($result);
        $this->assertContains('http://', $result);
        $this->assertNotContains('http:///', $result);
        $this->assertContains($_SERVER['HTTP_HOST'], $result);
        $this->assertContains(Kohana::config('config.site_domain'), $result);
        $this->assertNotFalse(preg_match('!/$!', $result));
    }

}
