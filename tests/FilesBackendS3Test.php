<?php

use Aws\Exception\AwsException;
use Aws\Exception\CredentialsException;
use Aws\S3\Exception\S3Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Sprout\Helpers\FilesBackendS3;

/**
 *
 */

class FilesBackendS3Test extends TestCase
{

    private static $_config;
    private static $_image_key = 'unit_test_image_s3.png';
    private static $_test_image = 'tests/data/images/camper.png';
    private static $_image_path_orig;
    private static $_local_copy_path;

    /** @var FilesBackendS3 */
    private static $_backend;


    public static function setUpBeforeClass(): void
    {
        self::$_backend = new FilesBackendS3();
        self::$_local_copy_path = WEBROOT . 'files/' . self::$_image_key;
        self::$_config = self::$_backend->getSettings();
        self::$_config['region'] = self::$_backend->getS3Client()->getRegion();

        try {
            self::$_backend->clearCaches('_test_');
            self::$_backend->delete('_test_');
        } catch (CredentialsException|AwsException $e) {
            echo "SKIP S3 TEST SUITE\n > ", $e->getMessage(), "\n\n";
            self::markTestSkipped($e->getMessage());
        }
    }


    public function setUp(): void
    {
        self::$_backend->clearCaches(self::$_image_key);

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

        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $url = self::$_backend->absUrl(self::$_image_key);
        $expected = sprintf('https://%s.s3.%s.amazonaws.com/%s', self::$_config['bucket'], self::$_config['region'], self::$_image_key);

        if (self::$_config['require_url_signing'] === true) {
            $this->assertStringContainsString($expected, $url);
        } else {
            $this->assertEquals($expected, $url);
        }
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

        if (self::$_config['public_access'] === false) {
            $this->expectException(S3Exception::class);

            $res = self::$_backend->makePublic(self::$_image_path_orig, self::$_image_key);
            $this->assertTrue($res);

        } else {
            $res = self::$_backend->makePublic(self::$_image_path_orig, self::$_image_key);
            $this->assertTrue($res);

            $res = self::$_backend->existsPublic(self::$_image_key);
            $this->assertTrue($res);
        }
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


    public function dataImageSize(): array
    {
        return [
            'png' => [self::$_test_image, 'image/png'],
            'jpg' => ['tests/data/images/camper.jpg', 'image/jpeg'],
            'webp' => ['tests/data/images/camper.webp', 'image/webp'],
        ];
    }


    /** @dataProvider dataImageSize */
    public function testImageSize($local_image, $mime_type)
    {
        $size_local = getimagesize($local_image);

        if (!$size_local or $size_local['mime'] != $mime_type) {
            $this->fail('Test file has invalid or missing mimetype');
        }

        $res = self::$_backend->putExisting(self::$_image_key, $local_image);
        $this->assertTrue($res);

        $size = self::$_backend->imageSize(self::$_image_key);
        $this->assertNotEmpty($size);

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


    public function testGetStream()
    {
        $string_orig = file_get_contents(self::$_image_path_orig);
        $res = self::$_backend->moveUpload(self::$_image_path_orig, self::$_image_key);
        $this->assertTrue($res);

        $res = self::$_backend->getStream(self::$_image_key);
        $this->assertInstanceOf(StreamInterface::class, $res);

        $contents = $res->getContents();
        $this->assertNotEmpty($contents);
        $this->assertEquals($string_orig, $contents);
    }


    public function testPutExisting()
    {
        $res = self::$_backend->putExisting(self::$_image_key, self::$_image_path_orig);
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

        $res = self::$_backend->cleanupLocalCopy($file);
        $this->assertTrue($res);
    }



    public function testBigReadFile()
    {
        $this->markTestSkipped('Toggle this when you\'re ready');

        $big_file = 'tests/data/big-file.dat';
        $output_file = 'tests/data/output-file.dat';
        @unlink($big_file);
        @unlink($output_file);

        self::$_backend->clearCaches('big-file.dat');

        $memory_limit = ini_get('memory_limit');

        // Only a bit more but not enough for 50mb.
        $memory = floor(memory_get_usage(true) / 1024 / 1024);
        ini_set('memory_limit', ($memory + 5) . 'M');

        try {
            // 50mb
            foreach (range(0, 50) as $i) {
                $bytes = random_bytes(1024);
                $bytes = str_repeat($bytes, 1024);
                file_put_contents($big_file, $bytes, FILE_APPEND);
            }

            unset($bytes);

            // Load it up.
            $stream = fopen($big_file, 'r');
            $res = self::$_backend->putStream('big-file.dat', $stream);
            $this->assertTrue($res);

            // Small chunks here that write out to the file.
            ob_start(function($chunk) use ($output_file) {
                file_put_contents($output_file, $chunk, FILE_APPEND);
                return '';
            }, 1024);

            $length = self::$_backend->readfile('big-file.dat');

            // This will be empty.
            ob_end_clean();

            // OK?
            $this->assertEquals(sha1_file($big_file), sha1_file($output_file));
            $this->assertEquals(filesize($big_file), $length);

        } finally {
            if ($stream) @fclose($stream);

            @unlink($big_file);
            @unlink($output_file);

            self::$_backend->delete('big-file.dat');

            ini_set('memory_limit', $memory_limit);
        }
    }


    public function testBigStreamFile()
    {
        $this->markTestSkipped('Toggle this when you\'re ready');

        $big_file = 'tests/data/big-file.dat';
        $output_file = 'tests/data/output-file.dat';
        @unlink($big_file);
        @unlink($output_file);

        self::$_backend->clearCaches('big-file.dat');

        $memory_limit = ini_get('memory_limit');

        // Only a bit more but not enough for 50mb.
        $memory = floor(memory_get_usage(true) / 1024 / 1024);
        ini_set('memory_limit', ($memory + 5) . 'M');

        try {
            // 50mb
            foreach (range(0, 50) as $i) {
                $bytes = random_bytes(1024);
                $bytes = str_repeat($bytes, 1024);
                file_put_contents($big_file, $bytes, FILE_APPEND);
            }

            unset($bytes);

            // Load it up.
            $stream1 = fopen($big_file, 'r');
            $this->assertNotFalse($stream1);

            $res = self::$_backend->putStream('big-file.dat', $stream1);
            $this->assertTrue($res);

            $stream2 = fopen($output_file, 'w');
            $this->assertNotFalse($stream2);

            $stream3 = self::$_backend->getStream('big-file.dat');
            $stream3 = $stream3 ? $stream3->detach() : null;
            $this->assertNotNull($stream3);

            $length = stream_copy_to_stream($stream3, $stream2);
            $this->assertNotFalse($length);

            // OK?
            $this->assertEquals(sha1_file($big_file), sha1_file($output_file));
            $this->assertEquals(filesize($big_file), $length);

        } finally {
            if ($stream1) @fclose($stream1);
            if ($stream2) @fclose($stream2);
            if ($stream3) @fclose($stream3);

            @unlink($big_file);
            @unlink($output_file);

            self::$_backend->delete('big-file.dat');

            ini_set('memory_limit', $memory_limit);
        }
    }
}
