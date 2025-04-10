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
 * Locale info for Sweden; see {@see LocaleInfo}
 */
class LocaleInfoSWE extends LocaleInfo
{
    protected $state_list = [
        'AB' => 'Stockholms län',
        'AC' => 'Västerbottens län',
        'BD' => 'Norrbottens län',
        'C' => 'Uppsala län',
        'D' => 'Södermanlands län',
        'E' => 'Östergötlands län',
        'F' => 'Jönköpings län',
        'G' => 'Kronoborgs län',
        'H' => 'Kalmar län',
        'I' => 'Gotlands län',
        'K' => 'Blekinge län',
        'M' => 'Skåne län',
        'N' => 'Hallands län',
        'O' => 'Västra Götalands län',
        'S' => 'Värmlands län',
        'T' => 'Örebro län',
        'U' => 'Västmanlands län',
        'W' => 'Dalarnes län',
        'X' => 'Gävleborgs län',
        'Y' => 'Västernorrlands län',
        'Z' => 'Jämtlands län',
    ];
    protected $currency_iso = 'SEK';
    protected $phone_code = '46';

}
