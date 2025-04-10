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
 * Locale info for Poland; see {@see LocaleInfo}
 */
class LocaleInfoPOL extends LocaleInfo
{
    protected $state_list = [
        'DS' => 'Dolnośląskie',
        'KP' => 'Kujawsko-pomorskie',
        'LB' => 'Lubuskie',
        'LD' => 'Łódzkie',
        'LU' => 'Lubelskie',
        'MA' => 'Małopolskie',
        'MZ' => 'Mazowieckie',
        'OP' => 'Opolskie',
        'PD' => 'Podlaskie',
        'PK' => 'Podkarpackie',
        'PM' => 'Pomorskie',
        'SK' => 'Świętokrzyskie',
        'SL' => 'Śląskie',
        'WN' => 'Warmińsko-mazurskie',
        'WP' => 'Wielkopolskie',
        'ZP' => 'Zachodniopomorskie',
    ];
    protected $currency_iso = 'PLN';
    protected $phone_code = '48';

}
