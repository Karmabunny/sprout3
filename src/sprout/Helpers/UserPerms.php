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

use Sprout\Services\UserPermsInterface;

/**
 * Default implementation of user permissions for when the users module is not installed.
 *
 * Methods here will however use the installed service, if available.
 */
class UserPerms implements UserPermsInterface
{

    /** @inheritdoc */
    public static function configure(array $config)
    {
        return [];
    }


    /** @inheritdoc */
    public static function checkPermissionsTree(string $table, int $id): bool
    {
        /** @var UserPermsInterface|null $inst */
        $inst = Services::get(UserPermsInterface::class);

        if ($inst) {
            return $inst->checkPermissionsTree($table, $id);
        } else {
            return true;
        }
    }


    /** @inheritdoc */
    public static function getAccessableGroups(string $table, int $id): array
    {
        /** @var UserPermsInterface|null $inst */
        $inst = Services::get(UserPermsInterface::class);

        if ($inst) {
            return $inst->getAccessableGroups($table, $id);
        } else {
            return [];
        }
    }

    /** @inheritdoc */
    public static function getAccessDenied(): ?BaseView
    {
        /** @var UserPermsInterface|null $inst */
        $inst = Services::get(UserPermsInterface::class);

        if ($inst) {
            return $inst->getAccessDenied();
        } else {
            return null;
        }
    }

    /** @inheritdoc */
    public static function getAllCategories(): array
    {
        /** @var UserPermsInterface|null $inst */
        $inst = Services::get(UserPermsInterface::class);

        if ($inst) {
            return $inst->getAllCategories();
        } else {
            return [];
        }
    }
}


