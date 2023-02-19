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
use Sprout\Helpers\Enc;
use Sprout\Helpers\Itemlist;


/**
* Test suite
**/
class EncHelperTest extends TestCase
{

    public function testCleanfunky()
    {
        $this->assertEquals(Enc::cleanfunky('a'), 'a');
        $this->assertEquals(Enc::cleanfunky('&'), '&');
        $this->assertEquals(Enc::cleanfunky('ê'), 'ê');

        // UTF-8 chars
        $this->assertEquals("\303\252", 'ê');
        $this->assertEquals(Enc::cleanfunky("\303\252"), 'ê');
        $this->assertEquals("\xC3\xAA", 'ê');
        $this->assertEquals(Enc::cleanfunky("\xC3\xAA"), 'ê');

        // Spaces (border case to 0x1F)
        $this->assertEquals(Enc::cleanfunky("\x20"), ' ');
        $this->assertEquals(Enc::cleanfunky(' '), ' ');

        // Some disallowed stuff
        $this->assertEquals(Enc::cleanfunky("\x1A"), '');
        $this->assertEquals(Enc::cleanfunky("\0"), '');
        $this->assertEquals(Enc::cleanfunky("\x1F"), '');

        // Some allowed stuff
        $this->assertEquals(Enc::cleanfunky("\t"), "\t");
        $this->assertEquals(Enc::cleanfunky("\n"), "\n");
        $this->assertEquals(Enc::cleanfunky("\r"), "\r");

        // Non-string inputs
        $this->assertEquals(Enc::cleanfunky(null), '');
        $this->assertEquals(Enc::cleanfunky(array()), '');
        $this->assertEquals(Enc::cleanfunky(new Itemlist), '');
        $this->assertEquals(Enc::cleanfunky(new stdClass), '');
        $this->assertEquals(Enc::cleanfunky(123), '123');
        $this->assertEquals(Enc::cleanfunky(123.45), '123.45');
        $this->assertEquals(Enc::cleanfunky(123e12), (string) 123e12);
    }

    public function testHtml()
    {
        $this->assertEquals(Enc::html('a'), 'a');
        $this->assertEquals(Enc::html('&'), '&amp;');
        $this->assertEquals(Enc::html('"'), '&quot;');
        $this->assertEquals(Enc::html('>'), '&gt;');
        $this->assertEquals(Enc::html('<'), '&lt;');
        $this->assertEquals(Enc::html('ê'), 'ê');
        $this->assertEquals(Enc::html("\0"), '');
        $this->assertEquals(Enc::html("\x1A"), '');
        $this->assertEquals(Enc::html(array()), '');

        $this->assertEquals('&amp;amp;', Enc::html('&amp;'));
        $this->assertEquals('&amp;quot;', Enc::html('&quot;'));
        $this->assertEquals('&amp;gt;', Enc::html('&gt;'));
        $this->assertEquals('&amp;lt;', Enc::html('&lt;'));
    }

    public function htmlNoDup()
    {
        $this->assertEquals(Enc::htmlNoDup('a'), 'a');
        $this->assertEquals(Enc::htmlNoDup('&'), '&amp;');
        $this->assertEquals(Enc::htmlNoDup('"'), '&quot;');
        $this->assertEquals(Enc::htmlNoDup('>'), '&gt;');
        $this->assertEquals(Enc::htmlNoDup('<'), '&lt;');
        $this->assertEquals(Enc::htmlNoDup('ê'), 'ê');
        $this->assertEquals(Enc::htmlNoDup("\0"), '');
        $this->assertEquals(Enc::htmlNoDup("\x1A"), '');
        $this->assertEquals(Enc::htmlNoDup(array()), '');

        $this->assertEquals('&amp;', Enc::htmlNoDup('&amp;'));
        $this->assertEquals('&quot;', Enc::htmlNoDup('&quot;'));
        $this->assertEquals('&gt;', Enc::htmlNoDup('&gt;'));
        $this->assertEquals('&lt;', Enc::htmlNoDup('&lt;'));
    }

    public function textXml()
    {
        $this->assertEquals(Enc::xml('a'), 'a');
        $this->assertEquals(Enc::xml('&'), '&amp;');
        $this->assertEquals(Enc::xml('"'), '&quot;');
        $this->assertEquals(Enc::xml('>'), '&gt;');
        $this->assertEquals(Enc::xml('<'), '&lt;');
        $this->assertEquals(Enc::xml('&amp;'), '&amp;amp;');
        $this->assertEquals(Enc::xml('ê'), 'ê');
        $this->assertEquals(Enc::xml("\0"), '');
        $this->assertEquals(Enc::xml("\x1A"), '');
        $this->assertEquals(Enc::xml(array()), '');
    }

    public function textUrl()
    {
        $this->assertEquals(Enc::url('a'), 'a');
        $this->assertEquals(Enc::url('1'), '1');
        $this->assertEquals(Enc::url('hello?'), 'hello%3F');
        $this->assertEquals(Enc::url('hello how are you'), 'hello+how+are+you');
        $this->assertEquals(Enc::url('ê'), '%C3%AA');
        $this->assertEquals(Enc::url("\0"), '');
        $this->assertEquals(Enc::url("\x1A"), '');
        $this->assertEquals(Enc::url(array()), '');
    }

