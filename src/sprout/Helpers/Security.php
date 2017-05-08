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

use InvalidArgumentException;
use Exception;


/**
 * Functions for implementing security, including secure random numbers
 */
class Security
{

    /**
     * Returns a binary string of random bytes
     *
     * @param int $length
     * @return string Binary string
     */
    public static function randBytes($length)
    {
        $length = (int) $length;
        if ($length < 8) {
            throw new InvalidArgumentException('Insufficient length; min is 8 bytes');
        }

        if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
            return random_bytes($length);
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $strong = false;
            $rand = openssl_random_pseudo_bytes($length, $strong);
            if ($strong) {
                return $rand;
            }
        }

        if (function_exists('mcrypt_create_iv')) {
            return mcrypt_create_iv($length, MCRYPT_DEV_RANDOM);
        }

        throw new Exception('A secure random implementation is not available');
    }


    /**
     * Return a single random byte
     *
     * @return string Binary string; one byte
     */
    public static function randByte()
    {
        static $buffer = [];
        if (count($buffer) === 0) {
            $buffer = str_split(self::randBytes(256));
        }
        return array_pop($buffer);
    }


    /**
     * Returns a string of random characters
     *
     * @param int $length
     * @return string
     */
    public static function randStr($length = 16, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
    {
        $num_chars = strlen($chars) * 1.0;
        $mask = 256 - (256 % $num_chars);

        $result = '';
        do {
            $val = self::randByte();
            if (ord($val) >= $mask) {
                continue;
            }
            $result .= $chars[ord($val) % $num_chars];
        } while (strlen($result) < $length);

        return $result;
    }


    /**
     * Constant-time string comparison
     *
     * @param string $known_string The known hash
     * @param string $user_string The user supplied hash to check
     * @return bool True if the strings match, false if they don't
     */
    public static function compareStrings($known_string, $user_string)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($known_string, $user_string);
        } else {
            $ret = strlen($known_string) ^ strlen($user_string);
            $ret |= array_sum(unpack("C*", $known_string ^ $user_string));
            return !$ret;
        }
    }

}
