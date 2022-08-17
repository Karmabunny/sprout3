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

use Kohana;

use karmabunny\pdb\Exceptions\QueryException;


/**
* Provides user authentication functions for the admin
**/
class AdminAuth extends Auth
{
    const KEY = 'admin';
    const LOGIN_URL = 'admin/login';

    /**
     * @var array A cache of categories the current operator is a member of, populated on demand
     */
    private static $category_cache = [];

    /**
    * If the user is not logged, redirect them to a login page
    **/
    public static function checkLogin()
    {
        if (! self::isLoggedIn()) {
            $redirect = Enc::url(Url::current());

            if (Router::$controller == 'admin' and Router::$method == 'index') {
                $redirect = null;
            }

            if ($redirect && $redirect !== "admin") Notification::error('You need to be logged in to access this part of the site');
            Url::redirect (self::LOGIN_URL . '?redirect=' . $redirect);
        }
    }

    /**
    * Check if the user is logged in or not
    *
    * @return boolean True if the user is logged in, false otherwise
    **/
    public static function isLoggedIn()
    {
        Session::instance();
        if (!empty($_SESSION[self::KEY]['login_id'])) {
            return true;
        }

        return false;
    }


    /**
    * Processes the login by a operator with the specified username and password
    *
    * @param string $username The username to attempt login with
    * @param string $password The password to attempt login with
    * @return boolean True on success, false on failure
    **/
    public static function processLogin($username, $password)
    {
        Session::instance();

        $q = "SELECT id, password, password_algorithm AS algorithm, password_salt AS salt, tfa_method
            FROM ~operators
            WHERE username LIKE ?";

        try {
            $admin = Pdb::q($q, [Pdb::likeEscape($username)], 'row');
        } catch (Exception $ex) {
            return false;
        }

        // Check IP restrictions in categories the operator belongs to
        $q = "SELECT cat.allowed_ips
            FROM ~operators_cat_join AS joiner
            INNER JOIN ~operators_cat_list AS cat ON joiner.cat_id = cat.id
            WHERE joiner.operator_id = ?";
        $ip_lists = Pdb::q($q, [$admin['id']], 'col');
        foreach ($ip_lists as $ip_list) {
            $ips = preg_split('/,\s*/', $ip_list);
            $ips = array_filter($ips);
            if (count($ips) == 0) continue;
            if (!Sprout::ipaddressInArray(Request::userIp(), $ips)) {
                return false;
            }
        }

        // Password algorithm supported?
        if (! AdminAuth::checkAlgorithm($admin['algorithm'])) {
            $err = 'Unable to login - unsupported password hash algorithm. This is a server configuration error.';
            throw new Exception($err);
        }

        // Password correct?
        if (!self::doPasswordCheck($admin['password'], $admin['algorithm'], $admin['salt'], $password)) {
            return false;
        }

        // If the operator has 2FA enabled then don't log them in yet, but the id is required
        if ($admin['tfa_method'] !== 'none') {
            $_SESSION[self::KEY]['tfa_id'] = $admin['id'];
        } else {
            $_SESSION[self::KEY]['login_id'] = $admin['id'];
        }

        $_SESSION[self::KEY]['super'] = false;
        $_SESSION[self::KEY]['remote'] = false;
        $_SESSION[self::KEY]['lock_key'] = Admin::createLockKey();

        // If the default algorithm has changed, upgrade the password while the plaintext is on hand
        $default_algorithm = self::defaultAlgorithm();
        if ($admin['algorithm'] != $default_algorithm) {
            self::changePassword($password, $admin['id']);
        }

        return true;
    }


    /**
    * Checks the password on the database matches the one provided
    * For re-authenticating certain actions of logged in operators
    **/
    public static function checkPassword($password, $operator_id = null)
    {
        $operator_id = (int) $operator_id;

        if (! $operator_id) {
            Session::instance();
            if (! self::isLoggedIn()) return false;
            $operator_id = $_SESSION[self::KEY]['login_id'];
        }

        $q = "SELECT password, password_algorithm, password_salt
            FROM ~operators
            WHERE id = ?";
        try {
            $op = Pdb::q($q, [$operator_id], 'row');
        } catch (QueryException $ex) {
            return false;
        }

        if (!self::doPasswordCheck($op['password'], $op['password_algorithm'], $op['password_salt'], $password)) {
            return false;
        }

        return true;
    }


    /**
    * Stub function for future development using OpenID
    *
    * @param string $openid The openid username url
    * @return boolean True on success, false on failure
    **/
    public static function processOpenid($openid)
    {
        return false;
    }


