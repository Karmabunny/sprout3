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

namespace Sprout\Helpers;

use Sprout\Services\UserAuthInterface;

/**
 * A default implementation of user auth when the users module is not installed.
 *
 * Methods here will however use the installed service, if available.
 */
class UserAuth implements UserAuthInterface
{

    /** @inheritdoc */
    public static function configure(array $config)
    {
    }


    /** @inheritdoc */
    public static function isLoggedIn(): bool
    {
        /** @var UserAuthInterface|null */
        $inst = Services::get(UserAuthInterface::class);

        if ($inst) {
            return $inst->isLoggedIn();
        } else {
            return false;
        }
    }


    /** @inheritdoc */
    public static function getId(): int
    {
        /** @var UserAuthInterface|null */
        $inst = Services::get(UserAuthInterface::class);

        if ($inst) {
            return $inst->getId();
        } else {
            return 0;
        }
    }


    /** @inheritdoc */
    public static function checkLogin($msg = null)
    {
        /** @var UserAuthInterface|null */
        $inst = Services::get(UserAuthInterface::class);
        if ($inst) {
            $inst->checkLogin($msg);
        } else {
            Notification::error($msg);
            Url::redirect('/');
        }
    }
}
