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
use Sprout\Helpers\Category;



/**
* Test suite
**/
class CategoryTest extends TestCase
{

    public function dataInvalidTableValidation()
    {
        return [
            ['1'],
            ['1.1'],
            ['aaa.aaa'],
            [''],
            [null],
            [1],
            [false],
            [true],
        ];
    }

    /**
    * @dataProvider dataInvalidTableValidation
    * @expectedException InvalidArgumentException
    **/
    public function testTableMain2catValidationInvalid($val)
    {
        Category::tableMain2cat($val);
    }

    /**
    * @dataProvider dataInvalidTableValidation
    * @expectedException InvalidArgumentException
    **/
    public function testTableMain2joinerValidationInvalid($val)
    {
        Category::tableMain2joiner($val);
    }

    /**
    * @dataProvider dataInvalidTableValidation
    * @expectedException InvalidArgumentException
    **/
    public function testTableCat2mainValidationInvalid($val)
    {
        Category::tableCat2main($val);
    }



    public function dataValidTableValidation()
    {
        return [
            ['pages'],
            ['articles'],
        ];
    }

    /**
    * @dataProvider dataValidTableValidation
    **/
    public function testTableMain2catValidationValid($val)
    {
        $this->assertInternalType('string', Category::tableMain2cat($val));
    }

    /**
    * @dataProvider dataValidTableValidation
    **/
    public function testTableMain2joinerValidationValid($val)
    {
        $this->assertInternalType('string', Category::tableMain2joiner($val));
    }

    /**
    * @dataProvider dataValidTableValidation
    **/
    public function testTableCat2mainValidationValid($val)
    {
        $this->assertInternalType('string', Category::tableCat2main($val));
    }

}
