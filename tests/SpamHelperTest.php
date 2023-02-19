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
use Sprout\Helpers\Spam;


/**
* Test suite
**/
class SpamHelperTest extends TestCase
{

    public function testGlue()
    {
        $html = Spam::glue();

        $this->assertContains('<div', $html);
        $this->assertContains('</div>', $html);
        $this->assertContains('<input name="_email"', $html);
        $this->assertContains('id="f_', $html);
        $this->assertContains('<label for="f_', $html);

        // Label for= and input id= should match
        $count = preg_match_all('/="f_[^"]+"/', $html, $matches);
        $this->assertEquals(2, $count);
        $this->assertEquals($matches[0][0], $matches[0][1]);
    }

    public function testDetect()
    {
        $this->assertEmpty(Spam::detect(''));
        $this->assertEmpty(Spam::detect('a'));
        $this->assertEmpty(Spam::detect('ê'));

        $this->assertArrayHasKey('html', Spam::detect('<b>'));
        $this->assertArrayHasKey('html', Spam::detect('<b>hello</b>'));
        $this->assertArrayHasKey('html', Spam::detect('<img>'));
        $this->assertArrayNotHasKey('html', Spam::detect('< b >'));

        $this->assertArrayHasKey('url', Spam::detect('http://www.example.com'));
        $this->assertArrayHasKey('url', Spam::detect('www.example.com'));
        $this->assertArrayNotHasKey('url', Spam::detect('http example com'));

        $this->assertArrayHasKey('email', Spam::detect('test@example.com'));
        $this->assertArrayHasKey('email', Spam::detect('test.test@example.com'));
        $this->assertArrayNotHasKey('email', Spam::detect('test @ test'));
    }

}
