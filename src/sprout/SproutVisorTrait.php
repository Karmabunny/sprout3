<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout;

use karmabunny\visor\errors\VisorException;
use PHPUnit\Framework\Assert;

/**
 * A trait for creating visors on PHPUnit test cases.
 *
 * @mixin Assert
 */
trait SproutVisorTrait
{

    /** @var SproutVisor */
    public static $server;


    /**
     * @beforeClass
     */
    public static function setUpVisor(): void
    {
        try {
            self::$server ??= SproutVisor::create([
                'ports' => [5632, 5633, 5634],
                'webroot' => __DIR__ . '/web',
            ]);
        } catch (VisorException $ex) {
            self::markTestSkipped('mysql is not available right now');
        }
    }


    /**
     * @afterClass
     */
    public static function tearDownVisor(): void
    {
        if (self::$server) {
            self::$server->stop();
        }
    }
}
