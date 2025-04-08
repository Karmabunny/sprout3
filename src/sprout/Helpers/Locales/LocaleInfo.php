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

namespace Sprout\Helpers\Locales;

use InvalidArgumentException;
use stdClass;

use Kohana;

use Sprout\Helpers\CountryConstants;
use Sprout\Helpers\Form;
use Sprout\Helpers\Validator;


/**
 * Class to handle locale-dependent data such as addresses and currency
 */
class LocaleInfo
{
    protected $state_name = 'State/Province';
    protected $state_list = null;

    protected $town_name = 'Suburb/Town';

    protected $line1 = 'Line 1';
    protected $line2 = 'Line 2';

    protected $postcode_name = 'Postcode';

    protected $decimal_seperator = '.';
    protected $group_seperator = ',';

    protected $currency_symbol = '$';
    protected $currency_decimal = 2;
    protected $currency_name = 'Dollar';
    protected $currency_iso = '';

    protected $shortdate = 'j/n/Y';
    protected $longdate = 'D jS M Y';
    protected $time = 'g:ia';


    public static $auto;

    /**
     * Automatically chooses a locale and returns it
     * @return LocaleInfo
     */
    public static function auto()
    {
        if (! self::$auto) {
            $l = Kohana::config('sprout.locale');
            if ($l == '') $l = Kohana::config('config.default_country_code');
            self::$auto = self::get($l);
        }

        return self::$auto;
    }


    /**
    * Returns the LocaleInfo class for the specified country code.
    * If no LocaleInfo can be found, a generic version is used.
    * @param string $code 3-letter country code (ISO 3166-1 alpha-3)
    * @return LocaleInfo
    */
    public static function get($code = null)
    {
        $code = strtoupper($code);
        $code = preg_replace('/[^A-Z]/', '', $code);

        $class_name = 'LocaleInfo' . $code;
        if (!file_exists(__DIR__ . "/{$class_name}.php")) {
            $class_name = 'LocaleInfo';
        }

        $class_name = __NAMESPACE__ . '\\' . $class_name;
        $locale = new $class_name();
        return $locale;
    }


    /**
     * Return the raw parameters of this locale
     */
    public function getParameters()
    {
        return [
            'decimal_seperator' => $this->decimal_seperator,
            'group_seperator' => $this->group_seperator,
            'currency_symbol' => $this->currency_symbol,
            'currency_decimal' => $this->currency_decimal,
            'currency_iso' => $this->currency_iso,
        ];
    }


    /**
     * Organises the state list so that it doesn't have numeric keys.
     *
     * The state names become the form field values if state list is a numeric array.
     * This makes it easy to configure a new locate with states/provinces/etc which don't have standard abbreviations.
     *
     * E.g. ['State 1', 'State 2'] would become: ['State 1' => 'State 1', 'State 2' => 'State 2']
     *
     * @return array
     */
    public function nonNumericStates()
    {
        $states = $this->state_list;
        reset($states);
        $key = key($states);
        $val = current($states);
        if (is_int($key) and !is_array($val)) {
            $states = array_combine($states, $states);
        }
        return $states;
    }


    /**
     * Extracts only the enterable values from the state list
     *
     * This should be used for validation.
     *
     * @return array
     */
    public function stateValues()
    {
        $states = $this->nonNumericStates();
        if (!is_array(reset($states))) {
            return array_keys($states);
        }

        // Handle grouped states, e.g. Japan's prefectures, grouped by region (Chihō)
        $keys = [];
        foreach ($states as $group) {
            foreach ($group as $key => $name) {
                $keys[] = $key;
            }
        }

        return $keys;
    }


    /**
     * Get the name of a given state by looking it up in the states list
     *
     * @param string $val Values stored by the {@see LocaleInfo::outputAddressFields} dropdown field
     * @return string Full name of state, e.g. 'South Australia'
     * @return null If the country does not have states (e.g. Vatican City)
     */
    public function getStateName($val)
    {
        if (empty($this->state_list)) return null;

        // Most common case of standard array
        if (isset($this->state_list[$val])) {
            return $this->state_list[$val];
        }

        // Handle grouped states, e.g. Japan's prefectures, grouped by region (Chihō)
        foreach ($this->state_list as $group) {
            if (isset($group[$val])) {
                return $group[$val];
            }
        }

        return $val;
    }


    /**
     * Generates the address fields for the current locale
     *
     * @param string $prefix Optional prefix, e.g. 'postal_'
     * @param bool $required True if the address as a whole is required, irrespective of individual fields
     * @return string
     */
    public function outputAddressFields($prefix = '', $required = false)
    {
        $out = '';
        foreach (['street', 'street2', 'town', 'state', 'postcode'] as $field) {
            $out .= self::outputAddressField($field, $prefix, $required);
        }
        return $out;
    }


