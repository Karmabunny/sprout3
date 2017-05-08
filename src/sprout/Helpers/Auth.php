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
use InvalidArgumentException;


/**
* Provides user authentication functions for the admin
**/
class Auth
{

    /**
     * Check a password hash against an entered password
     *
     * @param string $known_hash The known hash to check against, typically from the database
     * @param int $algorithm Password algorithm, {@see Constants}
     * @param string $salt
     * @param string $user_string Password which was entered by the user, to check against the stored hash
     * @return bool True if the password matches, false if it doesn't
     */
    public static function doPasswordCheck($known_hash, $algorithm, $salt, $user_string)
    {
        switch ($algorithm) {
            case Constants::PASSWORD_SHA:
                $expected = sha1($user_string);
                return Security::compareStrings($known_hash, $expected);

            case Constants::PASSWORD_SHA_SALT:
                $expected = sha1(sha1($salt . $user_string . $salt));
                return Security::compareStrings($known_hash, $expected);

            case Constants::PASSWORD_SHA_SALT_5000:
                $expected = $salt . $user_string . $salt;
                for ($i = 1; $i <= 5000; $i++) {
                    $expected = sha1($expected);
                }
                return Security::compareStrings($known_hash, $expected);

            case Constants::PASSWORD_BCRYPT12:
                // The entire known password is used as a salt when generating the expected hash
                $expected = crypt($user_string, $known_hash);
                return Security::compareStrings($known_hash, $expected);
        }

        return false;
    }


    /**
     * Return a hashed password, password algorithm, and salt, for inserting into the database
     *
     * @param string $password Plaintext password
     * @param int $algorithmPassword algorithm, {@see Constants}. If not specified, the default is used.
     * @return array 0 => hash, 1 => algorithm, 2 => salt
     */
    public static function hashPassword($password, $algorithm = null)
    {
        if ($algorithm == null) {
            $algorithm = self::defaultAlgorithm();
        }

        switch ($algorithm) {
            case Constants::PASSWORD_PLAIN:
            case Constants::PASSWORD_SHA:
                throw new InvalidArgumentException('Read-only password algorithm specified');
                break;

            case Constants::PASSWORD_SHA_SALT:
                $salt = Security::randStr(10);
                $hash = sha1(sha1($salt . $password . $salt));
                break;

            case Constants::PASSWORD_SHA_SALT_5000:
                $salt = Security::randStr(10);
                $hash = $salt . $password . $salt;
                for ($i = 1; $i <= 5000; $i++) {
                    $hash = sha1($hash);
                }
                break;

            case Constants::PASSWORD_BCRYPT12:
                $salt = '$2y$12$';
                $salt .= Security::randStr(22, './ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');

                $hash = crypt($password, $salt);
                if (strlen($hash) <= 12) {
                    throw new Exception('Bcrypt hashing failed');
                }

                // Unit tests check whether this field is set, but it's not used
                // so it's just set to a dummy value
                $salt = Sprout::randStr(4);
                break;

            default:
                return null;
        }

        return array($hash, $algorithm, $salt);
    }


    /**
     * Checks if a given password algorithm is available
     *
     * @param int $algorithm Password algorithm, {@see Constants}
     * @return bool True if the specified algorithm is available, False otherwise
     */
    public static function checkAlgorithm($algorithm)
    {
        switch ($algorithm) {
            case Constants::PASSWORD_SHA:
            case Constants::PASSWORD_SHA_SALT:
            case Constants::PASSWORD_PLAIN:
            case Constants::PASSWORD_SHA_SALT_5000:
                return true;

            case Constants::PASSWORD_BCRYPT12:
                return (CRYPT_BLOWFISH == 1);
        }

        return false;
    }


    /**
     * Return the algorithm for new accounts.
     * Existing accounts will be re-crypted into this algorithm upon next login.
     *
     * @return int Password algorithm, {@see Constants}
     **/
    public static function defaultAlgorithm()
    {
        if (self::checkAlgorithm(Constants::PASSWORD_BCRYPT12)) {
            return Constants::PASSWORD_BCRYPT12;
        } else {
            return Constants::PASSWORD_SHA_SALT_5000;
        }
    }

}