    /**
     * Process a local (developer) login, with details stored in a config file
     *
     * @param string $username The username to attempt login with
     * @param string $password The password to attempt login with
     * @return boolean True on success, false on failure
     */
    public static function processLocal($username, $password)
    {
        if ($password == '') return false;

        try {
            $super_users = Kohana::config('super_ops.operators');
        } catch (Exception $ex) {
            return false;
        }

        foreach ($super_users as $user => $details) {
            if ($user != $username) continue;
            if (!self::doPasswordCheck($details['hash'], Constants::PASSWORD_BCRYPT12, $details['salt'], $password)) continue;

            $uid = $details['uid'];
            Session::instance();
            $_SESSION[self::KEY]['super'] = true;
            $_SESSION[self::KEY]['remote'] = false;
            $_SESSION[self::KEY]['login_id'] = $uid;
            $_SESSION[self::KEY]['login_user'] = $user;
            $_SESSION[self::KEY]['lock_key'] = Admin::createLockKey();
            return true;
        }
        return false;
    }


    /**
     * Load the existing super-operators list from config, inject another operator, return new array
     *
     * @param string $username The username to add or edit
     * @param string $pass_hash The password hash, as generated by {@see Auth::hashPassword}
     * @param string $pass_salt The password salt, as generated by {@see Auth::hashPassword}
     * @return array New users array
     */
    public static function injectLocalSuperConf($username, $pass_hash, $pass_salt)
    {
        try {
            $users = Kohana::config('super_ops.operators');
            if (!isset($users)) $users = [];
        } catch (Exception $ex) {
            $users = [];
        }

        if (isset($users[$username])) {
            // Update existing user
            $users[$username]['hash'] = $pass_hash;
            $users[$username]['salt'] = $pass_salt;
        } else {
            // Determine user ID and add new user
            $uid = 99999;
            $q = "SELECT MAX(id) FROM ~operators";
            $max_op_id = (int) Pdb::q($q, [], 'val');
            $uid = max($uid, $max_op_id);

            foreach ($users as $user) {
                $uid = max($uid, $user['uid']);
            }
            ++$uid;

            $users[$username] = ['uid' => $uid, 'hash' => $pass_hash, 'salt' => $pass_salt];
        }

        return $users;
    }


    /**
    * Process a remote (developer) login, as provided by the external web service
    *
    * @param string $username The username to attempt login with
    * @param string $password The password to attempt login with
    * @return boolean True on success, false on failure
    **/
    public static function processRemote($username, $password)
    {
        if (!SERVER_ONLINE) return false;

        if ($remote = Services::getRemoteAuth()) {
            $uid = $remote::process([
                'username' => $username,
                'password' => $password,
                'ip' => Request::userIp(),
                'user_agent' => trim(@$_SERVER['HTTP_USER_AGENT']),
            ]);

            if (!$uid) return false;

            $_SESSION[self::KEY]['super'] = true;
            $_SESSION[self::KEY]['remote'] = true;
            $_SESSION[self::KEY]['login_id'] = $uid;
            $_SESSION[self::KEY]['lock_key'] = Admin::createLockKey();

            return true;
        }

        return false;
    }


    /**
    * Sets the password for a operator, or the current operator if a operator-id is not specified.
    *
    * @param string $new_password The new password.
    * @param int $operator_id The operator to update. If not specified, the currently logged in operator is used.
    **/
    public static function changePassword($new_password, $operator_id = null)
    {
        $operator_id = (int) $operator_id;

        if (! $operator_id) {
            Session::instance();
            if (! self::isLoggedIn()) return false;
            $operator_id = $_SESSION[self::KEY]['login_id'];
        }

        $new_password = trim($new_password);
        if ($new_password == '') return false;

        $hashed = self::hashPassword($new_password);
        if (! $hashed) throw new Exception('Password hashing failed');

        list($hash, $algorithm, $salt) = $hashed;

        $data = ['password' => $hash, 'password_algorithm' => $algorithm, 'password_salt' => $salt];
        Pdb::update('operators', $data, ['id' => $operator_id]);

        return true;
    }


    /**
     * Does a rate-limit check for admin logins against the login_attempts table
     *
     * @return array If the rate limit has been hit. Keys: 0 => problematic field, 1 => max rate
     * @return bool True if things are OK and the rate limit hasn't yet been hit
     */
    public static function checkRateLimit($username, $ip)
    {
        $username = trim($username);
        $ip = bin2hex(inet_pton(trim($ip)));

        $rate_limits = Kohana::config('sprout.auth_rate_limit');
        try {
            // Limit the username to 10 per hour
            $res = Sprout::checkInsertRate('login_attempts', 'username', $username, $rate_limits['username'], 3600, ['success' => 0]);
            if (! $res) return array('Username', '10 per hour');

            // Limit the ip to 10 per hour
            $res = Sprout::checkInsertRate('login_attempts', 'ip', $ip, $rate_limits['ip'], 3600, ['success' => 0]);
            if (! $res) return array('IP address', '10 per hour');

        } catch (Exception $ex) {}

        return true;
    }

    /**
    * Store a login attempt (used for rate checking)
    **/
    public static function saveLoginAttempt($username, $ip, $success)
    {
        $username = trim($username);
        $ip = bin2hex(inet_pton(trim($ip)));

        $data = array();
        $data['username'] = $username;
        $data['ip'] = $ip;
        $data['success'] = $success;
        $data['date_added'] = Pdb::now();
        $data['date_modified'] = Pdb::now();

        try {
            Pdb::insert('login_attempts', $data);
        } catch (Exception $ex) {}
    }


