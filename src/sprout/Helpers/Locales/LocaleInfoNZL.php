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

use Sprout\Helpers\Validator;


/**
 * Locale info for New Zealand; see {@see LocaleInfo}
 */
class LocaleInfoNZL extends LocaleInfo
{
    protected $state_name = 'Town';

    protected $town_name = 'Suburb';

    protected $postcode_name = 'Postcode';

    protected $currency_iso = 'NZD';
    protected $phone_code = '64';


    /**
     * Validate address fields
     *
     * @param Validator $valid The validation object to add rules to
     * @param bool $required Are the address fields required?
     */
    public function validateAddress(Validator $valid, $required = false)
    {
        parent::validateAddress($valid, $required);

        $valid->check('postcode', 'Validity::positiveInt');
        $valid->check('postcode', 'Validity::length', 4, 4);
    }

}
