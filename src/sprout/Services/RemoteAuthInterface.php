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

namespace Sprout\Services;

/**
 * Minimal interface for remote authentication.
 *
 * Implement this as a concrete class and register it using `Register::remoteAuth()`.
 *
 * @package Sprout\Services
 */
interface RemoteAuthInterface extends ServiceInterface
{

    /**
     * Process a remote login, as provided by the external web service.
     *
     * @param array $request an auth request containing:
     *  - username
     *  - password
     *  - ip
     *  - user_agent
     * @return string|null A string UID on success, null otherwise.
     */
    public static function process(array $request): ?string;


    /**
     * Get the user details for a remote UID.
     *
     * @param string $uid
     * @return array|null - null if the user does not exist, or an array with:
     *  - uid
     *  - name
     *  - username
     *  - email
     */
    public static function getDetails(string $uid): ?array;
}
