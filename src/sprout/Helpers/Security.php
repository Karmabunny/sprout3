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
use karmabunny\kb\Secrets;
use Kohana;
use Kohana_404_Exception;
use Kohana_Exception;
use Sprout\Exceptions\SignatureInvalidException;


/**
 * Functions for implementing security, including secure random numbers
 */
class Security
{

    /**
     * A non-clashy name for the honeypot field
     */
    private static $honeypot_field = 'terms_special_set_143';


    /**
     *
     * @return Secrets
     * @throws Kohana_Exception
     */
    public static function getSecretSanitizer(): Secrets
    {
        static $secrets;

        if (!$secrets) {
            $config = Kohana::config('secrets', false, false) ?: [];
            $secrets = Secrets::create($config);
        }

        return $secrets;
    }


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


    /**
     * Return the server key
     *
     * @throws InvalidArgumentException Config option is not set
     * @throws InvalidArgumentException Test-server only value used in production
     * @return string Unqiue key for this site
     */
    protected static function getServerKey()
    {
        $server_key = Kohana::config('database.server_key');
        if (empty($server_key)) {
            throw new InvalidArgumentException('Config "database.server_key" not set');
        }
        if (IN_PRODUCTION and $server_key === 'NOT SECURE') {
            throw new InvalidArgumentException('Config "database.server_key" set to test-server only value');
        }
        return $server_key;
    }


    /**
     * Generate a signature from a given set of fields, using the server key
     *
     * For a given set of fields, the signature will always be the same value.
     * Returned signatures are always URL and HTML safe
     *
     * @example
     *     // In method which creates link to resource/download
     *     $sig = Security::serverKeySign(['id' => $id]);
     *     $file_url = "resource/download?id={$id}&sig={$sig}";
     *
     * @param array $fields Key-value fields making up the data to sign
     * @return string Signature of the data, always url safe
     */
    public static function serverKeySign(array $fields)
    {
        sort($fields);
        $data = http_build_query($fields);
        $data = strtolower($data);

        $key = self::getServerKey();

        return hash_hmac('sha1', $data, $key);
    }


    /**
     * Verify a signature which was generated by {@see Security::serverKeySign}
     *
     * @example
     *     // In resource::download method
     *     Security::serverKeyVerify(['id' => $id], $_GET['sig']);
     *
     * @param array $fields Key-value fields making up the data to verify
     * @param string $signature Incoming signature to check
     * @throws SignatureInvalidException A non-string value was specified for the signature
     * @throws SignatureInvalidException If the signature is not valid
     * @return void
     */
    public static function serverKeyVerify(array $fields, $signature)
    {
        // Only in dev. This protects test/qa/live.
        if (SITES_ENVIRONMENT == 'dev' and $signature == 'DEBUG') {
            return;
        }

        if (!is_string($signature)) {
            throw new SignatureInvalidException('Signature not valid');
        }

        $expected = self::serverKeySign($fields);
        $sig_valid = self::compareStrings($expected, $signature);
        if (!$sig_valid) {
            throw new SignatureInvalidException('Signature not valid');
        }
    }


    /**
     * Check the given password meets complexity requirements
     *
     * @param string $str String to check
     * @param int $length Minimum length in bytes
     * @param int $classes Minumum number of "character classes", so 2 would accept 'passWORD' but not 'password'
     * @param bool $bad_list SHould the password be checked against the "bad list" of most common passwords
     * @return array Errors, may be an empty array
     */
    public static function passwordComplexity($str, $length, $classes, $bad_list)
    {
        $errs = [];

        if (strlen($str) < $length) {
            $errs[] = "Too short, minimum length {$length} characters";
        }

        if ($classes > 1) {
            $num = 0;
            if (preg_match('/[a-z]/', $str)) $num += 1;
            if (preg_match('/[A-Z]/', $str)) $num += 1;
            if (preg_match('/[0-9]/', $str)) $num += 1;
            if (preg_match('/[^a-zA-Z0-9]/', $str)) $num += 1;
            if ($num < $classes) {
                $errs[] = "Need {$classes} character types (lowercase, uppercase, numbers, symbols)";
            }
        }

        if ($bad_list) {
            $bad_passwords = file(APPPATH . 'config/bad_passwords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($bad_passwords as $bad_pass) {
                // Ignore licence at start of file
                if ($bad_pass[0] == '/') {
                    continue;
                }

                if (strcmp($bad_pass, $str) == 0) {
                    $errs[] = 'Matches a very common password';
                    break;
                }
            }
        }

        return $errs;
    }


    /**
     * Get the field name for a honeypot trap. Optionally override via config
     *
     * Add a config called 'honeypot.php' with an index value for 'field_name' to override the default
     *
     * @return string
     */
    private static function honeypotFieldName(): string
    {
        try {
            return Kohana::config('honeypot.field_name');
        } catch (Exception $e) {
            return self::$honeypot_field;
        }
    }


    /**
     * Drop a honeypot form field
     */
    public static function honeypotSet()
    {
        $field_name = self::honeypotFieldName();
        echo sprintf('<input type="text" name="%s" class="-vis-hidden" tabindex="-1" autocomplete="false">', $field_name);
    }


    /**
     * See if anything stuck to the honey
     *
     * @param string $method Optional form method (POST|GET). Default of POST
     *
     * @return bool Validation flag - false if honeypot was triggered
     */
    public static function honeypotCheck($method = 'POST')
    {
        if (empty($method) or !in_array(strtoupper($method), array('POST', 'GET'))) $method = 'POST';

        switch ($method)
        {
            case 'POST':
                $value = $_POST[self::honeypotFieldName()] ?? null;
                break;

            case 'GET':
                $value = $_GET[self::honeypotFieldName()] ?? null;
                break;
        }

        if (!empty($value)) {
            return false;
        }


        return true;
    }


    /**
     * See if anything stuck to the honey. Explode if it did
     *
     * @param string $method Optional form method (POST|GET). Default of POST
     *
     * @return void Throws error
     */
    public static function honeypotCheckOrDie($method = 'POST')
    {
        $passed = self::honeypotCheck($method);

        if (!$passed) {
            throw new Kohana_404_Exception();
        }
    }

}
