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

use Sprout\Helpers\Url;


class urlTest extends PHPUnit_Framework_TestCase
{

    public function testCheckRedirect()
    {
        // Correct (no querystring)
        $this->assertTrue(Url::checkRedirect('/hello/world'));
        $this->assertTrue(Url::checkRedirect('hello/world'));
        $this->assertTrue(Url::checkRedirect('hello/wor_ld'));
        $this->assertTrue(Url::checkRedirect('hello/wor-ld'));
        $this->assertTrue(Url::checkRedirect(''));

        // Correct (with querystring)
        $this->assertTrue(Url::checkRedirect('/hello/world', true));
        $this->assertTrue(Url::checkRedirect('hello/world', true));
        $this->assertTrue(Url::checkRedirect('hello/wor_ld', true));
        $this->assertTrue(Url::checkRedirect('hello/wor-ld', true));
        $this->assertTrue(Url::checkRedirect('', true));
        $this->assertTrue(Url::checkRedirect('/hello/world?id=123', true));
        $this->assertTrue(Url::checkRedirect('hello/world?id=123', true));
        $this->assertTrue(Url::checkRedirect('hello/wor_ld?id=123', true));
        $this->assertTrue(Url::checkRedirect('hello/wor-ld?id=123', true));
        $this->assertTrue(Url::checkRedirect('hello/wor_ld?id=12-3', true));
        $this->assertTrue(Url::checkRedirect('hello/wor-ld?id=12_3', true));
        $this->assertTrue(Url::checkRedirect('hello/world?id=12_3', true));
        $this->assertTrue(Url::checkRedirect('hello/world?id=123%204', true));
        $this->assertTrue(Url::checkRedirect('hello/world?id=%20', true));
        $this->assertTrue(Url::checkRedirect('hello/world?id=aa_%20-bb', true));

        // Incorrect (querystring not allowed)
        $this->assertFalse(Url::checkRedirect('/hello/world?id=123', false));
        $this->assertFalse(Url::checkRedirect('hello/world?id=123', false));
        $this->assertFalse(Url::checkRedirect('hello/wo_rld?id=123', false));
        $this->assertFalse(Url::checkRedirect('hello/wo-rld?id=123', false));

        // Incorrect (no base url)
        $this->assertFalse(Url::checkRedirect('?id=123', false));
        $this->assertFalse(Url::checkRedirect('?id=123', true));

        // Incorrect (contains protocol)
        $this->assertFalse(Url::checkRedirect('http://www.evil.com', false));
        $this->assertFalse(Url::checkRedirect('http://www.evil.com', true));
        $this->assertFalse(Url::checkRedirect('https://www.evil.com', false));
        $this->assertFalse(Url::checkRedirect('https://www.evil.com', true));
        $this->assertFalse(Url::checkRedirect('ftp://www.evil.com', false));
        $this->assertFalse(Url::checkRedirect('ftp://www.evil.com', true));

        // Incorrect (assumed protocol)
        $this->assertFalse(Url::checkRedirect('://www.evil.com', false));
        $this->assertFalse(Url::checkRedirect('://www.evil.com', true));

        // Incorrect (weirdness)
        $this->assertFalse(Url::checkRedirect("\0"));
        $this->assertFalse(Url::checkRedirect("\r"));
        $this->assertFalse(Url::checkRedirect("\t"));
        $this->assertFalse(Url::checkRedirect("\n"));

        // Incorrect (non-strings)
        $this->assertFalse(Url::checkRedirect(array()));
        $this->assertFalse(Url::checkRedirect(new stdClass));
    }


    public function dataAddSocialDomain()
    {
        return [
            ['kbtestbot3000', 'instagram.com', 'https://instagram.com/kbtestbot3000'],
            ['https://instagram.com/kbtestbot3000', 'instagram.com', 'https://instagram.com/kbtestbot3000'],
            ['https://instagram.com/kbtestbot3000?xx', 'instagram.com', 'https://instagram.com/kbtestbot3000?xx'],
            ['https://instagram.com/kbtestbot3000#xx', 'instagram.com', 'https://instagram.com/kbtestbot3000#xx'],
            ['http://instagram.com/kbtestbot3000', 'instagram.com', 'http://instagram.com/kbtestbot3000'],
            ['HTTP://instagram.com/kbtestbot3000', 'instagram.com', 'http://instagram.com/kbtestbot3000'],
            ['HTTPS://INSTAGRAM.com/kbtestbot3000', 'instagram.com', 'https://instagram.com/kbtestbot3000'],
            ['instagram.com/kbtestbot3000', 'instagram.com', 'https://instagram.com/kbtestbot3000'],
            ['www.instagram.com/kbtestbot3000', 'instagram.com', 'https://instagram.com/kbtestbot3000'],
        ];
    }

    /**
    * @dataProvider dataAddSocialDomain
    **/
    public function testAddSocialDomain($social_link, $domain, $expected)
    {
        $this->assertEquals($expected, Url::addSocialDomain($social_link, $domain));
    }


    public function dataAddUrlScheme()
    {
        return [
            ['example.com', 'http://example.com'],
            ['example.com/xxx/yyy?zzz', 'http://example.com/xxx/yyy?zzz'],
            ['example.com/http/https?http=https', 'http://example.com/http/https?http=https'],
            ['http://example.com', 'http://example.com'],
            ['https://example.com', 'https://example.com'],
            ['HTTP://example.com', 'HTTP://example.com'],
            ['HTTPS://example.com', 'HTTPS://example.com'],
        ];
    }

    /**
    * @dataProvider dataAddUrlScheme
    **/
    public function testAddUrlScheme($url, $expected)
    {
        $this->assertEquals($expected, Url::addUrlScheme($url));
    }

}
