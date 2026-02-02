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

use Exception;
use Sprout\Services\AdminAuthInterface;


/**
* Provides user authentication functions for the admin
**/
class AdminAuth implements AdminAuthInterface
{

    /** @var AdminAuthInterface|null */
    private static $inst;


    /** @inheritdoc */
    public static function configure(array $config)
    {
    }


    /**
     * Get an instance of the current AdminAuthInterface implementation
     *
     * @return AdminAuthInterface
     */
    private static function getInst(): AdminAuthInterface
    {
        if (!empty(self::$inst)) return self::$inst;

        self::$inst = Services::get(AdminAuthInterface::class);
        if (empty(self::$inst)) {
            throw new Exception('AdminAuthInterface not registered');
        }

        return self::$inst;
    }


    /** @inheritdoc */
    public static function getSessionKey(): string
    {
        return self::getInst()->getSessionKey();
    }


    /** @inheritdoc */
    public static function isLoggedIn(): bool
    {
        return self::getInst()->isLoggedIn();
    }


    /** @inheritdoc */
    public static function getId(): int
    {
        return self::getInst()->getId();
    }


    /** @inheritdoc */
    public static function checkLogin($msg = null)
    {
        self::getInst()->checkLogin($msg);
    }


    /** @deprecated use CoreAdminAuth */
    public static function processLogin($username, $password)
    {
        return CoreAdminAuth::processLogin($username, $password);
    }


    /** @deprecated use CoreAdminAuth */
    public static function checkPassword($password, $operator_id = null)
    {
        return CoreAdminAuth::checkPassword($password, $operator_id);
    }


    /** @deprecated use CoreAdminAuth */
    public static function processOpenid($openid)
    {
        return CoreAdminAuth::processOpenid($openid);
    }


    /** @deprecated use CoreAdminAuth */
    public static function processLocal($username, $password)
    {
        return CoreAdminAuth::processLocal($username, $password);
    }


    /** @deprecated use CoreAdminAuth */
    public static function injectLocalSuperConf($username, $pass_hash, $pass_salt)
    {
        return CoreAdminAuth::injectLocalSuperConf($username, $pass_hash, $pass_salt);
    }


    /** @deprecated use CoreAdminAuth */
    public static function processRemote($username, $password)
    {
        return CoreAdminAuth::processRemote($username, $password);
    }


    /** @inheritdoc */
    public static function changePassword($new_password, $operator_id = null)
    {
        return self::getInst()->changePassword($new_password, $operator_id);
    }


    /** @inheritdoc */
    public static function createUser(array $details, string $password)
    {
        return self::getInst()->createUser($details, $password);
    }


    /** @inheritdoc */
    public static function updateDetails(int $item_id, array $details)
    {
        return self::getInst()->updateDetails($item_id, $details);
    }


    /** @deprecated use CoreAdminAuth */
    public static function checkRateLimit($username, $ip)
    {
        return CoreAdminAuth::checkRateLimit($username, $ip);
    }


    /** @deprecated use CoreAdminAuth */
    public static function saveLoginAttempt($username, $ip, $success)
    {
        return CoreAdminAuth::saveLoginAttempt($username, $ip, $success);
    }


    /** @inheritdoc */
    public static function loginComplete(?string $username = '', ?string $redirect = '')
    {
        return self::getInst()->loginComplete($username, $redirect);
    }


    /** @inheritdoc */
    public static function logout(): void
    {
        self::getInst()->logout();
    }


    /** @inheritdoc */
    public static function getLocalId()
    {
        return self::getInst()->getLocalId();
    }


    /**
     * Get the email of the current or a specified operator
     *
     * @param null|int $operator_id
     * @return string|null
     */
    public static function getEmail(?int $operator_id = null)
    {
        if (empty($operator_id)) $operator_id = self::getId();

        if (empty($operator_id)) {
            Notification::error('Operator not found, please contact your administrator');
            Url::redirect('/error');
        }

        // NOTE if using Auth0 or another provider, the remote ID is not the same as our Operator ID
        $q = "SELECT email FROM ~operators WHERE id = ?";
        $email = Pdb::query($q, [$operator_id], 'val?');

        return $email;
    }


    /** @inheritdoc */
    public static function getDetails()
    {
        return self::getInst()->getDetails();
    }


    /** @deprecated use CoreAdminAuth */
    public static function inCategory($category_id)
    {
        return CoreAdminAuth::inCategory($category_id);
    }


    /** @deprecated use CoreAdminAuth */
    public static function getOperatorCategories()
    {
        return CoreAdminAuth::getOperatorCategories();
    }


    /** @deprecated use CoreAdminAuth */
    public static function getAllCategories()
    {
        return CoreAdminAuth::getAllCategories();
    }


    /** @inheritdoc */
    public static function isSuper(): bool
    {
        return self::getInst()->isSuper();
    }


    /** @inheritdoc */
    public static function hasDatabaseRecord(): bool
    {
        return self::getInst()->hasDatabaseRecord();
    }


    /** @deprecated use CoreAdminAuth */
    public static function getPrimaryCategoryId()
    {
        return CoreAdminAuth::getPrimaryCategoryId();
    }


    /** @inheritdoc */
    public static function deleteUser(int $item_id): bool
    {
        return self::getInst()->deleteUser($item_id);
    }

}