    public function testId()
    {
        $this->assertEquals(Enc::id('a'), 'a');
        $this->assertEquals(Enc::id('&'), '');
        $this->assertEquals(Enc::id('"'), '');
        $this->assertEquals(Enc::id('hey ya'), 'hey_ya');
        $this->assertEquals(Enc::id('ê'), '');
        $this->assertEquals(Enc::id('lowercase_id'), 'lowercase_id');
        $this->assertEquals(Enc::id('UPPERCASE_ID'), 'UPPERCASE_ID');
        $this->assertEquals(Enc::id('MIXED_case_ID'), 'MIXED_case_ID');
        $this->assertEquals(Enc::id('test--dashes'), 'test--dashes');
        $this->assertEquals(Enc::id('underscore_test'), 'underscore_test');
        $this->assertEquals(Enc::id('double__underscore__test'), 'double_underscore_test');
        $this->assertEquals(Enc::id('trailing_underscore_test_'), 'trailing_underscore_test');
        $this->assertEquals(Enc::id('no_symbols~!@#$%^&*()<>.,?/\'":;|\\]}[{`_no_symbols'), 'no_symbols_no_symbols');
        $this->assertEquals(Enc::id("\0"), '');
        $this->assertEquals(Enc::id("\x1A"), '');
        $this->assertEquals(Enc::id(array()), '');
    }

    public function testJs()
    {
        $this->assertEquals(Enc::js('a'), 'a');
        $this->assertEquals(Enc::js('"'), '\\"');
        $this->assertEquals(Enc::js('\''), '\\\'');
        $this->assertEquals(Enc::js('\\'), '\\\\');
        $this->assertEquals(Enc::js('hey ya'), 'hey ya');
        $this->assertEquals(Enc::js('ê'), 'ê');
        $this->assertEquals(Enc::js("\n"), '\n');
        $this->assertEquals(Enc::js("\0"), '');
        $this->assertEquals(Enc::js("\x1A"), '');
        $this->assertEquals(Enc::js(array()), '');
    }

    public function testHttpfield()
    {
        $this->assertEquals(Enc::httpfield('a'), 'a');
        $this->assertEquals(Enc::httpfield('&'), '');
        $this->assertEquals(Enc::httpfield('"'), '');
        $this->assertEquals(Enc::httpfield(':-.'), ':-.');
        $this->assertEquals(Enc::httpfield('[]'), '');
        $this->assertEquals(Enc::httpfield('hey ya'), 'hey_ya');
        $this->assertEquals(Enc::httpfield('ê'), '');
        $this->assertEquals(Enc::httpfield("\0"), '');
        $this->assertEquals(Enc::httpfield("\x1A"), '');
        $this->assertEquals(Enc::httpfield(array()), '');
    }

    public function testUrlname()
    {
        // General conformance
        $this->assertEquals(Enc::urlname('a'), 'a');
        $this->assertEquals(Enc::urlname('"'), '');
        $this->assertEquals(Enc::urlname(':-.[]'), '');
        $this->assertEquals(Enc::urlname('ê'), '');
        $this->assertEquals(Enc::urlname("\0"), '');
        $this->assertEquals(Enc::urlname("\x1A"), '');
        $this->assertEquals(Enc::urlname(array()), '');

        // Space character tests
        $this->assertEquals(Enc::urlname('hey ya', '_'), 'hey_ya');
        $this->assertEquals(Enc::urlname('hey ya', '-'), 'hey-ya');

        // Special case of '&' => 'and'
        $this->assertEquals(Enc::urlname(' '), '');
        $this->assertEquals(Enc::urlname('&'), '');
        $this->assertEquals(Enc::urlname(' & '), '');
        $this->assertEquals(Enc::urlname(' hello world ', '_'), 'hello_world');
        $this->assertEquals(Enc::urlname(' hello  world ', '_'), 'hello_world');
        $this->assertEquals(Enc::urlname(' hello & world ', '_'), 'hello_and_world');
        $this->assertEquals(Enc::urlname(' hello world & ', '_'), 'hello_world');
        $this->assertEquals(Enc::urlname(' & hello world & ', '_'), 'hello_world');
    }

    public function testJsdate()
    {
        // The mysql path through the helper
        $this->assertEquals(Enc::jsdate('1988-05-07'), 'new Date(1988, 5 - 1, 7)');
        $this->assertEquals(Enc::jsdate('1988-05-07', 'mysql'), 'new Date(1988, 5 - 1, 7)');
        $this->assertNull(Enc::jsdate(array(7,5,1988), 'mysql'));

        // the array path through the helper
        $this->assertEquals(Enc::jsdate(array(7,5,1988), 'array'), 'new Date(1988, 5 - 1, 7)');
        $this->assertNull(Enc::jsdate('1988-05-07', 'array'));

        // Something else
        $this->assertNull(Enc::jsdate('goog', 'goog'));

        // 2-digit years
        $this->assertEquals(Enc::jsdate('88-05-07', 'mysql'), 'new Date(1988, 5 - 1, 7)');
        $this->assertEquals(Enc::jsdate(array(7,5,88), 'array'), 'new Date(1988, 5 - 1, 7)');
        $this->assertEquals(Enc::jsdate('20-05-07', 'mysql'), 'new Date(2020, 5 - 1, 7)');
        $this->assertEquals(Enc::jsdate(array(7,5,20), 'array'), 'new Date(2020, 5 - 1, 7)');
        $this->assertEquals(Enc::jsdate('49-05-07', 'mysql'), 'new Date(2049, 5 - 1, 7)');
        $this->assertEquals(Enc::jsdate(array(7,5,49), 'array'), 'new Date(2049, 5 - 1, 7)');
        $this->assertEquals(Enc::jsdate('50-05-07', 'mysql'), 'new Date(1950, 5 - 1, 7)');
        $this->assertEquals(Enc::jsdate(array(7,5,50), 'array'), 'new Date(1950, 5 - 1, 7)');
    }
}
