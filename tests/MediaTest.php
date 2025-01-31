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

use karmabunny\pdb\Exceptions\ConnectionException;
use PHPUnit\Framework\TestCase;
use Sprout\Helpers\HttpReq;
use Sprout\Helpers\Media;
use Sprout\Helpers\Pdb;
use Sprout\SproutVisor;

class MediaTest extends TestCase
{

    /** @var SproutVisor */
    public static $server;


    public static function setUpBeforeClass(): void
    {
        try {
            Pdb::query("SELECT 1", [], 'null');
        } catch (ConnectionException $ex) {
            self::markTestSkipped('mysql is not available right now');
        }

        self::$server ??= SproutVisor::create([
            'webroot' => __DIR__ . '/web',
        ]);
    }


    public static function tearDownAfterClass(): void
    {
        if (self::$server) {
            self::$server->stop();
        }
    }


    public function setUp(): void
    {
        Media::clean('silent');
    }


    /** @dataProvider dataGroup */
    public function testGroup($group, $path)
    {
        $actual = Media::getGroup($path);
        $this->assertEquals($group, $actual);
    }


    /** @dataProvider dataUrls */
    public function testPath($section, $name, $generated, $path)
    {
        $actual = Media::path($name);
        $this->assertEquals($path, $actual);
    }


    /** @dataProvider dataUrls */
    public function testUrl($section, $name, $generated, $path)
    {
        $actual = Media::url($name);
        $this->assertEquals($generated, $actual);
    }


    /** @dataProvider dataUrls */
    public function testUrlNoGenerate($section, $name, $generated, $path)
    {
        $expected = "_media/{$section}/{$name}?" . @filemtime($path);
        $actual = Media::url($name, false);
        $this->assertEquals($expected, $actual);
    }


    /** @dataProvider dataUrls */
    public function testGenerate($section, $name, $generated, $path)
    {
        $actual = Media::generateUrl($path);
        $this->assertEquals($generated, $actual);
    }


    /** @dataProvider dataUrls */
    public function testMediaGenerate($section, $name, $generated, $path)
    {
        $expected = file_get_contents($path);
        $url = self::$server->getHostUrl() . $generated;

        $actual = HttpReq::get($url);
        $status = HttpReq::getLastreqStatus();

        $this->assertEquals(200, $status, $actual);
        $this->assertEquals($expected, $actual);
    }


    /** @dataProvider dataUrls */
    public function testMediaResolve($section, $name, $generated, $path)
    {
        $url = self::$server->getHostUrl() . "/_media/{$name}";

        $res = HttpReq::get($url);
        $status = HttpReq::getLastreqStatus();
        $headers = HttpReq::getLastreqHeaders();
        $headers = array_change_key_case($headers, CASE_LOWER);

        $expected = Media::generateUrl($path);
        $actual = $headers['location'][0] ?? null;

        $this->assertEquals(302, $status, $res);
        $this->assertEquals($expected, $actual);
    }


    /** @dataProvider dataUrls */
    public function testMediaCompat($section, $name, $generated, $path)
    {
        $prefix = match ($section) {
            'core' => 'media/',
            'sprout' => 'sprout/media/',
            'skin' => 'skin/default/',
            'Test' => 'Test/media/',
        };

        $url = self::$server->getHostUrl() . "/{$prefix}{$name}";

        $res = HttpReq::get($url);
        $status = HttpReq::getLastreqStatus();
        $headers = HttpReq::getLastreqHeaders();
        $headers = array_change_key_case($headers, CASE_LOWER);

        $expected = Media::generateUrl($path);
        $actual = $headers['location'][0] ?? null;

        $this->assertEquals(302, $status, $res);
        $this->assertEquals($expected, $actual);
    }


    /** @dataProvider dataTimestamp */
    public function testMediaTimestamp($path, $url)
    {
        $url = self::$server->getHostUrl() . '/' . $url;

        $res = HttpReq::get($url);
        $status = HttpReq::getLastreqStatus();
        $headers = HttpReq::getLastreqHeaders();
        $headers = array_change_key_case($headers, CASE_LOWER);

        $expected = Media::generateUrl($path);
        $actual = $headers['location'][0] ?? null;

        $this->assertEquals(302, $status, $res);
        $this->assertEquals($expected, $actual);
    }


    public function dataGroup()
    {
        return [
            'css' => ['css', 'path/to/file.css'],
            'js' => ['js', 'something/css/else.js'],
            'image' => ['images', 'blah/blah/css/js/test.png'],
            'other' => ['images', 'bogus/something/dont/match'],
        ];
    }


    public function dataUrls()
    {
        // section - path - generated - full path
        $data = [
            'core CSS' => [
                'core',
                'core/css/common.css',
                '_media/CHECKSUM/core/css/common.css',
                COREPATH . 'media/css/common.css',
            ],
            'sprout JS' => [
                'sprout',
                'sprout/js/admin_layout.js',
                '_media/CHECKSUM/sprout/js/admin_layout.js',
                APPPATH . 'media/js/admin_layout.js',
            ],
            'skin CSS' => [
                'skin',
                'skin/css/test.css',
                '_media/CHECKSUM/skin/css/test.css',
                DOCROOT . 'skin/default/css/test.css',
            ],
            'module image' => [
                'Test',
                'Test/images/office.png',
                '_media/CHECKSUM/Test/images/office.png',
                DOCROOT . 'modules/TestModule/media/images/office.png',
            ],
        ];


        foreach ($data as &$item) {
            $checksum = Media::generateChecksum($item[0]);
            $item[2] = str_replace('CHECKSUM', $checksum, $item[2]);
        }
        unset($item);
        return $data;
    }


    public function dataTimestamp()
    {
        return [
            'core' => [COREPATH . 'media/css/common.css', 'media-123123/css/common.css'],
            'skin' => [DOCROOT . 'skin/default/css/test.css', 'skin-556677/css/test.css'],
        ];
    }

}
