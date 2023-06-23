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
use Sprout\Helpers\Validity;
use Sprout\Exceptions\ValidationException;


class ValidityTest extends TestCase
{

    public static function dataEmail() {
        return [
            ['aa', false],
            ['test@example.com', true],
            ['@example.com', false],
            ['test@', false],
        ];
    }

    /**
    * @dataProvider dataEmail
    */
    public function testEmail($value, $success)
    {
        try {
            Validity::email($value);
            if (!$success) $this->assertFalse(true);
        } catch (ValidationException $ex) {
            if ($success) $this->assertFalse(true);
        }
    }



    public static function dataPositiveInt() {
        return [
            ['0', false],
            ['1', true],
            ['1000', true],
            ['-1', false],
            ['test', false],
        ];
    }

    /**
    * @dataProvider dataPositiveInt
    */
    public function testPositiveInt($value, $success)
    {
        try {
            Validity::positiveInt($value);
            if (!$success) $this->assertFalse(true);
        } catch (ValidationException $ex) {
            if ($success) $this->assertFalse(true);
        }
    }



    public static function dataBinary() {
        return [
            ['0', true],
            ['1', true],
            [0, true],
            [1, true],
            // Anything else is invalid
            ["\0", false],
            ['01', false],
            [array(), false],
            ['1000', false],
            ['-1', false],
            ['test', false],
            [true, false],
            [false, false],
            ['false', false],
            ['true', false],
        ];
    }

    /**
    * @dataProvider dataBinary
    */
    public function testBinary($value, $success)
    {
        try {
            Validity::binary($value);
            if (!$success) $this->assertFalse(true);
        } catch (ValidationException $ex) {
            if ($success) $this->assertFalse(true);
        }
    }



    public static function dataDateMySQL() {
        return [
            ['0000-00-00', false],
            ['1980-01-01', true],
            ['1970-01-01', true],
            ['1900-01-01', true],
            ['2100-01-01', true],
            ['', false],
            ['1899-01-01', false],
            ['2101-01-01', false],
            ['1970-00-01', false],
            ['1970-13-01', false],
            ['1970-01-00', false],
            ['1970-01-32', false],
        ];
    }

    /**
    * @dataProvider dataDateMySQL
    */
    public function testDateMySQL($value, $success)
    {
        try {
            Validity::dateMySQL($value);
            if (!$success) $this->assertFalse(true);
        } catch (ValidationException $ex) {
            if ($success) $this->assertFalse(true);
        }
    }
}