    /**
     * Generates a single address field for the current locale
     *
     * @param string $field 'street', 'street2', 'town', 'state', or 'postcode'
     * @param string $prefix Optional prefix, e.g. 'postal_'
     * @param bool $required True if the address as a whole is required, irrespective of the individual field
     * @return string
     */
    public function outputAddressField($field, $prefix = '', $required = false)
    {
        switch ($field) {
        case 'street':
            Form::nextFieldDetails($this->line1, $required);
            return Form::text($prefix . 'street', ['-wrapper-class' => 'address-street1']);

        case 'street2':
            Form::nextFieldDetails($this->line2, false);
            return Form::text($prefix . 'street2', ['-wrapper-class' => 'address-street2']);

        case 'town':
            Form::nextFieldDetails($this->town_name, $required);
            return Form::text($prefix . 'town', ['-wrapper-class' => 'address-town']);

        case 'state':
            if ($this->state_name) {
                Form::nextFieldDetails($this->state_name, $required);
                if ($this->state_list) {
                    $states = $this->nonNumericStates();
                    return Form::dropdown($prefix . 'state', ['-wrapper-class' => 'address-state'], $states);
                } else {
                    return Form::text($prefix . 'state', ['-wrapper-class' => 'address-state']);
                }
            } else {
                Form::nextFieldDetails('State/Province (please ignore)', false);
                $field = Form::text($prefix . 'state', ['-wrapper-class' => 'address-state']);
                return preg_replace('/^<div /', '<div style="display:none;" ', $field);
            }

        case 'postcode':
            if ($this->postcode_name) {
                Form::nextFieldDetails($this->postcode_name, $required);
                return Form::text($prefix . 'postcode', ['-wrapper-class' => 'address-postcode']);
            } else {
                Form::nextFieldDetails('Postcode (please ignore)', false);
                $field = Form::text($prefix . 'postcode', ['-wrapper-class' => 'address-postcode']);
                return preg_replace('/^<div /', '<div style="display:none;" ', $field);
            }

        default:
            throw new InvalidArgumentException('Unrecognised address field');
        }
    }


    /**
    * Returns a string which is a formatted version of the address specified using the provided data.
    * The string contains newlines which will need converting to BRs for HTML output
    *
    * @param array|object $data A record from the database
    *        required columns: street, town, state, postcode, country
    * @return string Plaintext
    **/
    public function outputAddressText($data)
    {
        if ($data instanceof stdClass) $data = get_object_vars($data);

        $str = $data['street'] . "\n" . $data['town'];

        if ($this->state_name) {
            if ($this->state_list and is_numeric($data['state'])) {
                $str .= ', ' . $this->state_list[$data['state']];
            } else {
                $str .= ', ' . $data['state'];
            }
        }

        if ($this->postcode_name) {
            $str .= ' ' . $data['postcode'];
        }

        $str .= "\n" . CountryConstants::$alpha3[$data['country']];

        return $str;
    }


    /**
     * Validate address fields
     *
     * @param Validator $valid Validator for the form being processed
     * @param bool $required Are the address fields required?
     * @return void
     */
    public function validateAddress(Validator $valid, $required = false)
    {
        $field_names = [
            'street' => $this->line1,
            'street2' => $this->line2,
            'town' => $this->town_name,
            'state' => $this->state_name,
            'postcode' => $this->postcode_name,
        ];
        foreach ($field_names as $field => $label) {
            $valid->setFieldLabel($field, $label);
        }

        if ($required) $valid->required(['street']);
        $valid->check('street', 'Validity::length', 0, 200);
        $valid->check('street2', 'Validity::length', 0, 200);

        if ($required) $valid->required(['town']);
        $valid->check('town', 'Validity::length', 0, 100);

        if ($this->state_name) {
            if ($required) $valid->required(['state']);
            $valid->check('state', 'Validity::length', 0, 100);

            if ($this->state_list) {
                $states = $this->stateValues();
                $valid->check('state', 'Validity::inArray', $states);
            }
        }

        if ($this->postcode_name) {
            if ($required) $valid->required(['postcode']);
            $valid->check('postcode', 'Validity::length', 0, 10);
        }
    }


    /**
    * Formats numbers, like the interal {@see number_format} function
    *
    * @param int|float The number to format
    * @param int $precision The number of decimal places to render
    * @return string
    **/
    public function numberFormat($number, $precision = 0)
    {
        return number_format($number, $precision, $this->decimal_seperator, $this->group_seperator);
    }


    /**
    * Formats currency values, similar to the interal {@see number_format} function
    *
    * @param int|float The number to format
    * @param int $precision The number of decimal places to render; if NULL then it's locale-dependent
    * @return string
    **/
    public function moneyFormat($number, $precision = null)
    {
        if ($precision === null) $precision = $this->currency_decimal;
        if ($number < 0.0) {
            return '-' . $this->currency_symbol . $this->numberFormat(abs($number), $precision);
        } else {
            return $this->currency_symbol . $this->numberFormat($number, $precision);
        }
    }


    /**
    * Formats dates in a long format
    *
    * @param int $timestamp Unix timestamp
    * @return string
    **/
    public function longdate($timestamp)
    {
        return date($this->longdate, $timestamp);
    }


    /**
    * Formats dates in a short format
    *
    * @param int $timestamp Unix timestamp
    * @return string
    **/
    public function shortdate($timestamp)
    {
        return date($this->shortdate, $timestamp);
    }


    /**
    * Formats times
    *
    * @param int $timestamp Unix timestamp
    * @return string
    **/
    public function time($timestamp)
    {
        return date($this->time, $timestamp);
    }


    /**
     * Gets a list of states
     * @return array For use with {@see Fb::dropdown}
     */
    public function getStateList()
    {
        return $this->state_list;
    }


    /**
     * The name of the currency, e.g. 'Dollar' for $
     * @return string
     */
    public function getCurrencyName()
    {
        return $this->currency_name;
    }


    /**
     * Return currency ISO code, eg 'AUD'
     *
     * @return string eg 'AUD'
     */
    public function getCurrencyISO()
    {
        return $this->currency_iso;
    }
}
