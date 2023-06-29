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
use Sprout\Helpers\ContentReplace;


class ContentReplaceTest extends TestCase
{

    public static function expandoData()
    {
        return array(
            array(
                '<p>Just a paragraph</p>',
                'TEST_LINK',
                '<p>Just a paragraph</p>'
            ),
            array(
                '<p>Before</p><div>Not an expando</div>',
                'TEST_LINK',
                '<p>Before</p><div>Not an expando</div>'
            ),
            array(
                '<p>Before</p><div class="expando">Inside expando</div>',
                'TEST_LINK',
                '<p>Before</p><p><a href="TEST_LINK">More information</a></p>'
            ),
            array(
                '<p>Before</p><div class="expando">Inside expando</div>',
                null,
                '<p>Before</p>'
            ),
            array(
                '<p>Before</p><div class="expando">Inside expando</div><p>After</p>',
                'TEST_LINK',
                '<p>Before</p><p>After</p><p><a href="TEST_LINK">More information</a></p>'
            ),
            array(
                '<p>Before</p><div align="center" class="expando">Inside expando</div><p>After</p>',
                'TEST_LINK',
                '<p>Before</p><p>After</p><p><a href="TEST_LINK">More information</a></p>'
            ),
            array(
                '<p>Before</p><div class="expando" align="center">Inside expando</div><p>After</p>',
                'TEST_LINK',
                '<p>Before</p><p>After</p><p><a href="TEST_LINK">More information</a></p>'
            ),
            array(
                '<p>Before</p><div   class = "expando"  >Inside expando</div><p>After</p>',
                'TEST_LINK',
                '<p>Before</p><p>After</p><p><a href="TEST_LINK">More information</a></p>'
            ),
            array(
                '<p>Before</p><div class="expando smalltext">Inside expando</div><p>After</p>',
                'TEST_LINK',
                '<p>Before</p><p>After</p><p><a href="TEST_LINK">More information</a></p>'
            ),
            array(
                '<p>Before</p><div class="smalltext expando">Inside expando</div><p>After</p>',
                'TEST_LINK',
                '<p>Before</p><p>After</p><p><a href="TEST_LINK">More information</a></p>'
            ),
            array(
                '<p>Before</p><div class="exp ando">Inside expando</div><p>After</p>',
                'TEST_LINK',
                '<p>Before</p><div class="exp ando">Inside expando</div><p>After</p>'
            ),
            array(
                '<p>Before</p><div class="expando">Inside expando</div><p>Middle</p><div class="expando">Inside expando</div>',
                'TEST_LINK',
                '<p>Before</p><p>Middle</p><p><a href="TEST_LINK">More information</a></p>'
            ),
            array(
                '<div class="expando">Inside expando</div><p>Middle</p><div class="expando">Inside expando</div>',
                'TEST_LINK',
                '<p>Middle</p><p><a href="TEST_LINK">More information</a></p>'
            ),
            array(
                '<p>Before</p><div class="expando">Inside expando</div><p>Middle</p><div class="expando">Inside expando</div><p>After</p>',
                'TEST_LINK',
                '<p>Before</p><p>Middle</p><p>After</p><p><a href="TEST_LINK">More information</a></p>'
            ),
            array(
                '<p>Before</p><div class="expando">Inside <div>Another div</div> expando</div>',
                'TEST_LINK',
                '<p>Before</p><p><a href="TEST_LINK">More information</a></p>'
            ),
        );
    }


    /**
    * @dataProvider expandoData
    **/
    public function testExpandolink($html, $replacement, $expected)
    {
        $this->assertEquals($expected, ContentReplace::expandolink($html, $replacement));
    }

}
