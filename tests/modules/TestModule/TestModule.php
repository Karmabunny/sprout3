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

namespace Sprout\TestModules\TestModule;

use Sprout\Helpers\Module;
use Sprout\Helpers\Register;
use Sprout\Helpers\Sprout;
use Sprout\TestModules\TestModule\Controllers\TestController;

/**
 * A test module.
 */
class TestModule extends Module
{

    /** @inheritdoc */
    public function getVersion(): string
    {
        return Sprout::getVersion('sproutcms/cms');
    }


    public function loadSprout(): void
    {
        Register::cronJob('test', TestController::class, 'cronTest');
    }
}
