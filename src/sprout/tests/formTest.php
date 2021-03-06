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

use Sprout\Helpers\Form;


class formTest extends PHPUnit_Framework_TestCase
{

    public function testText()
    {
        Form::nextFieldDetails('Bob', true);
        $html = Form::text('bob');

        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $xml = str_replace(['<html><body>', '</body></html>'], '', $doc->saveXML());

        $this->assertXmlStringEqualsXmlString(
            '<div class="field-element field-element--text field-element--required">
                <div class="field-label">
                    <label for="field0">Bob <span class="field-label__required">required</span></label>
                </div>
                <div class="field-input">
                    <input type="text" value="" name="bob" class="textbox" id="field0"/>
                </div>
            </div>',
            $xml
        );
    }

    public function testWrapperClass()
    {
        Form::nextFieldDetails('Bob', true);
        $field = Form::text('bob', ['-wrapper-class' => 'small']);
        $this->assertContains('field-element--small', $field);
    }

    public function testWrapperID()
    {
        Form::nextFieldDetails('Bob', true);
        $field = Form::text('bob', ['id' => 'first-name']);
        $this->assertContains('field-element--id-first-name', $field);
    }

    public function testDataShallow()
    {
        Form::setData(['aaa' => '**value**']);
        $field = Form::text('aaa');
        $this->assertContains('**value**', $field);
    }

    public function testDataShallowNameFormat()
    {
        Form::setData(['--aaa' => '**value**']);
        Form::setFieldNameFormat('--%s');
        $field = Form::text('aaa');
        $this->assertContains('**value**', $field);
        Form::setFieldNameFormat('%s');
    }

    public function testDataDeep()
    {
        Form::setData(['aaa' => ['bbb' => '**value**']]);
        $field = Form::text('aaa[bbb]');
        $this->assertContains('**value**', $field);
    }

    public function testDataDeepNameFormat()
    {
        Form::setData(['aaa' => ['bbb' => '**value**']]);
        Form::setFieldNameFormat('aaa[%s]');
        $field = Form::text('bbb');
        $this->assertContains('**value**', $field);
        Form::setFieldNameFormat('%s');
    }

    public function testErrorShallow()
    {
        Form::setErrors(['aaa' => '**error**']);
        $field = Form::text('aaa');
        $this->assertContains('**error**', $field);
    }

    public function testErrorShallowNameFormat()
    {
        Form::setErrors(['--aaa' => '**error**']);
        Form::setFieldNameFormat('--%s');
        $field = Form::text('aaa');
        $this->assertContains('**error**', $field);
        Form::setFieldNameFormat('%s');
    }

    public function testErrorDeep1()
    {
        Form::setErrors(['aaa' => ['bbb' => '**error**']]);
        $field = Form::text('aaa[bbb]');
        $this->assertContains('**error**', $field);
    }

    public function testErrorDeep2()
    {
        Form::setErrors(['aaa' => [5 => ['bbb' => '**error**']]]);
        $field = Form::text('aaa[5][bbb]');
        $this->assertContains('**error**', $field);
    }

    public function testErrorDeepNameFormat()
    {
        Form::setErrors(['aaa' => ['bbb' => '**error**']]);
        Form::setFieldNameFormat('aaa[%s]');
        $field = Form::text('bbb');
        $this->assertContains('**error**', $field);
        Form::setFieldNameFormat('%s');
    }
}
