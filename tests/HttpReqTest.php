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
use Sprout\Helpers\HttpReq;


class MyHttpReq extends HttpReq
{
    public static function reqFopen($url, array $opts, $data = null)
    {
        return parent::reqFopen($url, $opts, $data);
    }

    public static function buildHeadersString($headers)
    {
        return parent::buildHeadersString($headers);
    }
}


class HttpReqTest extends TestCase
{

    /**
    * Seriously, if Google has SSL issues then someone isn't doing their job properly
    **/
    public function testSSLVerificationOkay()
    {
        try {
            HttpReq::get('https://www.google.com/');
        } catch (Exception $ex) {
            $this->assertNotContains('SSL', $ex->getMessage());
        }
        $this->assertTrue(true);
    }

    public function testTimeout()
    {
        $ok = false;

        try {
            MyHttpReq::req('http://0.0.255.255', [
                'timeout' => 1,
            ]);
        } catch (Exception $ex) {
            $this->assertStringContainsString('timed out', $ex->getMessage());
            $ok = true;
        }

        $this->assertTrue($ok);
    }

    public function testTimeoutFopen()
    {
        $start = microtime(true);
        $res = MyHttpReq::reqFopen('http://0.0.255.255', [
            'method' => 'GET',
            'timeout' => 1,
        ]);

        $duration = microtime(true) - $start;

        // It's enough that it didn't just get stuck on the request.
        // We rarely see PHP built without curl so we don't need to rely on
        // fopen API compatible.
        $this->assertEquals(false, $res);
        $this->assertGreaterThanOrEqual(1, $duration);

    }

    public function dataBuildHeadersString()
    {
        return [
            ['',''],
            [[],''],
            ['Test', 'Test'],

            [
                ['Content-type: text/plain'],
                "Content-type: text/plain"
            ],
            [
                ['Content-type: text/plain', 'Content-length: 100'],
                "Content-type: text/plain\r\nContent-length: 100"
            ],
            [
                ['Content-type' => 'text/plain', 'Content-length' => 100],
                "Content-type: text/plain\r\nContent-length: 100"
            ],
            [
                ['X-Something-Weird' => 'test'],
                "X-Something-Weird: test"
            ],
        ];
    }

    /**
    * @dataProvider dataBuildHeadersString
    **/
    public function testBuildHeadersString($input, $expected)
    {
        $this->assertEquals($expected, MyHttpReq::buildHeadersString($input));
    }



    public function dataBuildHeadersStringInvalid()
    {
        $bad = [
            '',
            "\r\n", "\n\r", "\r", "\n",
            "\t", "\v", "\e", "\f",
            "\0",
            "\x01",
        ];

        $out = [];
        foreach ($bad as $b) {
            $out[] = [['X-Something' => $b]];
        }
        foreach ($bad as $b) {
            $out[] = [[$b => 'Test']];
        }
        foreach ($bad as $b) {
            foreach ($bad as $c) {
                $out[] = [[$b => $c]];
            }
        }
        return $out;
    }

    /**
    * @dataProvider dataBuildHeadersStringInvalid
    * @expectedException InvalidArgumentException
    **/
    public function testBuildHeadersStringInvalid($input)
    {
        MyHttpReq::buildHeadersString($input);
    }

}
