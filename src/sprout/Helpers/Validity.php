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

use Kohana;

use Sprout\Exceptions\RowMissingException;
use Sprout\Exceptions\ValidationException;


/**
 * New validation class for Sprout 3.
 * All of its methods should give useful errors by throwing a {@see ValidationException}.
 * Used with the {@see Validator} class.
 */
class Validity
{

    /**
     * Checks the length of a string is within an allowed range
     *
     * @example
     *    $valid->check('name', 'Validity::length', 1, 100)
     *
     * @param string $val The value
     * @param int $min Minimum length
     * @param int $max Maximum length
     * @throws ValidationException If item is too short or too long
     */
    public static function length($val, $min, $max = PHP_INT_MAX)
    {
        $len = mb_strlen($val);
        if ($len < $min) {
            throw new ValidationException("Shorter than minimum allowed length of {$min}");
        }
        if ($len > $max) {
            throw new ValidationException("Longer than maximum allowed length of {$max}");
        }
    }


    /**
     * Validate email, commonly used characters only
     *
     * @example
     *    $valid->check('email', 'Validity::email')
     *
     * @param string email address
     * @throws ValidationException
     */
    public static function email($val)
    {
        $regex = '/^[-_a-z0-9\'+*$^&%=~!?{}]++(?:\.[-_a-z0-9\'+*$^&%=~!?{}]+)*+@(?:(?![-.])[-a-z0-9.]+(?<![-.])\.[a-z]{2,6}|\d{1,3}(?:\.\d{1,3}){3})(?::\d++)?$/iD';

        if (!preg_match($regex, $val)) {
            throw new ValidationException('Invalid email address');
        }
    }


    /**
     * Validate password by length, type of characters, and list of common passwords
     *
     * @example
     *    $valid->check('password', 'Validity::password')
     *
     * @param string $val Password to validate
     * @throws ValidationException
     */
    public static function password($val)
    {
        $length = (int) Kohana::config('sprout.password_length');
        if ($length < 6) $length = 6;

        $classes = Kohana::config('sprout.password_classes');
        if (!is_int($classes)) $classes = 2;

        $bad_list = Kohana::config('sprout.password_bad_list');
        if (!is_bool($bad_list)) $bad_list = true;

        $errs = Security::passwordComplexity($val, $length, $classes, $bad_list);

        if (count($errs) > 0) {
            throw new ValidationException(
                ucfirst(strtolower(implode('; ', $errs)))
            );
        }
    }


    /**
     * Checks if a phone number is valid.
     *
     * @example
     *    $valid->check('mobile', 'Validity::phone', 10)
     *
     * @param string $val Phone number
     * @param int $min_digits Minimum number of digits required in phone number.
     *        This can be less than 8 for fields which allow short numbers like 000 or 13 11 66
     * @throws ValidationException
     */
    public static function phone($val, $min_digits = 8)
    {
        $min_digits = (int) $min_digits;
        if ($min_digits <= 0) $min_digits = 8;

        // Allow international numbers starting with + and country code, e.g. +61 for Australia
        $clean = preg_replace('/^\+[0-9]+ */', '', $val);

        // Allow area code in parentheses, e.g. in Australia (08) or Mexico (01 55)
        $clean = preg_replace('/^\(([0-9]+(?: [0-9]+)*)\)/', '$1', $clean);

        // Allow all kinds of different digit separation:
        // space (AU), dash - (US), dot . and slash / (crazy Belgians)
        if (preg_match('#[^\- 0-9/\.]#', $clean)) {
            if (preg_match('#[\+\(\)]#', $clean)) {
                throw new ValidationException("Invalid format");
            }
            throw new ValidationException("Contains invalid characters");
        }

        // Check length meets the minimum requirement
        $len = strlen(preg_replace('/[^0-9]/', '', $val));
        if ($len < $min_digits) {
            throw new ValidationException("Must contain at least {$min_digits} digits");
        }
        if ($len > 15) {
            throw new ValidationException("Cannot contain more than 15 digits");
        }
    }


