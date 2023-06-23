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
use Sprout\Helpers\DatabaseSync;
use Sprout\Helpers\Subsites;

class SubsitesTest extends TestCase
{

    public static function setUpBeforeClass(): void
    {
        $sync = new DatabaseSync(true);
        $sync->loadXml(APPPATH . 'db_struct.xml');
        $sync->updateDatabase();

    }

    /**
    **/
    public function testGetAbsRootMissing()
    {
        $this->expectException(InvalidArgumentException::class);
        Subsites::getAbsRoot(0);
    }

    public function testGetAbsRoot()
    {
        $result = Subsites::getAbsRoot(1);
        $this->assertNotNull($result);
        $this->assertStringContainsString('http://', $result);
        $this->assertStringNotContainsString('http:///', $result);
        $this->assertStringContainsString($_SERVER['HTTP_HOST'], $result);
        $this->assertStringContainsString(Kohana::config('config.site_domain'), $result);
        $this->assertNotFalse(preg_match('!/$!', $result));
    }

}
