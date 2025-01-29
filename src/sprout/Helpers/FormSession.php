<?php
/**
 * @link      https://github.com/Karmabunny
 * @copyright Copyright (c) 2025 Karmabunny
 */

namespace Sprout\Helpers;

use Sprout\Helpers\Session;


class FormSession
{
    /**
     * Determine if given form session field or form session as a whole is set.
     *
     * @param string $session Session key
     * @param string $field Optional field name. If blank determines if any field values are set
     * @return bool
     */
    public static function isSet(string $session, ?string $field = ''): bool
    {
        Session::instance();

        if (!empty($field)) return isset($_SESSION[$session]['field_values'][$field]);
        return isset($_SESSION[$session]['field_values']);
    }



    /**
     * Adds/updates form session variable.
     *
     * @param string $session Session key
     * @param string $field Field name
     * @param mixed $value Field value
     * @return void
     */
    public static function valueAdd(string $session, string $field, mixed $value): void
    {
        Session::instance();

        $_SESSION[$session]['field_values'] ??= [];
        $_SESSION[$session]['field_values'][$field] = $value;
    }


    /**
     * Adds default form session variable, if not set.
     *
     * @param string $session Session key
     * @param string $field Field name
     * @param mixed $value Field value
     * @return void
     */
    public static function valueAddDefault(string $session, string $field, mixed $value): void
    {
        Session::instance();

        $_SESSION[$session]['field_values'] ??= [];
        if (isset($_SESSION[$session]['field_values'][$field])) return;

        $_SESSION[$session]['field_values'][$field] = $value;
    }


    /**
     * Adds form session variables. This will clobber existing values.
     *
     * @param string $session
     * @param array $fields Key value pairs [field => value, ...]
     * @return void
     */
    public static function valueAddAll(string $session, array $fields): void
    {
        Session::instance();

        $_SESSION[$session]['field_values'] = $fields;
    }


    /**
     * Removes value from form session variables.
     *
     * @param string $session Session key
     * @param string $field Field name
     * @return void
     */
    public static function valueRemove(string $session, string $field): void
    {
        Session::instance();

        unset($_SESSION[$session]['field_values'][$field]);
    }


    /**
     * Clears form session values.
     *
     * @param string $session Session key
     * @return void
     */
    public static function valueRemoveAll(string $session): void
    {
        Session::instance();

        unset($_SESSION[$session]['field_values']);
    }


    /**
     * Fetch form session field value.
     *
     * @param string $session
     * @param string $field Field name
     * @return mixed
     */
    public static function valueGet(string $session, string $field): mixed
    {
        Session::instance();

        return $_SESSION[$session]['field_values'][$field] ?? null;
    }


    /**
     * Fetch form session field values.
     * @param string $session
     * @return array
     */
    public static function valueGetAll(string $session): array
    {
        Session::instance();

        return $_SESSION[$session]['field_values'] ?? [];
    }


    /**
     * Adds/updates form session error.
     *
     * @param string $session Session key
     * @param string $field Field name
     * @param mixed $msg Error message
     * @return void
     */
    public static function errorAdd(string $session, string $field, mixed $msg): void
    {
        Session::instance();

        $_SESSION[$session]['field_errors'][$field] ??= [];
        $_SESSION[$session]['field_errors'][$field][] = $msg;
    }


    /**
     * Adds form session errors. This will clobber existing errors.
     *
     * @param string $session Session key
     * @param array $fields Key value pairs [field => msgs, ...]
     * @return void
     */
    public static function errorAddAll(string $session, array $fields): void
    {
        Session::instance();

        $_SESSION[$session]['field_errors'] = $fields;
    }


    /**
     * Removes error from form session variables.
     *
     * @param string $session Session key
     * @param string $field Field name
     * @return void
     */
    public static function errorRemove(string $session, string $field): void
    {
        Session::instance();

        unset($_SESSION[$session]['field_errors'][$field]);
    }


    /**
     * Clears form session errors.
     *
     * @param string $session Session key
     * @return void
     */
    public static function errorRemoveAll(string $session): void
    {
        Session::instance();

        unset($_SESSION[$session]['field_errors']);
    }


    /**
     * Fetch form field error.
     *
     * @param string $session
     * @param string $field
     * @return array
     */
    public static function errorGet(string $session, string $field): array
    {
        Session::instance();

        return $_SESSION[$session]['field_errors'][$field] ?? [];
    }


    /**
     * Fetch all form session errors.
     *
     * @param string $session
     * @return array
     */
    public static function errorGetAll(string $session): array
    {
        Session::instance();

        return $_SESSION[$session]['field_errors'] ?? [];
    }


    /**
     * Clear form session values and errors.
     *
     * @param string $session Session key
     * @return void
     */
    public static function removeAll(string $session): void
    {
        Session::instance();

        unset($_SESSION[$session]['field_values']);
        unset($_SESSION[$session]['field_errors']);
    }
}
