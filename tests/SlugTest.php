<?php

use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Slug;
use Sprout\Exceptions\ValidationException;

class SlugTest extends TestCase
{
    public static function dataValid()
    {
        return [
            ['a'],
            ['0'],
            ['-'],
            ['test'],
            ['test-test'],
            ['test--test'],
            ['01234'],
            ['slug-09-test'],
            ['9876-5432-1'],
            ['long-page-name-should-still-work-fine'],
            ['manywordsnohyphensisntaproblem'],
        ];
    }

    /**
     *
     * @dataProvider dataValid
     */
    public function testValid($value)
    {
        try {
            Slug::valid($value);
            $this->assertEquals(true, true);
        } catch (ValidationException $exp) {
            $this->assertEquals($value, 'Slug is valid but failed the validation test');
        }
    }

    public static function dataInvalid()
    {
        return [
            [''],

            // Capitalisation isn't permitted
            ['Test'],
            ['INVALID'],

            ['_test'],
            ['test_'],
            ['slug_slug'],

            // All these characters need to be rejected
            ['~'],
            ['!'],
            ['@'],
            ['#'],
            ['$'],
            ['%'],
            ['^'],
            ['&'],
            ['*'],
            ['('],
            [')'],
            ['_'],
            ['+'],
            ['='],
            ['{'],
            ['['],
            ['}'],
            [']'],
            [':'],
            [';'],
            ['"'],
            ['\''],
            ['<'],
            ['>'],
            [','],
            ['.'],
            ['|'],
            ['\\'],
            ['?'],
            ['/'],
        ];
    }

    /**
     *
     * @dataProvider dataInvalid
     * @expectedException Sprout\Exceptions\ValidationException
     */
    public function testInvalid($value)
    {
        Slug::valid($value);
    }
}
