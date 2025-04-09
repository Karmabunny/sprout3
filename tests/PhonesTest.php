<?php
/*
 * Copyright (C) 2025 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

use karmabunny\kb\ValidationException;
use libphonenumber\PhoneNumberType;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberFormat;
use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Phones;
use libphonenumber\PhoneNumberUtil;

/**
 * Test suite for the Phones helper class
 */
class PhonesTest extends TestCase
{

    /**
     * Test country code list. Will auto-populate if first run
     *
     * @return void
     */
    public function testCompoundFormField()
    {
        $html = Phones::compoundFormField(true, 'Phone (mobile)', ['phone', 'phone_code']);
        $this->assertStringContainsString('Phone (mobile)', $html);
        $this->assertStringContainsString('+61', $html);
        $this->assertStringContainsString('"61"', $html);
        $this->assertStringContainsString('+64', $html);
        $this->assertStringContainsString('"64"', $html);
        $this->assertStringContainsString('+44', $html);
        $this->assertStringContainsString('"44"', $html);
    }

    /**
     * Test country code list. Will auto-populate if first run
     *
     * @return void
     */
    public function testCompoundFormFieldWithCustomFieldNames()
    {
        $html = Phones::compoundFormField(true, 'Phone (mobile)', ['phone_number_jumper', 'boogie_woogie']);
        $this->assertStringContainsString('class="dropdown" name="boogie_woogie"', $html);
        $this->assertStringContainsString('name="phone_number_jumper"', $html);
        $this->assertStringContainsString('name="boogie_woogie"', $html);
    }


    public function dataProviderCleanPhoneNumber()
    {
        return [
            'leading code' => ['+61491570156', '+61', '491570156'],
            'leading 0' => ['0491570156', '+61', '491570156'],
            'no leading 0' => ['491570156', '+61', '491570156'],
            'leading code 2' => ['+642102468429', '+64', '2102468429'],
            'leading 0 2' => ['02102468429', '+64', '2102468429'],
            'no leading 0 2' => ['2102468429', '+64', '2102468429'],
            'leading double 0' => ['0061491570156', '+61', '491570156'],
            'leading double 0 2' => ['00642102468429', '+64', '2102468429'],
            'with spaces' => ['0491 570 156', '+61', '491570156'],
            'with dashes' => ['0491-570-156', '+61', '491570156'],
            'with parentheses' => ['(0491) 570 156', '+61', '491570156'],
            'with country code no plus' => ['61491570156', '+61', '491570156'],
            'with mixed formatting' => ['+61 (0) 491-570-156', '+61', '491570156'],
            'with letters' => ['0491ABC570156', '+61', '491570156'],
            'with phone code without plus' => ['61491570156', '61', '491570156'],
            'with international format and spaces' => ['+61 491 570 156', '+61', '491570156'],
            'with triple zero prefix' => ['000491570156', '+61', '491570156'],
            'empty string' => ['', '+61', ''],
            'non-numeric only' => ['(+*&)', '+61', ''],
        ];
    }


    /**
     * @dataProvider dataProviderCleanPhoneNumber
     */
    public function testCleanPhoneNumber(string $dirty, string $phone_code, string $clean)
    {
        $this->assertEquals($clean, Phones::cleanStripCountryCode($dirty, $phone_code));
    }


    /**
     * @dataProvider dataProviderCleanPhoneNumber
     */
    public function testPhoneNumberWithCountryCodeString(string $dirty, string $phone_code, string $clean)
    {
        $this->assertEquals("{$phone_code}{$clean}", Phones::numberWithCountryCode($dirty, $phone_code));
    }


    public function dataProviderCompareCleanNumbers()
    {
        return [
            'same number' => ['+61491570156', '+61491570156', true],
            'same number different code' => ['+61491570156', '+64491570156', false],
            'different number same code' => ['+61491570156', '+61491570157', false],
            'same number no code' => ['0491570156', '0491570156', true],
            'same number no code 2' => ['491570156', '491570156', true],
            'formatted vs unformatted' => ['+61 491 570 156', '+61491570156', true],
            'with dashes' => ['+61-491-570-156', '+61491570156', true],
            'with parentheses' => ['+61 (0) 491 570 156', '+61491570156', true],
            'with letters' => ['+61491ABC570156', '+61491570156', true],
            'empty strings' => ['', '', true],
            'non-numeric only' => ['(+*&)', '', true],
            'leading zero vs country code' => ['0491570156', '+61491570156', true],
            'different formatting but same number' => ['(04) 9157-0156', '0491 570 156', true],
            'spaces vs no spaces' => ['0491 570 156', '0491570156', true],
            'double zero prefix vs country code' => ['0061491570156', '+61491570156', true],
        ];
    }


    /**
     * @dataProvider dataProviderCompareCleanNumbers
     */
    public function testCompareCleanNumbers(string $number_1, string $number_2, bool $expected)
    {
        $this->assertEquals($expected, Phones::compareNumbers($number_1, $number_2));
    }


