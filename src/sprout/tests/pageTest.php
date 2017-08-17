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

use Sprout\Helpers\Page;
use Sprout\Helpers\Pdb;


class pageTest extends PHPUnit_Framework_TestCase
{

    public function testUrl()
    {
        $pages = Pdb::lookup('pages');

        if (count($pages) === 0) {
            $this->markTestSkipped('Cannot test page URLs without any pages in the database');
        }

        $integer = Page::url((int) key($pages));
        $this->assertTrue(is_string($integer));

        $string = Page::url((string) key($pages));
        $this->assertTrue(is_string($string));

        $this->assertTrue($integer == $string);

        $url = Page::url(2362728);
        $this->assertTrue($url == 'page/view_by_id/2362728');

        $url = Page::url('2362728');
        $this->assertTrue($url == 'page/view_by_id/2362728');

        $url = Page::url('abcde');
        $this->assertNull($url);

        $url = Page::url(array());
        $this->assertNull($url);
    }

}