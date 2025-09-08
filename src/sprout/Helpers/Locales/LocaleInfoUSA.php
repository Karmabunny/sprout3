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
 * Locale info for USA; see {@see LocaleInfo}
 */
class LocaleInfoUSA extends LocaleInfo
{
    protected $state_name = 'State';
    protected $state_list = array(
        'AL'=>"Alabama",
        'AK'=>"Alaska",
        'AZ'=>"Arizona",
        'AR'=>"Arkansas",
        'CA'=>"California",
        'CO'=>"Colorado",
        'CT'=>"Connecticut",
        'DE'=>"Delaware",
        'DC'=>"District Of Columbia",
        'FL'=>"Florida",
        'GA'=>"Georgia",
        'HI'=>"Hawaii",
        'ID'=>"Idaho",
        'IL'=>"Illinois",
        'IN'=>"Indiana",
        'IA'=>"Iowa",
        'KS'=>"Kansas",
        'KY'=>"Kentucky",
        'LA'=>"Louisiana",
        'ME'=>"Maine",
        'MD'=>"Maryland",
        'MA'=>"Massachusetts",
        'MI'=>"Michigan",
        'MN'=>"Minnesota",
        'MS'=>"Mississippi",
        'MO'=>"Missouri",
        'MT'=>"Montana",
        'NE'=>"Nebraska",
        'NV'=>"Nevada",
        'NH'=>"New Hampshire",
        'NJ'=>"New Jersey",
        'NM'=>"New Mexico",
        'NY'=>"New York",
        'NC'=>"North Carolina",
        'ND'=>"North Dakota",
        'OH'=>"Ohio",
        'OK'=>"Oklahoma",
        'OR'=>"Oregon",
        'PA'=>"Pennsylvania",
        'RI'=>"Rhode Island",
        'SC'=>"South Carolina",
        'SD'=>"South Dakota",
        'TN'=>"Tennessee",
        'TX'=>"Texas",
        'UT'=>"Utah",
        'VT'=>"Vermont",
        'VA'=>"Virginia",
        'WA'=>"Washington",
        'WV'=>"West Virginia",
        'WI'=>"Wisconsin",
        'WY'=>"Wyoming"
    );

    protected $town_name = 'Suburb';

    protected $postcode_name = 'ZIP Code';

    protected $currency_iso = 'USD';
    protected $phone_code = '1';

    /**
     * Validate a ZIP Code, as a 5-digit number with an optional appended hyphen with 4 additional digits
     * E.g. 20521 or 20521-9000
     *
     * @param string $code The postcode to validate
     * @throws ValidationException If the format isn't correct
     */
    public static function validatePostcode($code)
    {
        if (!preg_match('/^[0-9]{5}(?:-[0-9]{4})?$/i', $code)) {
            throw new ValidationException('Incorrect format');
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

        // Parent validation checks for postcode length <= 10, so don't double up the max length error message
        $valid->check('postcode', 'Validity::length', 5, PHP_INT_MAX);
    }

}