    public function dataProviderCompareCleanNumbersWithCodeString()
    {
        return [
            'same number' => ['+61491570156', '+61', '+61491570156', '+61', true],
            'same number different code' => ['+61491570156', '+61', '+64491570156', '+64', false],
            'different number same code' => ['+61491570156', '+61', '+61491570157', '+61', false],
            'same number with leading zero' => ['0491570156', '+61', '+61491570156', '+61', true],
            'same number with spaces and formatting' => ['+61 491 570 156', '+61', '+61491570156', '+61', true],
            'same number with dashes' => ['+61-491-570-156', '+61', '+61491570156', '+61', true],
            'same number with parentheses' => ['+61 (0) 491 570 156', '+61', '+61491570156', '+61', true],
            'same number with letters' => ['+61491ABC570156', '+61', '+61491570156', '+61', true],
            'empty strings' => ['', '+61', '', '+61', true],
            'non-numeric only' => ['(+*&)', '+61', '', '+61', true],
            'different codes but same number after cleaning' => ['0491570156', '+61', '0491570156', '+64', false],
            'different formatting but same number' => ['(04) 9157-0156', '+61', '0491 570 156', '+61', true],
        ];
    }


    /**
     * @dataProvider dataProviderCompareCleanNumbersWithCodeString
     */
    public function testCompareCleanNumbersWithCodeString(string $number_1, string $code_1, string $number_2, string $code_2, bool $expected)
    {
        $this->assertEquals($expected, Phones::compareWithCode($number_1, $code_1, $number_2, $code_2));
    }


    public function dataProviderCompareCleanNumbersWithCountryCodeId()
    {
        return [
            'same number' => ['+61491570156', 'AUS', '+61491570156', 'AUS', true],
            'same number different code' => ['+61491570156', 'AUS', '+64491570156', 'NZL', false],
            'different number same code' => ['+61491570156', 'AUS', '+61491570157', 'AUS', false],
            'same number with leading zero' => ['0491570156', 'AUS', '+61491570156', 'AUS', true],
            'same number with spaces and formatting' => ['+61 491 570 156', 'AUS', '+61491570156', 'AUS', true],
            'same number with dashes' => ['+61-491-570-156', 'AUS', '+61491570156', 'AUS', true],
            'same number with parentheses' => ['+61 (0) 491 570 156', 'AUS', '+61491570156', 'AUS', true],
            'same number with letters' => ['+61491ABC570156', 'AUS', '+61491570156', 'AUS', true],
            'empty strings' => ['', 'AUS', '', 'AUS', true],
            'non-numeric only' => ['(+*&)', 'AUS', '', 'AUS', true],
        ];
    }

    public function dataProviderParsePhoneNumber()
    {
        $lib = PhoneNumberUtil::getInstance();

        return [
            'valid Australian mobile' => [
                $lib,
                '+61491570156',
                'AU',
                true,
                PhoneNumberType::MOBILE
            ],
            'valid Australian landline' => [
                $lib,
                '+61298765432',
                'AU',
                true,
                PhoneNumberType::FIXED_LINE
            ],
            'valid New Zealand mobile' => [
                $lib,
                '+642102468429',
                'NZ',
                true,
                PhoneNumberType::MOBILE
            ],
            'invalid number' => [
                $lib,
                '123',
                'AU',
                false,
                NumberParseException::NOT_A_NUMBER
            ],
            'invalid country code' => [
                $lib,
                '+61491570156',
                'XX',
                false,
                NumberParseException::INVALID_COUNTRY_CODE
            ],
            'valid with spaces' => [
                $lib,
                '+61 491 570 156',
                'AU',
                true,
                PhoneNumberType::MOBILE
            ],
            'valid with parentheses' => [
                $lib,
                '(0491) 570 156',
                'AU',
                true,
                PhoneNumberType::MOBILE
            ],
        ];
    }

    /**
     * @dataProvider dataProviderParsePhoneNumber
     */
    public function testParsePhoneNumber(PhoneNumberUtil $lib, string $number, string $country, bool $isValid, ?int $type)
    {
        if ($isValid) {
            $parsed = Phones::parse($lib, $number, $country);
            $parsedType = $lib->getNumberType($parsed);
            $this->assertEquals($type, $parsedType);
        } else {
            $this->expectException(NumberParseException::class);
            $this->expectExceptionCode($type);
            Phones::parse($lib, $number, $country);
        }
    }

    /**
     * @dataProvider dataProviderParsePhoneNumberOutput
     */
    public function testParsePhoneNumberOutput(
        PhoneNumberUtil $lib,
        string $number,
        string $country,
        int $countryCode,
        string $nationalNumber,
        int $type
    ) {
        $parsed = Phones::parse($lib, $number, $country);
        
        $this->assertEquals($countryCode, $parsed->getCountryCode());
        $this->assertEquals($nationalNumber, $parsed->getNationalNumber());
        $this->assertEquals($type, $lib->getNumberType($parsed));
    }

