<?php
/*
 * Copyright (C) 2024 Karmabunny Pty Ltd.
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
 * Provides admin authentication functions for the back-end
 *
 * @package Sprout\Services
 */
interface AdminAuthInterface extends ServiceInterface
{

    /**
     * Get the session key, e.g. 'admin'
     *
     * @return string
     */
    public static function getSessionKey(): string;


    /**
     * Check if the user is logged in or not
     *
     * @return bool True if the user is logged in, false otherwise
     */
    public static function isLoggedIn(): bool;


    /**
     * If the user is not logged in, redirect them to a login page.
     *
     * @param string $msg Optional message to display on the login page
     * @return void
     */
    public static function checkLogin($msg = null);


    /**
     * Set up various login params and redirect into admin
     *
     * Called after a successful login (either one-factor or two-factor)
     *
     * @param string|null $username Provided username or empty string
     * @param string|null $redirect URL to redirect to after login (or empty string)
     * @return string URL to redirect to
     */
    public static function loginComplete(?string $username = '', ?string $redirect = '');


    /**
     * Gets id of logged-in user
     *
     * @return int 0 if user isn't logged in
     */
    public static function getId(): int;


    /**
     * Log a user out of the system
     *
     * @return void
     */
    public static function logout();


    /**
     * Fetches the ID of current operator if and only if they're a local operator, otherwise 0.
     *
     * @return int An ID if a local operator, 0 otherwise
     */
    public static function getLocalId();


    /**
    * Sets the password for a operator, or the current operator if a operator-id is not specified.
    *
    * @param string $new_password The new password.
    * @param int|null $operator_id The operator to update. If not specified, the currently logged in operator is used.
    **/
    public static function changePassword(string $new_password, ?int $operator_id = null);


    /**
     * Gets the id, name, username and email of the currently logged in operator.
     * N.B. the id will be 0 for remote users
     *
     * @return array|bool Under normal circumstances, with keys 'id', 'name', 'username', 'email' and 'editor'. False if fetching data for a remote operator failed
     */
    public static function getDetails();


    /**
     * Create a new user with a set of details, excluding password
     *
     * @param array $details
     * @param string $password
     *
     * @return int The ID of the new user
     */
    public static function createUser(array $details, string $password);


    /**
     * Update a set of details for a user, excluding password
     *
     * @param int $item_id
     * @param array $details
     * @return bool
     */
    public static function updateDetails(int $item_id, array $details);


    /**
     * Does the record-id for this login correspond to a local database record?
     *
     * @return bool True if the logged-in operator has a database record
     */
    public static function hasDatabaseRecord(): bool;


    /**
     * A super-operator -- has access to everything (dev tools, all permissions, etc)
     *
     * @return bool True if the logged-in user is a super-operator
     */
    public static function isSuper(): bool;


    /**
     * Delete an operator. Called after the standard internal deletion
     *
     * @param int $item_id
     * @return bool
     */
    public static function deleteUser(int $item_id): bool;

}
