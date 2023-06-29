<?php

use karmabunny\pdb\Exceptions\ConnectionException;
use PHPUnit\Framework\TestCase;
use Sprout\Helpers\HttpReq;
use Sprout\Helpers\Pdb;
use Sprout\SproutVisor;


class VisorTest extends TestCase
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


    public function testVisor()
    {
        $url = self::$server->getHostUrl();

        // A 302 redirect.
        $body = HttpReq::get($url . '/dbtools/testing');
        $status = HttpReq::getLastreqStatus();
        $headers = HttpReq::getLastreqHeaders();

        $this->assertEquals(302, $status);
        $this->assertStringContainsString('admin/login?redirect', $body);
        $this->assertEquals($url . '/admin/login?redirect=dbtools%2Ftesting', $headers['location'][0]);

    }


    public function testHomePage()
    {
        // The home page, 404 because we haven't got a fallback homepage thingy.
        // TODO should we?
        $url = self::$server->getHostUrl();
        $body = HttpReq::get($url);
        $status = HttpReq::getLastreqStatus();
        $headers = HttpReq::getLastreqHeaders();

        $this->assertEquals(404, $status);
        $this->assertStringStartsWith('text/html', $headers['content-type'][0]);
        $this->assertStringContainsString('<title>', $body);
        $this->assertStringContainsString('404', $body);
        $this->assertStringContainsString('Sprout3 test', $body);
    }
}
