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


/**
 * Locale info for Nepal; see {@see LocaleInfo}
 */
class LocaleInfoNPL extends LocaleInfo
{
    protected $state_list = [
        'BA' => 'Bagmati',
        'BH' => 'Bheri',
        'DH' => 'Dhawalagiri',
        'GA' => 'Gandaki',
        'JA' => 'Janakpur',
        'KA' => 'Karnali',
        'KO' => 'Koshi',
        'LU' => 'Lumbini',
        'MA' => 'Mahakali',
        'ME' => 'Mechi',
        'NA' => 'Narayani',
        'RA' => 'Rapti',
        'SA' => 'Sagarmatha',
        'SE' => 'Seti',
    ];

    protected $currency_iso = 'NPR';
    protected $phone_code = '977';

}
