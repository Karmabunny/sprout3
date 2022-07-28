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


/**
* Stub class for when the users module is not installed
*
* Makes use of the fact that it's legal to call static
* methods from instances
*
* Will load the 'Helpers\UserPerms' class from the namespace
* registered for the feature "users".
**/
class UserPerms
{
    /**
     * Cached instance across multiple calls. FALSE = not yet loaded
     */
    public static $user_perms_inst = false;


    /**
     * Create an instance of the "real" user perms class, if it's available.
     *
     * @return object The "real" user perms class
     * @return null No module registering the feature 'users' is loaded
     */
    protected static function realUserPermsInst()
    {
        if (self::$user_perms_inst !== false) {
            return self::$user_perms_inst;
        }

        if (Register::hasFeature('users')) {
            $ns = Register::getFeatureNamespace('users');
            $class = $ns . '\Helpers\UserPerms';
            self::$user_perms_inst = Sprout::instance($class);
        } else {
            self::$user_perms_inst = null;
        }

        return self::$user_perms_inst;
    }


    /**
     * Stub method for when the users module is not installed
     * See {@see SproutModules\Karmabunny\Users\Helpers\UserPerms::checkPermissionsTree}
     * @return bool True if the user has access, false otherwise
     */
    public static function checkPermissionsTree($table, $id)
    {
        $inst = self::realUserPermsInst();
        if ($inst) {
            return $inst->checkPermissionsTree($table, $id);
        } else {
            return true;
        }
    }

    /**
     * Stub method; uses real one if Users module is installed.
     * See {@see SproutModules\Karmabunny\Users\Helpers\UserPerms::getAccessableGroups}
     * @return array Each element is a category id
     */
    public static function getAccessableGroups($table, $id)
    {
        $inst = self::realUserPermsInst();
        if ($inst) {
            return $inst->getAccessableGroups($table, $id);
        } else {
            return [];
        }
    }

    /**
     * Stub method; uses real one if Users module is installed.
     * See {@see SproutModules\Karmabunny\Users\Helpers\UserPerms::getAccessDenied}
     * @return BaseView
     */
    public static function getAccessDenied()
    {
        $inst = self::realUserPermsInst();
        if ($inst) {
            return $inst->getAccessDenied();
        } else {
            return null;
        }
    }

    /**
     * Stub method; uses real one if Users module is installed.
     * See {@see SproutModules\Karmabunny\Users\Helpers\UserPerms::getAllCategories}
     * @return array id => name
     */
    public static function getAllCategories()
    {
        $inst = self::realUserPermsInst();
        if ($inst) {
            return $inst->getAllCategories();
        } else {
            return [];
        }
    }
}


