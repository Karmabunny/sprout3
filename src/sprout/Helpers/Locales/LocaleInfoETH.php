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
 * Locale info for Ethiopia; see {@see LocaleInfo}
 */
class LocaleInfoETH extends LocaleInfo
{
    protected $state_list = [
        'AA' => 'Ādīs Ābeba',
        'AF' => 'Āfar',
        'AM' => 'Āmara',
        'BE' => 'Bīnshangul Gumuz',
        'DD' => 'Dirē Dawa',
        'GA' => 'Gambēla Hizboch',
        'HA' => 'Hārerī Hizb',
        'OR' => 'Oromīya',
        'SN' => 'YeDebub Bihēroch Bihēreseboch na Hizboch',
        'SO' => 'Sumalē',
        'TI' => 'Tigray',
    ];


    protected $currency_iso = 'ETB';
    protected $phone_code = '251';

}
