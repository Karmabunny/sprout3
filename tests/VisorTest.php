<?php

use PHPUnit\Framework\TestCase;
use Sprout\Helpers\HttpReq;
use Sprout\SproutVisor;


class VisorTest extends TestCase
{

    /** @var SproutVisor */
    public $server;


    public function setUp(): void
    {
        $this->server = SproutVisor::create([
            'webroot' => __DIR__ . '/web',
        ]);
    }


    public function testVisor()
    {
        $url = $this->server->getHostUrl();

        // A 302 redirect.
        $body = HttpReq::get($url . '/dbtools/testing');
        $status = HttpReq::getLastreqStatus();
        $headers = HttpReq::getLastreqHeaders();

        $this->assertEquals(302, $status);
        $this->assertStringContainsString('admin/login?redirect', $body);
        $this->assertEquals($url . '/admin/login?redirect=dbtools%2Ftesting', $headers['location'][0]);

        // The home page, 404 because we haven't got a fallback homepage thingy.
        // TODO should we?
        $url = $this->server->getHostUrl();
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
