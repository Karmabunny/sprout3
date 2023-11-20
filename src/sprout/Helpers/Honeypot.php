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
use Kohana;
use Kohana_404_Exception;

/**
 * Honeypots for hungry bots.
 */
class Honeypot
{

    /**
     * A non-clashy name for the honeypot field
     */
    private static $honeypot_field = 'terms_special_set_143';


    /**
     * Get the field name for a honeypot trap. Optionally override via config
     *
     * Add a config called 'honeypot.php' with an index value for 'field_name' to override the default
     *
     * @return string
     */
    private static function fieldName(): string
    {
        try {
            return Kohana::config('honeypot.field_name');
        } catch (Exception $e) {
            return self::$honeypot_field;
        }
    }


    /**
     * Drop a honeypot form field
     *
     * @return string
     */
    public static function render(): string
    {
        $field_name = self::fieldName();
        return sprintf('<input type="text" name="%s" class="-vis-hidden" tabindex="-1" autocomplete="false">', $field_name);
    }


    /**
     * See if anything stuck to the honey
     *
     * @param string $method Form method (POST|GET).
     * @return bool Validation flag - false if honeypot was triggered
     * @return void
     * @throws InvalidArgumentException
     */
    public static function check(string $method): bool
    {
        if (!in_array(strtoupper($method), ['POST', 'GET'])) {
            $error = new InvalidArgumentException('Invalid honeypot method specified');

            if (!IN_PRODUCTION) {
                throw $error;
            } else {
                Kohana::logException($error);
                return null;
            }
        }

        $fieldname = self::fieldName();

        switch ($method)
        {
            case 'POST':
                $value = $_POST[$fieldname] ?? null;
                break;

            case 'GET':
                $value = $_GET[$fieldname] ?? null;
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
     * @param string $method Form method (POST|GET).
     * @return void
     * @throws Kohana_404_Exception
     */
    public static function checkOrDie(string $method)
    {
        $passed = self::check($method);

        if (!$passed) {
            throw new Kohana_404_Exception();
        }
    }
}
