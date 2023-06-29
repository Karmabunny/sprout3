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

/**
 * Testing the shim for kohana events.
 */
class EventTest extends TestCase
{

    public function testKohanaDisplayEvent()
    {
        $expected = random_bytes(16);

        $called = false;

        Event::add('system.display', function() use ($expected, &$called) {
            $this->assertEquals($expected, Event::$data);
            $called = true;
        });

        Event::run('system.display', $expected);

        $this->assertTrue($called);
    }


    public function testKohanaCustomAddEvent()
    {
        $this->expectException(InvalidArgumentException::class);

        Event::add('custom.event', fn() => null);
    }


    public function testKohanaCustomRunEvent()
    {
        $this->expectException(InvalidArgumentException::class);

        $data = '';
        Event::run('custom.event', $data);
    }
}
