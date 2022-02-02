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
 * Locale info for Ireland; see {@see LocaleInfo}
 */
class LocaleInfoIRL extends LocaleInfo
{
    protected $state_list = [
        'CE' => 'Clare',
        'CN' => 'Cavan',
        'CO' => 'Cork',
        'CW' => 'Carlow',
        'D' => 'Dublin',
        'DL' => 'Donegal',
        'G' => 'Galway',
        'KE' => 'Kildare',
        'KK' => 'Kilkenny',
        'KY' => 'Kerry',
        'LD' => 'Longford',
        'LH' => 'Louth',
        'LK' => 'Limerick',
        'LM' => 'Leitrim',
        'LS' => 'Laois',
        'MH' => 'Meath',
        'MN' => 'Monaghan',
        'MO' => 'Mayo',
        'OY' => 'Offaly',
        'RN' => 'Roscommon',
        'SO' => 'Sligo',
        'TA' => 'Tipperary',
        'WD' => 'Waterford',
        'WH' => 'Westmeath',
        'WW' => 'Wicklow',
        'WX' => 'Wexford',
    ];

    protected $currency_iso = 'EUR';
}
