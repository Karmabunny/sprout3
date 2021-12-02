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
* Will load the 'Helpers\UserAuth' class from the namespace
* registered for the feature "users".
**/
class UserAuth
{
    /**
     * Cached instance across multiple calls. FALSE = not yet loaded
     */
    public static $user_auth_inst = false;


    /**
     * Create an instance of the "real" user perms class, if it's available.
     *
     * @return object The "real" user perms class
     * @return null No module registering the feature 'users' is loaded
     */
    public static function realUserAuthInst()
    {
        if (self::$user_auth_inst !== false) {
            return self::$user_auth_inst;
        }

        if (Register::hasFeature('users')) {
            $ns = Register::getFeatureNamespace('users');
            $class = $ns . '\Helpers\UserAuth';
            self::$user_auth_inst = Sprout::instance($class);
        } else {
            self::$user_auth_inst = null;
        }

        return self::$user_auth_inst;
    }


    /**
     * Stub method for when the users module is not installed
     * See {@see SproutModules\Karmabunny\Users\Helpers\UserAuth::isLoggedIn}
     * @return bool True if the user is logged in, false otherwise
     */
    public static function isLoggedIn()
    {
        $inst = self::realUserAuthInst();
        if ($inst) {
            return $inst->isLoggedIn();
        } else {
            return false;
        }
    }


    /**
     * Gets id of logged-in user
     * See {@see SproutModules\Karmabunny\Users\Helpers\UserAuth::getId}
     * @return int 0 if user isn't logged in
     */
    public static function getId()
    {
        $inst = self::realUserAuthInst();
        if ($inst) {
            return $inst->getId();
        } else {
            return 0;
        }
    }
}