    /**
     * Checks if a value is a positive integer
     *
     * @example
     *    $valid->check('region_id', 'Validity::positiveInt')
     *
     * @param string $val Value to check
     * @throws ValidationException
     */
    public static function positiveInt($val)
    {
        if (preg_match('/[^0-9]/', $val)) {
            throw new ValidationException("Value must be a whole number that is greater than zero");
        }

        $int = (int) $val;
        if ($int <= 0) {
            throw new ValidationException("Value must be greater than zero");
        }
    }


    /**
     * Checks whether a string is made up of the kinds of characters that make up prose
     *
     * Allowed: letters, numbers, space, punctuation
     * Allowed punctuation:
     *    ' " / ! ? @ # $ % & ( ) - : ; . ,
     *
     * @example
     *    $valid->check('name', 'Validity::proseText')
     *
     * @param string $str
     * @throws ValidationException
     */
    public static function proseText($str)
    {
        // pL = letters, pN = numbers
        if (preg_match('/[^-\pL\pN \'"\/!?@#$%&():;.,]/u', (string) $str)) {
            throw new ValidationException('Non prose characters found');
        }
    }


    /**
     * Checks if a value is a date in MySQL format (YYYY-MM-DD)
     *
     * @example
     *    $valid->check('date_published', 'Validity::dateMySQL')
     *
     * @param string $val Value to check
     * @throws ValidationException
     */
    public static function dateMySQL($val)
    {
        $matches = null;
        if (!preg_match('/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/', $val, $matches)) {
            throw new ValidationException('Invalid date format');
        }

        if ($matches[1] < 1900 or $matches[1] > 2100) {
            throw new ValidationException('Year is outside of range of 1900 to 2100');
        }

        if ($matches[2] < 1 or $matches[2] > 12) {
            throw new ValidationException('Month is outside of range of 1 to 12');
        }

        if ($matches[3] < 1 or $matches[3] > 31) {
            throw new ValidationException('Day is outside of range of 1 to 31');
        }
    }


    /**
     * Checks if a value is a time in MySQL format (HH:MM:SS)
     *
     * @example
     *    $valid->check('event_time', 'Validity::timeMySQL')
     *
     * @param string $val Value to check
     * @throws ValidationException
     */
    public static function timeMySQL($val)
    {
        $matches = null;
        if (!preg_match('/^([0-9]{2}):([0-9]{2}):([0-9]{2})$/', $val, $matches)) {
            throw new ValidationException('Invalid time format');
        }

        if ($matches[1] < 0 or $matches[1] > 23) {
            throw new ValidationException('Hour is outside of range of 0 to 23');
        }

        if ($matches[2] < 0 or $matches[2] > 59) {
            throw new ValidationException('Minute is outside of range of 0 to 59');
        }

        if ($matches[3] < 0 or $matches[3] > 59) {
            throw new ValidationException('Second is outside of range of 0 to 59');
        }
    }


