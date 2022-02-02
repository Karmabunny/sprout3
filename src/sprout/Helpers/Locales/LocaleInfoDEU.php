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
 * Locale info for Germany; see {@see LocaleInfo}
 */
class LocaleInfoDEU extends LocaleInfo
{
    protected $state_list = [
        'BB' => 'Brandenburg',
        'BE' => 'Berlin',
        'BW' => 'Baden-Württemberg',
        'BY' => 'Bayern',
        'HB' => 'Bremen',
        'HE' => 'Hessen',
        'HH' => 'Hamburg',
        'MV' => 'Mecklenburg-Vorpommern',
        'NI' => 'Niedersachsen',
        'NW' => 'Nordrhein-Westfalen',
        'RP' => 'Rheinland-Pfalz',
        'SH' => 'Schleswig-Holstein',
        'SL' => 'Saarland',
        'SN' => 'Sachsen',
        'ST' => 'Sachsen-Anhalt',
        'TH' => 'Thüringen',
    ];


    protected $currency_iso = 'EUR';
}
