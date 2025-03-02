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


    /** @dataProvider dataUrls */
    public function testParse($section, $file, $root, $checksum)
    {
        $actual = Media::parse("{$section}/{$file}");

        $this->assertEquals($section, $actual->section);
        $this->assertEquals("_media/{$checksum}/{$section}/{$file}", $actual->generateUrl());
        $this->assertEquals($root, $actual->root);
    }


    /** @dataProvider dataUrls */
    public function testUrlNoGenerate($section, $file, $root, $checksum)
    {
        $expected = "_media/{$section}/{$file}?" . @filemtime($root . $file);
        $actual = Media::url("{$section}/{$file}", false);
        $this->assertEquals($expected, $actual);
    }


    /** @dataProvider dataUrls */
    public function testMediaGenerate($section, $file, $root, $checksum)
    {
        $expected = file_get_contents($root . $file);
        $url = self::$server->getHostUrl() . "/_media/{$checksum}/{$section}/{$file}";

        $actual = HttpReq::get($url);
        $status = HttpReq::getLastreqStatus();

        $this->assertEquals(200, $status, $actual);
        $this->assertEquals($expected, $actual);
    }


    /** @dataProvider dataUrls */
    public function testMediaResolve($section, $file, $root, $checksum)
    {
        $media = Media::parse("{$section}/{$file}");

        $url = self::$server->getHostUrl() . "/_media/{$section}/{$file}";
        $res = HttpReq::get($url);

        $status = HttpReq::getLastreqStatus();
        $this->assertEquals(302, $status, $res);

        $headers = HttpReq::getLastreqHeaders();
        $headers = array_change_key_case($headers, CASE_LOWER);

        $actual = $headers['location'][0] ?? null;
        $this->assertNotNull($actual);

        $actual = parse_url($actual, PHP_URL_PATH);
        $expected = "/_media/{$checksum}/{$section}/{$file}";
        $this->assertEquals($expected, $actual);
    }


    /** @dataProvider dataUrls */
    public function testMediaCompat($section, $file, $root, $checksum)
    {
        $prefix = match ($section) {
            'core' => '/media/' ,
            'sprout' => '/sprout/media/',
            'skin/default' => '/skin/default/',
            'modules/Test' => "/{$section}/media/",
        };

        $url = self::$server->getHostUrl() . $prefix . $file;
        $res = HttpReq::get($url);

        $status = HttpReq::getLastreqStatus();
        $this->assertEquals(302, $status, $res);

        $headers = HttpReq::getLastreqHeaders();
        $headers = array_change_key_case($headers, CASE_LOWER);

        $actual = $headers['location'][0] ?? null;
        $this->assertNotNull($actual);

        $actual = parse_url($actual, PHP_URL_PATH);
        $expected = "/_media/{$checksum}/{$section}/{$file}";
        $this->assertEquals($expected, $actual);
    }


    /** @dataProvider dataTimestamp */
    public function testMediaTimestamp($url, $generated)
    {
        $url = self::$server->getHostUrl() . '/' . $url;
        $res = HttpReq::get($url);

        $status = HttpReq::getLastreqStatus();
        $this->assertEquals(302, $status, $res);

        $headers = HttpReq::getLastreqHeaders();
        $headers = array_change_key_case($headers, CASE_LOWER);

        $actual = $headers['location'][0] ?? null;
        $this->assertNotNull($actual);

        $actual = trim(parse_url($actual, PHP_URL_PATH), '/');
        $this->assertEquals($generated, $actual);
    }


    public function dataUrls()
    {
        // section, file, root, (+checksum)
        $data = [
            'core CSS' => [
                'core',
                'css/common.css',
                COREPATH . 'media/',
            ],
            'sprout JS' => [
                'sprout',
                'js/admin_layout.js',
                APPPATH . 'media/',
            ],
            'skin CSS' => [
                'skin/default',
                'css/test.css',
                DOCROOT . 'skin/default/',
            ],
            'module image' => [
                'modules/Test',
                'images/office.png',
                DOCROOT . 'modules/TestModule/media/',
            ],
        ];

        foreach ($data as &$item) {
            $item[] = Media::generateChecksum($item[2]);
        }
        unset($item);
        return $data;
    }


    public function dataTimestamp()
    {
        return [
            'core' => [
                'media-123123/css/common.css',
                Media::parse('core/css/common.css')->generateUrl(),
            ],
            'skin' => [
                'skin-556677/default/css/test.css',
                Media::parse('skin/default/css/test.css')->generateUrl(),
            ],
        ];
    }

}
