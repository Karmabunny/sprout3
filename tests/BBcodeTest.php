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
use Sprout\Helpers\BbCode;


class BBcodeTest extends TestCase
{

    public function testBold()
    {
        $this->assertTrue(BbCode::inline('aaa') === 'aaa');
        $this->assertTrue(BbCode::inline('aa[b]a[/b]') === 'aa<b>a</b>');
        $this->assertTrue(BbCode::inline('aa[b]a') === 'aa[b]a');
        $this->assertTrue(BbCode::inline('aa[b]a[/b]', array('b')) === 'aa<b>a</b>');
        $this->assertTrue(BbCode::inline('aa[b]a[/b]', array()) === 'aa[b]a[/b]');
    }

    public function testUrl()
    {
        $this->assertTrue(BbCode::inline('aaa') === 'aaa');
        $this->assertTrue(BbCode::inline('aa[url=http://example.com]a[/url]') === 'aa<a href="http://example.com">a</a>');
        $this->assertTrue(BbCode::inline('aa[url=http://example.com]a') === 'aa[url=http://example.com]a');
        $this->assertTrue(BbCode::inline('aa[url http://example.com]a') === 'aa[url http://example.com]a');
        $this->assertTrue(BbCode::inline('aa[url http://example.com]a[/url]') === 'aa[url http://example.com]a[/url]');
        $this->assertTrue(BbCode::inline('aa[url]a') === 'aa[url]a');
        $this->assertTrue(BbCode::inline('aa[url=http://example.com]a[/url]', array('url')) === 'aa<a href="http://example.com">a</a>');
        $this->assertTrue(BbCode::inline('aa[url]a[/url]', array()) === 'aa[url]a[/url]');
    }

}
