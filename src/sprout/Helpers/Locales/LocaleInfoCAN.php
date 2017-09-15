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

use Sprout\Exceptions\ValidationException;
use Sprout\Helpers\Validator;


/**
 * Locale info for Canada; see {@see LocaleInfo}
 */
class LocaleInfoCAN extends LocaleInfo
{
    protected $state_name = 'Province';
    protected $state_list = array(
        'AB' => 'Alberta',
        'BC' => 'British Columbia',
        'MB' => 'Manitoba',
        'NB' => 'New Brunswick',
        'NL' => 'Newfoundland and Labrador',
        'NS' => 'Nova Scotia',
        'ON' => 'Ontario',
        'PE' => 'Prince Edward Island',
        'QC' => 'Quebec',
        'SK' => 'Saskatchewan',
    );

    // English speaking uses period and space
    // French speaking uses comma and space
    protected $decimal_seperator = '.';
    protected $group_seperator = ' ';


    /**
     * Validate a Canadian postcode, which must match the format 'A1A 1A1'
     *
     * @param string $code The postcode to validate
     * @throws ValidationException If the format isn't correct
     */
    public static function validatePostcode($code)
    {
        if (!preg_match('/^[A-Z][0-9][A-Z] [0-9][A-Z][0-9]$/', $code)) {
            $err = 'Incorrect format';

            $details = [];
            if (strpos($code, ' ') === false) {
                $details[] = 'space required';
            }
            if (preg_match('/[a-z]/', $code)) {
                $details[] = 'must be uppercase';
            }

            if (count($details) > 0) {
                $err .= ' - ' . implode(', ', $details);
            }

            throw new ValidationException($err);
        }
    }


    /**
     * Validate address fields
     *
     * @param Validator $valid The validation object to add rules to
     * @param bool $required Are the address fields required?
     */
    public function validateAddress(Validator $valid, $required = false)
    {
        parent::validateAddress($valid, $required);

        $valid->check('postcode', __CLASS__ . '::validatePostcode');
        $valid->check('postcode', 'Validity::length', 7, 7);
    }
}