    public function dataProviderParsePhoneNumberOutput()
    {
        $lib = PhoneNumberUtil::getInstance();
        
        return [
            'australian mobile' => [
                $lib,
                '+61491570156',
                'AU',
                61, // country code
                '491570156', // national number
                PhoneNumberType::MOBILE
            ],
            'australian landline' => [
                $lib,
                '+61298765432',
                'AU',
                61, // country code
                '298765432', // national number
                PhoneNumberType::FIXED_LINE
            ],
            'new zealand mobile' => [
                $lib,
                '+642102468429',
                'NZ',
                64, // country code
                '2102468429', // national number
                PhoneNumberType::MOBILE
            ],
            'australian mobile with spaces' => [
                $lib,
                '+61 491 570 156',
                'AU',
                61, // country code
                '491570156', // national number
                PhoneNumberType::MOBILE
            ],
            'australian mobile with parentheses' => [
                $lib,
                '(0491) 570 156',
                'AU',
                61, // country code
                '491570156', // national number
                PhoneNumberType::MOBILE
            ],
        ];
    }

	public function dataNumbers()
	{
		return [
			// mobile numbers
			['0422 333 444', '+61422333444', 'MOBILE', null],
			['04 2233 3444', '+61422333444', 'MOBILE', null],
			['+61 422 333 444', '+61422333444', 'MOBILE', 'AU'],

			// landline numbers
			['08 7120 7100', '+61871207100', 'FIXED_LINE', null],
			['(08) 7120 7100', '+61871207100', 'FIXED_LINE', null],
			['+61 8 7120 7100', '+61871207100', 'FIXED_LINE', 'AU'],
			['322 333 444', '+61322333444', 'FIXED_LINE', null],

			// kiwi numbers
			['+64 3 231 2344', '+6432312344', 'FIXED_LINE', 'NZ'],
			['+64 21 123 4567', '+64211234567', 'MOBILE', 'NZ'],

			// broken country codes
			['+99 123 123 123', null, null, null],
		];
	}


	/**
	* @dataProvider dataNumbers
	**/
	public function testNumbers($test, $expected, $type, $country)
	{
		if ($type === null) {
			$this->expectException(NumberParseException::class);
			$number = Phones::format($test);
		} else {
			$actual = Phones::format($test);
			$this->assertEquals($expected, $actual);

			$actual = Phones::getNumberType($test);
			$this->assertEquals($type, $actual);

			$actual = Phones::lookupCountry($test);
			$this->assertEquals($country, $actual);
		}
	}


	public function dataFormat()
	{
		return [
			// intl E.164
			['0871207100', '+61871207100', PhoneNumberFormat::E164],
			['0422333444', '+61422333444', PhoneNumberFormat::E164],
			['1800555555', '+611800555555', PhoneNumberFormat::E164],

			// localised
			['0871207100', '(08) 7120 7100', PhoneNumberFormat::NATIONAL],
			['0422333444', '0422 333 444', PhoneNumberFormat::NATIONAL],
			['1800555555', '1800 555 555', PhoneNumberFormat::NATIONAL],
			// ['1258881', '1258881', PhoneNumberFormat::NATIONAL],
		];
	}


	/**
	 * @dataProvider dataFormat
	 */
	public function testFormat($number, $expected, $type)
	{
		$actual = Phones::format($number, $type);
		$this->assertEquals($expected, $actual);
	}


	public function dataDirty()
	{
		return [
			[' 	‪+972 54‑396‑6815‬', '+972543966815'],
		];
	}


	/**
	 * @dataProvider dataDirty
	 */
	public function testDirty($number, $expected)
	{
		$actual = Phones::cleanNumber($number);
		$this->assertEquals($expected, $actual);
	}


    public function dataValidate()
    {
        return [
            // Valid Australian numbers
            ['0422333444', true, 'AU'], // Valid mobile
            ['0871207100', true, 'AU'], // Valid landline
            ['1800555555', true, 'AU'], // Valid toll-free

            // Valid international numbers
            ['+61422333444', true, 'AU'], // Valid mobile with country code
            ['+61871207100', true, 'AU'], // Valid landline with country code

            // Numbers that will clean up OK
            ['0422-333-444', true, null], // Contains dashes
            ['(08) 7120 7100', true, null], // Contains spaces and parentheses

            // Invalid for AU, but not invalid globally
            ['042233344', false, null],
            ['087120710', false, null],

            // Invalid numbers
            ['123', false, null], // Too short
            ['042233344', false, 'AU'], // Invalid mobile for AU (wrong length)
            ['087120710', false, 'AU'], // Invalid landline for AU (wrong length)

            // Invalid formats
            ['abc123', false, null], // Contains letters

            // Invalid country code
            ['+1234567890', false, 'XX'], // Invalid country code

            // Empty string
            ['', true, null], // Should be valid (empty strings are allowed)
        ];
    }


    /**
     * @dataProvider dataValidate
     */
    public function testValidate($number, bool $is_valid, ?string $country)
    {
        if (!$is_valid) {
            $this->expectException(ValidationException::class);
        }

        Phones::validate($number, $country);

        $this->assertTrue(true);
    }
}
