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
use Sprout\Helpers\File;
use Sprout\Helpers\FileTransform;

class FileTransformTest extends TestCase
{
    private static $_image_key = 'unit_test_image_resize.jpg';
    private static $_test_image = 'tests/data/images/camper.png';
    private static $_image_path_orig;


    public function setUp(): void
    {
        // Create a copy of the test image so we can work with it
        self::$_image_path_orig = 'tests/data/images/' . self::$_image_key;

        copy(self::$_test_image, self::$_image_path_orig);
    }


    public function tearDown(): void
    {
        @unlink(self::$_image_path_orig);
        File::delete(self::$_image_key);
    }


    public function dataResize()
    {
        return [
            'c400x200' => ['c400x200', 400, 200],
            'c200x400' => ['c200x400', 200, 400],
            'c800x600' => ['c800x600', 800, 600],
            'c600x800' => ['c600x800', 600, 800],
            'r512x200' => ['c512x200', 512, 200],
            'r400x400' => ['r400x400', 400, 223],
            'r200x200' => ['r200x200', 200, 111],
            'm600x600' => ['m400x400', 400, 223],
            'r50x50' => ['r50x50', 50, 28],
        ];
    }


    /**
     * @dataProvider dataResize
     *
     * @return void
     */
    public function testResize(string $resize_str, int $width, int $height): void
    {
        $size_orig = @getimagesize(self::$_image_path_orig);
        // print_r($size_orig);

        $this->assertNotEmpty($size_orig);
        $this->assertEquals(1920, $size_orig[0]);
        $this->assertEquals(1069, $size_orig[1]);

        $resized = FileTransform::resizeImage(self::$_image_path_orig, $resize_str);
        $this->assertTrue($resized);

        $size_resized = @getimagesize(self::$_image_path_orig);
        // print_r($size_resized);

        $this->assertNotEmpty($size_resized);
        $this->assertEquals($width, $size_resized[0]);
        $this->assertEquals($height, $size_resized[1]);
    }


    public function dataDefaultSizes()
    {
        return [
            [['small', 'medium', 'large']],
        ];
    }


    /**
     * @dataProvider dataDefaultSizes
     *
     * Note this test is based on the default sizes set in the 'file' config
     *
     * @return void
     */
    public function testDefaultSizes(array $transform_names): void
    {
        // Ensure the active backend has the file
        File::putExisting(self::$_image_key, self::$_image_path_orig);

        File::createDefaultSizes(self::$_image_key);

        foreach ($transform_names as $transform_name) {
            $transform_filename = FileTransform::getTransformFilename(self::$_image_key, $transform_name);
            $this->assertTrue(File::exists($transform_filename));

            $res = File::delete($transform_filename);
            $this->assertTrue($res);
        }
    }


    public function dataSizeRecords()
    {
        return [
            ['r600x500'],
        ];
    }


    /**
     * @dataProvider dataDefaultSizes
     *
     * Note this test is based on the default sizes set in the 'file' config
     *
     * @return void
     */
    public function testDefaultSizeRecords(array $transform_names): void
    {
        // Ensure the active backend has the file
        File::putExisting(self::$_image_key, self::$_image_path_orig);

        File::createDefaultSizes(self::$_image_key);

        foreach ($transform_names as $transform_name) {
            $transform_filename = FileTransform::getTransformFilename(self::$_image_key, $transform_name);
            $this->assertTrue(File::exists($transform_filename));

            $transform = FileTransform::findByFilename(self::$_image_key, $transform_name);
            $this->assertNotEmpty($transform);
            $this->assertGreaterThan(0, $transform->id);

            $this->assertEquals($transform_filename, $transform->transform_filename);

            // Delete entries directly
            $transform->delete(true);
        }
    }


    /**
     * @dataProvider dataDefaultSizes
     *
     * Note this test is based on the default sizes set in the 'file' config
     *
     * @return void
     */
    public function testDefaultSizeRecordUrls(array $transform_names): void
    {
        // Ensure the active backend has the file
        File::putExisting(self::$_image_key, self::$_image_path_orig);

        File::createDefaultSizes(self::$_image_key);

        foreach ($transform_names as $transform_name) {
            $transform_filename = FileTransform::getTransformFilename(self::$_image_key, $transform_name);
            $this->assertTrue(File::exists($transform_filename));

            $transform = FileTransform::findByFilename(self::$_image_key, $transform_name);
            $this->assertNotEmpty($transform);
            $this->assertGreaterThan(0, $transform->id);

            $start = microtime();
            $url = File::absUrl($transform->transform_filename);
            $end = microtime();
            echo "\nURL Build time for {$transform_filename}: " . ((int) $end - (int) $start) . "\n";

            $this->assertNotEmpty($url);
            $this->assertStringNotContainsString('missing', $url);

            // Delete entries directly
            $transform->delete(true);
        }
    }


    /**
     * @dataProvider dataDefaultSizes
     *
     * Note this test is based on the default sizes set in the 'file' config
     *
     * @return void
     */
    public function testDeleteTransforms(array $transform_names): void
    {
        // Ensure the active backend has the file
        File::putExisting(self::$_image_key, self::$_image_path_orig);

        File::createDefaultSizes(self::$_image_key);

        foreach ($transform_names as $transform_name) {
            $transform_filename = FileTransform::getTransformFilename(self::$_image_key, $transform_name);
            $this->assertTrue(File::exists($transform_filename));

            $transform = FileTransform::findByFilename(self::$_image_key, $transform_name);
            $this->assertNotEmpty($transform);
            $this->assertGreaterThan(0, $transform->id);

            $start = microtime();
            echo "\n" . File::absUrl($transform->transform_filename);
            $end = microtime();
            echo "\n\nURL Build time: " . ((int) $end - (int) $start) . "\n\n";
        }

        FileTransform::deleteTransforms(self::$_image_key);

        foreach ($transform_names as $transform_name) {
            $transform_filename = FileTransform::getTransformFilename(self::$_image_key, $transform_name);
            $this->assertFalse(File::exists($transform_filename));

            $transform = FileTransform::findByFilename(self::$_image_key, $transform_name);
            $this->assertEmpty($transform);
        }
    }

}
