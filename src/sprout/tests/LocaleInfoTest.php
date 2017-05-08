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

use Sprout\Helpers\Locales\LocaleInfo;


class LocaleInfoTest extends PHPUnit_Framework_TestCase
{

    public function dataGetStateName()
    {
        return [
            ['AUS', 'SA', 'South Australia'],    // Abbreviations
            ['AND', 'Canillo', 'Canillo'],       // Numeric
            ['JPN', 'Aomori-ken', 'Aomori'],     // Optgroups

            // No states/provinces; always null
            ['VAT', '', null],
            ['VAT', null, null],
            ['VAT', 1, null],
            ['VAT', 'AAA', null],
        ];
    }

    /**
    * @dataProvider dataGetStateName
    **/
    public function testGetStateName($country, $state, $expected)
    {
        $locale = LocaleInfo::get($country);
        $this->assertEquals($expected, $locale->getStateName($state));
    }

}
