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
use Sprout\Helpers\Security;


/**
* Test suite
**/
class SecurityHelperTest extends TestCase
{

    public function testRandBytes()
    {
        $bytes = Security::randBytes(16);
        $this->assertTrue(strlen($bytes) === 16, 'Return value length');
    }

    public function testRandByte()
    {
        $byte = Security::randByte();
        $this->assertTrue(strlen($byte) === 1, 'Return value length');
    }

    public function testRandStr()
    {
        $string = Security::randStr(16);
        $this->assertTrue(strlen($string) === 16, 'Return value length');
    }


    /**
     * Data for testing random distributions
     */
    public static function dataRandDistribution()
    {
        return [
            [
                'Sprout\Helpers\Security::randBytes',
                [4096 * 512],
                256
            ],
            [
                'Sprout\Helpers\Security::randStr',
                [4096 * 512, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'],
                26
            ],
            [
                'Sprout\Helpers\Security::randStr',
                [4096 * 512, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'],
                52
            ],
            [
                'Sprout\Helpers\Security::randStr',
                [4096 * 512, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890'],
                62
            ],
        ];
    }

    /**
     * @dataProvider dataRandDistribution
     *
     * @param callable $func Function to generate random strings
     * @param array $args Function arguments
     * @param int $num_unique Expected number of unique values returned from $func
     */
    public function testRandDistribution($func, array $args, $num_unique)
    {
        $bytes = call_user_func_array($func, $args);
        $bytes = str_split($bytes);

        $dists = [];
        foreach ($bytes as $b) {
            $b = ord($b);
            if (isset($dists[$b])) {
                $dists[$b]++;
            } else {
                $dists[$b] = 1;
            }
        }
        $this->assertCount($num_unique, $dists);

        $avg = count($bytes) / $num_unique;
        $thresh = $avg * 0.1;
        foreach ($dists as $b => $count) {
            $diff = abs($count - $avg);
            $this->assertLessThan($thresh, $diff, "Byte {$b} count {$count} expected {$avg} (+/- {$thresh})");
        }
    }


    public function testCompareStrings()
    {
        $this->assertTrue(Security::compareStrings('aaa', 'aaa'));
        $this->assertFalse(Security::compareStrings('aaa', 'bbb'));
    }

    public function testCompareStringsTimingSafe()
    {
        if (getenv('TRAVIS'))  {
            $this->markTestSkipped('Timing not stable in Travis CI');
        }

        $xxx = str_repeat('x', 1024 * 32);
        $yyy = str_repeat('x', 1024 * 32 - 1) . 'y';
        $zzz = 'z' . str_repeat('x', 1024 * 32 - 1);
        $matches = [0.0, 0.0, 0.0];

        // When using hash_equals its much faster than the fallback
        // and this makes the timing unstable so more iterations are required
        if (function_exists('hash_equals')) {
            $iter = 5000;
        } else {
            $iter = 500;
        }

        // Test one - both strings matching
        for ($i = 0; $i < $iter; ++$i) {
            $start = microtime(true);
            Security::compareStrings($xxx, $xxx);
            $matches[0] += (microtime(true) - $start) * 1000;
        }

        // Test two - matching except last character
        for ($i = 0; $i < $iter; ++$i) {
            $start = microtime(true);
            Security::compareStrings($xxx, $yyy);
            $matches[1] += (microtime(true) - $start) * 1000;
        }

        // Test three - matching except first character
        for ($i = 0; $i < $iter; ++$i) {
            $start = microtime(true);
            Security::compareStrings($xxx, $zzz);
            $matches[2] += (microtime(true) - $start) * 1000;
        }

        // Calculate the average time across all three tests
        $average = array_sum($matches) / count($matches);

        // Compare each test against the average, as a percentage
        // Require to be within 10% or better
        foreach ($matches as $idx => $val) {
            $diff = abs($val - $average);
            $perc = $diff / $average * 100.0;
            $this->assertLessThan(10, $perc);
        }
    }


    public static function dataPasswordComplexityLength()
    {
        return [
            ['abcdefg', 8, 'Too short, minimum length 8 characters'],
            ['abcdefgh', 8, null],
            ['abcdefghi', 8, null],
            ['abcdefghi', 10, 'Too short, minimum length 10 characters'],
            ['abcdefghij', 10, null],
            ['abcdefghijk', 10, null],
        ];
    }

    /**
     * @dataProvider dataPasswordComplexityLength
     */
    public function testPasswordComplexityLength($string, $length, $errmsg)
    {
        $errs = Security::passwordComplexity($string, $length, 0, false);
        $this->assertEquals($errmsg?[$errmsg]:[], $errs);
    }


    public static function dataPasswordComplexityClasses()
    {
        return [
            ['password', 1, null],
            ['password', 2, 'Need 2 character types (lowercase, uppercase, numbers, symbols)'],
            ['password', 3, 'Need 3 character types (lowercase, uppercase, numbers, symbols)'],
            ['password', 4, 'Need 4 character types (lowercase, uppercase, numbers, symbols)'],
            ['passWORD', 1, null],
            ['passWORD', 2, null],
            ['passWORD', 3, 'Need 3 character types (lowercase, uppercase, numbers, symbols)'],
            ['passWORD', 4, 'Need 4 character types (lowercase, uppercase, numbers, symbols)'],
            ['passW0RD', 1, null],
            ['passW0RD', 2, null],
            ['passW0RD', 3, null],
            ['passW0RD', 4, 'Need 4 character types (lowercase, uppercase, numbers, symbols)'],
            ['pa!sW0RD', 1, null],
            ['pa!sW0RD', 2, null],
            ['pa!sW0RD', 3, null],
            ['pa!sW0RD', 4, null],
        ];
    }

    /**
     * @dataProvider dataPasswordComplexityClasses
     */
    public function testPasswordComplexityClasses($string, $classes, $errmsg)
    {
        $errs = Security::passwordComplexity($string, 0, $classes, false);
        $this->assertEquals($errmsg?[$errmsg]:[], $errs);
    }


    public static function dataPasswordComplexityBadlist()
    {
        return [
            ['password', 'Matches a very common password'],
            ['dsbfb83s', null],
        ];
    }

    /**
     * @dataProvider dataPasswordComplexityBadlist
     */
    public function testPasswordComplexityBadlist($string, $errmsg)
    {
        $errs = Security::passwordComplexity($string, 0, 0, true);
        $this->assertEquals($errmsg?[$errmsg]:[], $errs);
    }


    public static function dataKeySign()
    {
        return [
            ['583e69759d930699493a0b7828aed9d957b8ffe7', ['abc' => 'DEF', 'ghi' => 123]],

            // out of order (same)
            ['583e69759d930699493a0b7828aed9d957b8ffe7', ['ghi' => 123, 'abc' => 'DEF']],

            // swapped keys
            ['7c3df8577308800d36f975086130f803224abadc', ['ghi' => 'DEF', 'abc' => 123]],

            // different keys
            ['c832c7da6994d50c20e02d235eec0d0dd67d3181', ['wtf' => 'DEF', 'ghi' => 123]],

            // lowercase
            ['67453d5afbe7be763eb9353ff4f949e0759837a6', ['abc' => 'def', 'ghi' => 123]],

            // no keys
            ['80d01ed1edbcfe0b29f6ab072d1c0c5451d0e3da', ['DEF', 123]],

            // original test
            ['50a9461490976af56edb047ab6af9acba22d0474', ['def', 123]],

            // same again
            ['50a9461490976af56edb047ab6af9acba22d0474', [123, 'def']],
        ];
    }


    /**
     * @dataProvider dataKeySign
     */
    public function testKeySign($expected, $actual)
    {
        $actual = Security::serverKeySign($actual);
        $this->assertTrue(hash_equals($expected, $actual), $actual);
    }

}
