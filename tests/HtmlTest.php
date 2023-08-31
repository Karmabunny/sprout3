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
use Sprout\Helpers\Html;


class HtmlTest extends TestCase
{

    function dataNamespaceName()
    {
        return [
            // simple
            ['moderate', 'foo', 'moderate[foo]'],
            // deep
            ['moderate', 'foo[bar][][action]', 'moderate[foo][bar][][action]'],
            // nested namespace
            ['bar[baz]', 'foo[]', 'bar[baz][foo][]'],

            // merged namespace, simple
            ['foo', 'foo[]', 'foo[]', true],
            // merged namespace, nested, full
            ['foo[bar]', 'foo[bar][baz]', 'foo[bar][baz]', true],
            // merged namespace, nested, partial
            ['foo[bar]', 'foo[baz]', 'foo[bar][baz]', true],

            // no merged namespace, simple
            ['foo', 'foo[]', 'foo[foo][]', false],
            // no merged namespace, nested, full
            ['foo[bar]', 'foo[bar][baz]', 'foo[bar][foo][bar][baz]', false],
            // no merged namespace, nested, partial
            ['foo[bar]', 'foo[baz]', 'foo[bar][foo][baz]', false],
        ];
    }


    /**
     * @dataProvider dataNamespaceName
     */
    public function testNamespaceName($namespace, $name, $expected, $merge = true)
    {
        $actual = Html::namespaceName($namespace, $name, $merge);
        $this->assertEquals($expected, $actual);
    }



    public function testNamespace()
    {
        $html = <<<HTML
            <input name="test">
            <input name="foo[bar][]">
        HTML;

        $expected = <<<HTML
            <input name="foo[test]">
            <input name="foo[bar][]">
        HTML;

        $actual = Html::namespace('foo', $html, true);
        $this->assertEquals($expected, $actual);

        // no merge.
        $expected = <<<HTML
            <input name="foo[test]">
            <input name="foo[foo][bar][]">
        HTML;

        $actual = Html::namespace('foo', $html, false);
        $this->assertEquals($expected, $actual);

        // Test nested namespaces.
        $html = <<<HTML
            <input name="foo[test]">
            <input name="foo[bar][]">
        HTML;

        $expected = <<<HTML
            <input name="foo[bar][test]">
            <input name="foo[bar][]">
        HTML;

        $actual = Html::namespace('foo[bar]', $html, true);
        $this->assertEquals($expected, $actual);

        // no merge.
        $expected = <<<HTML
            <input name="foo[bar][foo][test]">
            <input name="foo[bar][foo][bar][]">
        HTML;

        $actual = Html::namespace('foo[bar]', $html, false);
        $this->assertEquals($expected, $actual);
    }


    public function testNamespaceMerge()
    {
        $html = <<<HTML
            <input name="foo"/>
            <input name = "bar[]" checked>
            <input type="checkbox" name="list['of']['things']" />
            <input type='text' name="ns[existing][]" value="test" />
            <input type broken name='numbers[0][1][2]' input />
            <select name='options' required></select>
        HTML;

        $expected = <<<HTML
            <input name="ns[foo]"/>
            <input name = "ns[bar][]" checked>
            <input type="checkbox" name="ns[list]['of']['things']" />
            <input type='text' name="ns[existing][]" value="test" />
            <input type broken name='ns[numbers][0][1][2]' input />
            <select name='ns[options]' required></select>
        HTML;

        $actual = Html::namespace('ns', $html, true);
        $this->assertEquals($expected, $actual);

        $html = <<<HTML
            <input name="foo">
            <input name="moderate[other][]">
            <input name="moderate[php\\class\\name][item]">
        HTML;

        $expected = <<<HTML
            <input name="moderate[php\\class\\name][foo]">
            <input name="moderate[php\\class\\name][other][]">
            <input name="moderate[php\\class\\name][item]">
        HTML;

        $actual = Html::namespace('moderate[php\\class\\name]', $html, true);
        $this->assertEquals($expected, $actual);
    }

}
