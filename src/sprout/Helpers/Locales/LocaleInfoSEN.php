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
 * Locale info for Senegal; see {@see LocaleInfo}
 */
class LocaleInfoSEN extends LocaleInfo
{
    protected $state_list = [
        'DB ' => 'Diourbel',
        'DK' => 'Dakar',
        'FK' => 'Fatick',
        'KA' => 'Kaffrine',
        'KD' => 'Kolda',
        'KE' => 'Kédougou',
        'KL' => 'Kaolack',
        'LG' => 'Louga',
        'MT' => 'Matam',
        'SE' => 'Sédhiou',
        'SL' => 'Saint-Louis',
        'TC' => 'Tambacounda',
        'TH' => 'Thiès',
        'ZG' => 'Ziguinchor',
    ];

    protected $currency_iso = 'XOF';
    protected $phone_code = '221';

}
