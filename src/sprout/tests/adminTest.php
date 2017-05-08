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

use Sprout\Helpers\Admin;


class adminTest extends PHPUnit_Framework_TestCase
{

    private static function bufferedToggleStrip($field, $data, $selected)
    {
        ob_start();
        Admin::toggleStrip($field, $data, $selected);
        return ob_get_clean();
    }

    public function testToggleStripXssFieldName()
    {
        $html = self::bufferedToggleStrip('<XSS>', ['legit' => 'legit'], 'legit');
        $this->assertNotContains('<XSS>', $html);
    }

    public function testToggleStripXssId()
    {
        $html = self::bufferedToggleStrip('legit', ['<XSS>' => 'legit'], 'legit');
        $this->assertNotContains('<XSS>', $html);
    }

    public function testToggleStripXssValue()
    {
        $html = self::bufferedToggleStrip('legit', ['legit' => '<XSS>'], 'legit');
        $this->assertNotContains('<XSS>', $html);
    }

    public function testToggleStripXssSelection()
    {
        $html = self::bufferedToggleStrip('legit', ['legit' => 'legit'], '<XSS>');
        $this->assertNotContains('<XSS>', $html);
    }

}
