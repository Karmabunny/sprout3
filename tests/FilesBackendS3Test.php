<?php

use PHPUnit\Framework\TestCase;
use Sprout\Helpers\Aws\S3;
use Sprout\Helpers\FilesBackendS3;

/**
 *
 */

class FilesBackendS3Test extends TestCase
{

    private static $_image_key = 'unit_test_image.jpg';
    private static $_test_image = 'tests/data/images/camper.png';
    private static $_image_path_orig;
    private static $_local_copy_path;

    /** @var FilesBackendS3 */
    private static $_backend;


    public static function setUpBeforeClass(): void
    {
        self::$_backend = new FilesBackendS3();
        self::$_local_copy_path = WEBROOT . 'files/' . self::$_image_key;
    }


    public function setUp(): void
    {
        // Create a copy of the test image so we can work with it
        self::$_image_path_orig = 'tests/data/images/' . self::$_image_key;

        copy(self::$_test_image, self::$_image_path_orig);
    }


    public function tearDown(): void
    {
        @unlink(self::$_image_path_orig);
        @unlink(self::$_local_copy_path);
        self::$_backend->delete(self::$_image_key);
    }


    public function testUploadImage()
    {
        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);
    }


    /**
     * This test is kinda of implicit anyway as we do it in the tear down
     *
     * But we'll leave it here for clarity
     */
    public function testDeleteImage()
    {
        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $res = self::$_backend->delete(self::$_image_key);
        $this->assertTrue($res);
    }


    public function testAbsUrls()
    {
        $url = self::$_backend->absUrl(self::$_image_key);
        $expected = "file/not-found?url=unit_test_image.jpg";
        $this->assertEquals($expected, $url);

        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $url = self::$_backend->absUrl(self::$_image_key);
        $this->assertNotEquals($expected, $url);

        $expected = S3::filesBackendUrl(self::$_image_key);
        $this->assertEquals($expected, $url);
    }


    public function testExists()
    {
        $res = self::$_backend->exists(self::$_image_key);
        $this->assertFalse($res);

        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $res = self::$_backend->exists(self::$_image_key);
        $this->assertTrue($res);
    }


    public function testExistsPublic()
    {
        $res = self::$_backend->existsPublic(self::$_image_key);
        $this->assertFalse($res);

        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $res = self::$_backend->existsPublic(self::$_image_key);
        $this->assertTrue($res);

        self::$_backend->makePrivate(self::$_image_key);
        $res = self::$_backend->existsPublic(self::$_image_key);
        $this->assertFalse($res);
    }


    public function testSize()
    {
        $size_local = filesize(self::$_image_path_orig);

        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $size = self::$_backend->size(self::$_image_key);
        $this->assertNotEmpty($size);
        $this->assertEquals($size_local, $size);
    }


    public function testTouchMtime()
    {
        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $mtime = self::$_backend->mtime(self::$_image_key);
        $this->assertNotEmpty($mtime);

        sleep(2);

        $res = self::$_backend->touch(self::$_image_key);
        $this->assertTrue($res);

        $mtime2 = self::$_backend->mtime(self::$_image_key);
        $this->assertGreaterThan($mtime, $mtime2);
        $this->assertGreaterThan(1, ($mtime2 - $mtime));
    }


    public function testImageSize()
    {
        $size_local = getimagesize(self::$_image_path_orig);

        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $size = self::$_backend->imageSize(self::$_image_key);
        $this->assertNotEmpty($size);

        $this->assertEquals($size_local[0], $size['0']);
        $this->assertEquals($size_local[1], $size['1']);
        $this->assertEquals($size_local[0], $size['0']);
        $this->assertEquals($size_local[1], $size['1']);

        $this->assertEquals($size_local['mime'], $size['mime']);
    }


    public function testReadfile()
    {
        $string_orig = file_get_contents(self::$_image_path_orig);
        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        ob_start();
        $res = self::$_backend->readfile(self::$_image_key);
        $this->assertNotFalse($res);
        $this->assertIsNumeric($res);
        $str = ob_get_clean();

        $this->assertNotEmpty($str);
        $this->assertEquals($string_orig, $str);
    }


    public function testGetString()
    {
        $string_orig = file_get_contents(self::$_image_path_orig);

        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $str = self::$_backend->getString(self::$_image_key);
        $this->assertNotEmpty($str);
        $this->assertEquals($string_orig, $str);
    }


    public function testPutString()
    {
        $string_orig = file_get_contents(self::$_image_path_orig);
        $res = self::$_backend->putString(self::$_image_key, $string_orig);
        $this->assertTrue($res);

        $str = self::$_backend->getString(self::$_image_key);
        $this->assertNotEmpty($str);
        $this->assertEquals($string_orig, $str);
    }


    public function testPutStream()
    {
        $stream = fopen(self::$_image_path_orig, 'r');
        $string_orig = file_get_contents(self::$_image_path_orig);

        $res = self::$_backend->putStream(self::$_image_key, $stream);
        $this->assertTrue($res);

        $str = self::$_backend->getString(self::$_image_key);
        $this->assertNotEmpty($str);
        $this->assertEquals($string_orig, $str);
    }


    public function testPutExisting()
    {
        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $res = self::$_backend->putExisting(self::$_image_key, self::$_image_key);
        $this->assertTrue($res);
    }


    public function testCreateLocalCopy()
    {
        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $file = self::$_backend->createLocalCopy(self::$_image_key);
        $this->assertNotEmpty($file);
    }


    public function testCleanupLocalCopy()
    {
        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $file = self::$_backend->createLocalCopy(self::$_image_key);
        $this->assertNotEmpty($file);

        $res = self::$_backend->cleanupLocalCopy(basename($file));
        $this->assertTrue($res);
    }


    public function testPublicPrivate()
    {
        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        self::$_backend->makePrivate(self::$_image_key);
        $expected = "file/not-found?url=unit_test_image.jpg";

        $url = self::$_backend->absUrl(self::$_image_key);
        $this->assertEquals($expected, $url);

        self::$_backend->makePublic(self::$_image_key);
        $expected = S3::filesBackendUrl(self::$_image_key);
        $url = self::$_backend->absUrl(self::$_image_key);
        $this->assertEquals($expected, $url);
    }

}