    /**
     * Checks if a value is a datetime in MySQL format (YYYY-MM-DD HH:MM:SS)
     *
     * @example
     *    $valid->check('start_date', 'Validity::datetimeMySQL')
     *
     * @param string $val Value to check
     * @throws ValidationException
     */
    public static function datetimeMySQL($val)
    {
        $matches = null;
        if (!preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}) ([0-9]{2}:[0-9]{2}:[0-9]{2})$/', $val, $matches)) {
            throw new ValidationException('Invalid datedate format');
        }

        self::dateMySQL($matches[1]);
        self::timeMySQL($matches[2]);
    }


    /**
     * Checks that a unique value doesn't already exist in the database, e.g. a username or email address.
     * This is to give a friendlier frontend to DB errors pertaining to UNIQUE constraints.
     * N.B. this function uses LIKE for case-insensitive matching, so it's even stricter than a UNIQUE constraint.
     *
     * @example
     *    $valid->check('code', 'Validity::uniqueValue', 'events', 'code')
     *
     * @example
     *    $valid->check('email', 'Validity::uniqueValue', 'users', 'email', UserAuth::get_id())
     *
     * @param string $val Value to check
     * @param string $table Table to search for an extant matching value
     * @param string $column Column in specified table
     * @param int $id The ID of the record being edited (0 if adding a new record). This prevents an exception being
     *        thrown when the record being edited itself is found.
     * @param string $error_msg Error message to be included in the exception
     * @throws ValidationException
     * @throws InvalidArgumentException If $table or $column is invalid
     */
    public static function uniqueValue($val, $table, $column, $id = 0, $error_msg = '')
    {
        Pdb::validateIdentifier($table);
        Pdb::validateIdentifier($column);
        $id = (int) $id;

        $q = "SELECT id FROM ~{$table} WHERE {$column} LIKE ? AND id != ?";
        $res = Pdb::q($q, [Pdb::likeEscape($val), $id], 'arr');
        if (count($res) > 0) {
            if (!$error_msg) $error_msg = 'Must be a unique value';
            throw new ValidationException($error_msg);
        }
    }


    /**
     * Checks all selected values belong to a database SET definition
     *
     * @example
     *    $valid->check('days', 'Validity::allInSet', 'events', 'days')
     *
     * @param array $val The value
     * @param string $table The DB table which contains the SET column
     * @param string $col The SET column
     * @throws Exception If the data isn't an array or the column isn't an ENUM or SET
     * @throws ValidationException If item is too short or too long
     */
    public static function allInSet(array $val, $table, $col) {
        $set = Pdb::extractEnumArr($table, $col);
        foreach ($val as $choice) {
            if (!in_array($choice, $set)) {
                throw new ValidationException("Invalid value: {$choice}");
            }
        }
    }


    /**
     * At least one value must be specified (e.g. one of email/phone/mobile)
     *
     * @example
     *    $valid->multipleCheck(['email', 'phone'], 'Validity::oneRequired')
     *
     * @param array $vals Values to check
     * @throws ValidationException
     */
    public static function oneRequired(array $vals)
    {
        foreach ($vals as $v) {
            if (is_array($v) and count($v) > 0) {
                return;
            } else if ($v != '') {
                return;
            }
        }

        throw new ValidationException("At least one of these must be provided");
    }


    /**
     * All field values must match (e.g. password1 and password2 must match)
     *
     * @example
     *    $valid->multipleCheck(['password1', 'password2'], 'Validity::allMatch')
     *
     * @param array $vals Values to check
     * @throws ValidationException
     */
    public static function allMatch(array $vals)
    {
        $unique = array_unique($vals);
        if (count($unique) > 1) {
            throw new ValidationException("Provided values do not match");
        }
    }



    /**
     * All field values must be unique (e.g. home phone and work phone cannot be the same)
     *
     * @example
     *    $valid->multipleCheck(['home_phone', 'work_phone'], 'Validity::allUnique')
     *
     * @param array $vals Values to check
     * @throws ValidationException
     */
    public static function allUnique(array $vals)
    {
        $unique = array_unique($vals);
        if (count($unique) != count($vals)) {
            throw new ValidationException("Provided values must not be the same");
        }
    }


    /**
     * Checks a value is one of the allowed values
     *
     * @example
     *    $valid->check('vowel', 'Validity::inArray', ['a', 'e', 'i', 'o', 'u'])
     *
     * @param string $val
     * @param array $allowed
     * @throws ValidationException
     */
    public static function inArray($val, array $allowed)
    {
        if (!in_array($val, $allowed)) {
            throw new ValidationException('Invalid value');
        }
    }


    /**
     * Checks each value of an array is one of the allowed values
     *
     * @example
     *    $_POST['vowels'] = ['a', 'i']
     *    $valid->check('vowel', 'Validity::allInArray', ['a', 'e', 'i', 'o', 'u'])
     *
     * @param string $val
     * @param array $allowed
     * @throws ValidationException
     */
    public static function allInArray(array $val, array $allowed)
    {
        if (count(array_diff($val, $allowed)) > 0) {
            throw new ValidationException('Invalid value');
        }
    }


    /**
     * Checks a value matches an ID in a corresponding table
     *
     * @example
     *    $valid->check('user_id', 'Validity::inTable', 'users')
     *
     * @param string $val
     * @param string $table
     * @return void
     * @throws ValidationException
     */
    public static function inTable($val, $table)
    {
        // Should be required if an empty (null/zero) value isn't allowed
        if ($val == '') return;

        try {
            Pdb::validateIdentifier($table);
            $q = "SELECT id FROM ~{$table} WHERE id = ?";
            Pdb::query($q, [$val], 'val');
        } catch (RowMissingException $ex) {
            throw new ValidationException('Invalid value');
        }
    }


    /**
     * Checks all selected values match IDs in a corresponding table
     *
     * @example
     *    $_POST['favourite_cities'] = (array) @$_POST['favourite_cities']
     *    $valid->check('favourite_cities', 'Validity::allInTable', 'cities')
     *
     * @param array $val
     * @param string $table
     * @return void
     * @throws ValidationException
     */
    public static function allInTable(array $val, $table)
    {
        // Should be required if empty value isn't allowed
        if (count($val) == 0) return;

        // Extract IDs from autofill list submissions
        // This is a stop-gap measure until autofill list is reworked to only submit ID values
        foreach ($val as &$el) {
            if (is_array($el)) {
                if (!isset($el['id'])) {
                    throw new ValidationException('Invalid value');
                }
                $el = $el['id'];
            }
        }

        $val = array_unique($val);

        $found_ids = [];
        try {
            Pdb::validateIdentifier($table);
            $params = [];
            $where = Pdb::buildClause([['id', 'IN', $val]], $params);

            $q = "SELECT id FROM ~{$table} WHERE {$where}";
            $found_ids = Pdb::query($q, $params, 'col');
        } catch (RowMissingException $ex) {
        }

        if (count($val) != count($found_ids)) {
            throw new ValidationException('Invalid value');
        }
    }


    /**
     * Checks that a value is numeric (integral or decimal)
     *
     * @example
     *    $valid->check('cost', 'Validity::numeric')
     *
     * @param string $val
     * @throws ValidationException
     */
    public static function numeric($val)
    {
        if (!is_numeric($val)) {
            throw new ValidationException('Value must be a number');
        }
    }


    /**
     * Checks that a value is binary; either a '1' or a '0'.
     *
     * @example
     *    $valid->check('active', 'Validity::binary')
     *
     * @param string $val
     * @throws ValidationException
     */
    public static function binary($val)
    {
        if ($val !== '1' and $val !== 1 and $val !== '0' and $val !== 0) {
            throw new ValidationException('Value must be a "1" or "0"');
        }
    }


    /**
     * Checks that a value is numeric (integral or decimal) and within a given inclusive range
     *
     * @example
     *    $valid->check('cost', 'Validity::range', 0, 5000)
     *
     * @param string $val
     * @param number $min The minimum the value may be
     * @param number $max The maximum the value may be
     * @throws ValidationException
     */
    public static function range($val, $min, $max)
    {
        static::numeric($val);

        if ($val < $min or $val > $max) {
            throw new ValidationException("Value must be no less than {$min} and no greater than {$max}");
        }
    }



    /**
     * Validates a value meant for an ENUM field
     *
     * @param string $val
     * @param string $table The DB table which contains the ENUM column
     * @param string $col The ENUM column
     * @return bool
     */
    public static function inEnum($val, $table, $col)
    {
        $enum = Pdb::extractEnumArr($table, $col);
        if (in_array($val, $enum)) return true;
        throw new ValidationException("Invalid value");
    }

    /**
     * Checks that a date range is valid.
     *
     * @example
     *    $valid->multipleCheck(['date_start', 'date_end'], 'Validity::dateRange', '1999-01-01', '2099-01-01')
     *
     * @param array $vals The values to check; there must be exactly two with the 'start' field name occuring first in the array
     * @param string $min (optional) A date string (compatible with strtotime) for the minimum of the date range.
     * @param string $max (optional) A date string (compatible with strtotime) for the maximum of the date range.
     * @param bool $enforce_ordering (optional) Ensures that the start date is less than or equal to the end date. On by default.
     */
    public static function dateRange(array $vals, $min = null, $max = null, $enforce_ordering = true)
    {
        if (count($vals) != 2) {
            throw new InvalidArgumentException('Incorrect number of fields. A date range must only contain two dates: a start and an end date.');
        }

        list ($date_start, $date_end) = $vals;

        static::dateMySQL($date_start);
        static::dateMySQL($date_end);

        $ts_start = strtotime($date_start);
        $ts_end = strtotime($date_end);

        // Ideally we'd just switch the values around but that isn't possible
        if ($enforce_ordering and $ts_start > $ts_end) {
            throw new ValidationException("The start date, {$date_start}, cannot be later than the end date {$date_end}");
        }

        if ($min) {
            $ts_min = strtotime($min);

            if ($ts_start < $ts_min) {
                throw new ValidationException("The start of this date range is outside the minimum of {$min}");
            }
        }

        if ($max) {
            $ts_max = strtotime($max);

            if ($ts_end > $ts_max) {
                throw new ValidationException("The end of this date range is outside the maximum of {$max}");
            }
        }
    }


    /**
     * Checks that a value matches a regular expression
     * @param string $val value
     * @param string $pattern Regex pattern for preg_match. Consider starting with /^ and ending with $/
     * @return void
     * @throws ValidationException If the value doesn't match the pattern
     */
    public static function regex($val, $pattern)
    {
        if (!preg_match($pattern, $val)) {
            throw new ValidationException('Incorrect format');
        }
    }


    /**
     * Checks that a value is a valid IPv4 address
     * @param string $val value
     * @return void
     * @throws ValidationException If the value isn't a valid IPv4 address
     */
    public static function ipv4Addr($val)
    {
        if (!preg_match('/^[0-9]+(?:\.[0-9]+){3}$/', $val)) {
            throw new ValidationException('Invalid IP address');
        }

        $parts = explode('.', $val);
        foreach ($parts as $part) {
            $part = (int) $part;
            if ($part > 255) throw new ValidationException('Invalid IP address');
        }
    }


    /**
     * Checks that a value is a valid IPv4 CIDR block
     * @param string $val value
     * @return void
     * @throws ValidationException If the value isn't a valid IPv4 CIDR block
     */
    public static function ipv4Cidr($val)
    {
        if (strpos($val, '/') === false) {
            throw new ValidationException('Invalid CIDR block');
        }

        list($ip, $mask) = explode('/', $val, 2);
        self::ipv4Addr($ip);

        if (!preg_match('/^[0-9]{1,2}$/', $mask)) {
            throw new ValidationException('Invalid network mask');
        }
        $mask = (int) $mask;
        if ($mask > 32) {
            throw new ValidationException('Invalid network mask');
        }
    }


    /**
     * Checks that a value is a valid IPv4 address or CIDR block
     * @param string $val value
     * @return void
     * @throws ValidationException If the value isn't a valid IPv4 address or CIDR block
     */
    public static function ipv4AddrOrCidr($val)
    {
        if (strpos($val, '/') === false) {
            self::ipv4Addr($val);
        } else {
            self::ipv4Cidr($val);
        }
    }

}