    /**
    * Logs an operator out
    **/
    public static function logout()
    {
        Session::instance();
        unset($_SESSION[self::KEY]);
        return true;
    }


    /**
    * Returns the id of the currently logged in operator
    **/
    public static function getId()
    {
        Session::instance();
        return @$_SESSION[self::KEY]['login_id'];
    }


    /**
     * Fetches the ID of current operator if and only if they're a local operator, otherwise 0.
     *
     * @return int An ID if a local operator, 0 otherwise
     */
    public static function getLocalId()
    {
        Session::instance();
        return self::hasDatabaseRecord() ? @$_SESSION[self::KEY]['login_id'] : 0;
    }


    /**
     * Gets the id, name, username and email of the currently logged in operator.
     * N.B. the id will be 0 for remote users
     * @return array Under normal circumstances, with keys 'id', 'name', 'username', 'email' and 'editor'
     * @return bool False if fetching data for a remote operator failed
     */
    public static function getDetails()
    {
        Session::instance();

        // Local users.
        if (self::hasDatabaseRecord()) {
            $q = "SELECT id, name, username, email, '' AS editor
                FROM ~operators
                WHERE id = ?";
            return Pdb::q($q, [$_SESSION[self::KEY]['login_id']], 'row');
        }

        // Remote-authenticated super-operators.
        if (
            ($remote = Services::getRemoteAuth()) and
            $_SESSION[self::KEY]['remote']
        ) {
            // Cached result.
            if (isset($_SESSION[self::KEY]['remote_details'])) {
                return $_SESSION[self::KEY]['remote_details'];
            }

            $uid = $_SESSION[self::KEY]['login_id'];
            $user = $remote::getDetails($uid);

            // Cache the result for later
            $_SESSION[self::KEY]['remote_details'] = $user;
            return $user;
        }

        // A local super operator.
        return [
            'id' => $_SESSION[self::KEY]['login_id'],
            'name' => $_SESSION[self::KEY]['login_user'] . ' (super-op)',
            'username' => $_SESSION[self::KEY]['login_user'],
            'email' => '',
            'editor' => '',
        ];
    }



    /**
    * Returns true if the currently logged in user is in the specified category.
    * Always returns true for remotely-logged in users.
    *
    * @param int $category_id The category to check
    **/
    public static function inCategory($category_id)
    {
        Session::instance();

        if ($_SESSION[self::KEY]['login_id'] == false) return false;
        if ($_SESSION[self::KEY]['super'] == true) return true;

        $category_id = (int) $category_id;

        if (array_key_exists($category_id, static::$category_cache)) {
            return static::$category_cache[$category_id];
        }

        $q = "SELECT 1
            FROM ~operators_cat_join
            WHERE operator_id = ? AND cat_id = ?";
        $hascat = Pdb::q($q, [$_SESSION[self::KEY]['login_id'], $category_id], 'arr');

        if (count($hascat) == 0) static::$category_cache[$category_id] = false;
        else static::$category_cache[$category_id] = true;

        return static::$category_cache[$category_id];
    }


    /**
    * Returns an array of all categories the currently logged in operator is in
    **/
    public static function getOperatorCategories()
    {
        Session::instance();

        if ($_SESSION[self::KEY]['login_id'] == false) return array();

        if (self::isSuper()) {
            return array_keys(self::getAllCategories());
        }

        $q = "SELECT cat_id
            FROM ~operators_cat_join
            WHERE operator_id = ?";
        return Pdb::q($q, [$_SESSION[self::KEY]['login_id']], 'col');
    }

    /**
    * Gets a list of all of the admin categories
    * Returned as an array of id => name
    **/
    public static function getAllCategories()
    {
        $q = "SELECT id, name FROM ~operators_cat_list ORDER BY name";
        return Pdb::q($q, [], 'map');
    }


    /**
     * A super-operator -- has access to everything (dev tools, all permissions, etc)
     *
     * @return bool True if the logged-in user is a super-operator
     */
    public static function isSuper()
    {
        Session::instance();
        return !empty($_SESSION[self::KEY]['super']);
    }


    /**
     * Does the record-id for this login correspond to a local database record?
     *
     * @return bool True if the logged-in operator has a database record
     */
    public static function hasDatabaseRecord()
    {
        Session::instance();
        return empty($_SESSION[self::KEY]['super']);
    }


    /**
     * Get the ID of the 'Primary administrators' category
     *
     * i.e. the first category with permission to manage operators
     *
     * @return int
     * @throws RowMissingException If there's no such category (in which case the system will be unuseable)
     */
    public static function getPrimaryCategoryId()
    {
        static $q = null;

        if (!$q) {
            $q = Pdb::prepare("SELECT id FROM ~operators_cat_list WHERE access_operators = 1 ORDER BY id LIMIT 1");
        }
        return Pdb::execute($q, [], 'val');
    }

}
