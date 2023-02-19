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
use Sprout\Helpers\Validator;


class ValidatorTest extends TestCase
{

    public function dataCheckFailures() {
        return [
            ['aa', 'Validity::email'],
            ['@example.com', 'Validity::email'],
            ['test@', 'Validity::email'],
            ['A', 'Validity::length', 2],
            ['A', 'Validity::length', 2, 3],
            ['A', 'Validity::positiveInt'],
        ];
    }

    /**
    * @dataProvider dataCheckFailures
    */
    public function testCheckFailures($value, $func)
    {
        $validator = new Validator(['field' => $value]);

        // Awful hack to pass through varargs to the Validator::check method
        $args = func_get_args();
        $args[0] = 'field';
        call_user_func_array([$validator, 'check'], $args);

        $this->assertTrue($validator->hasErrors());
        $this->assertCount(1, $validator->getFieldErrors());
        $this->assertArrayHasKey('field', $validator->getFieldErrors());
        $errs = $validator->getFieldErrors();
        $this->assertCount(1, $errs['field']);
    }

    public function testArrayCheck()
    {
        $data = ['vals' => [1, 2, 'A', 'B', 5]];
        $validator = new Validator($data);

        $results = $validator->arrayCheck('vals', 'Validity::positiveInt');

        $this->assertCount(count($data['vals']), $results);
        $this->assertTrue($results[0]);
        $this->assertTrue($results[1]);
        $this->assertFalse($results[2]);
        $this->assertFalse($results[3]);
        $this->assertTrue($results[4]);

        $this->assertTrue($validator->hasErrors());
        $this->assertCount(1, $validator->getFieldErrors());
        $this->assertArrayHasKey('vals', $validator->getFieldErrors());

        $errs = $validator->getFieldErrors();
        $this->assertCount(2, $errs['vals']);
        $this->assertArrayHasKey(2, $errs['vals']);
        $this->assertCount(1, $errs['vals'][2]);
        $this->assertArrayHasKey(3, $errs['vals']);
        $this->assertCount(1, $errs['vals'][3]);
    }

}
