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
use Sprout\Helpers\I18n;

class I18nTest extends TestCase
{

    public static function dataNumber()
    {
        return array(
            array('AUS', 1234.123, 0, '1,234'),
            array('AUS', 1234.123, 1, '1,234.1'),
            array('AUS', 1234.123, 2, '1,234.12'),
            array('AUS', -1234.123, 1, '-1,234.1'),

            // Other countries which format numbers the same as AUS
            array('GBR', 1234.123, 2, '1,234.12'),
            array('JPN', 1234.123, 2, '1,234.12'),
            array('USD', 1234.123, 2, '1,234.12'),

            // Canada uses spaces instead of commas (in English)
            array('CAN', 1234.123, 2, '1 234.12'),

            // Numbering in india is ... odd.
            array('IND', 2, 0, '2'),
            array('IND', 12, 0, '12'),
            array('IND', 212, 0, '212'),
            array('IND', 1212, 0, '1,212'),
            array('IND', 21212, 0, '21,212'),
            array('IND', 121212, 0, '1,21,212'),
            array('IND', 2121212, 0, '21,21,212'),
            array('IND', 12121212, 0, '1,21,21,212'),
            array('IND', 212121212, 0, '21,21,21,212'),
            array('IND', 121212.1, 1, '1,21,212.1'),
            array('IND', 2121212.1, 1, '21,21,212.1'),
            array('IND', 12121212.1, 1, '1,21,21,212.1'),
            array('IND', 212121212.1, 1, '21,21,21,212.1'),
        );
    }

    public static function dataMoney()
    {
        return array(
            array('AUS', 1234.123, '$1,234.12'),
            array('AUS', -1234.123, '-$1,234.12'),

            array('JPN', 1234.123, '¥1,234'),
            array('JPN', -1234.123, '-¥1,234'),

            array('IND', 1234.123, 'Rs.1,234'),
            array('IND', -1234.123, '-Rs.1,234'),
            array('IND', -121234.123, '-Rs.1,21,234'),
        );
    }



    /**
    * @dataProvider dataNumber
    **/
    public function testNumber($country, $number, $precision, $expected)
    {
        I18n::setLocale($country);
        $got = I18n::number($number, $precision);
        $this->assertEquals($expected, $got);
    }

    /**
    * @dataProvider dataMoney
    **/
    public function testMoney($country, $number, $expected)
    {
        I18n::setLocale($country);
        $got = I18n::money($number);
        $this->assertEquals($expected, $got);
    }



    public function testDates()
    {
        $this->assertTrue(I18n::shortdate(strtotime('2012-01-01')) == '1/1/2012');
        $this->assertTrue(I18n::longdate(strtotime('2012-01-01')) == 'Sun 1st Jan 2012');
        $this->assertTrue(I18n::time(strtotime('10:00:00')) == '10:00am');
    }

}
