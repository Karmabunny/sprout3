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
 * Locale info for Suriname; see {@see LocaleInfo}
 */
class LocaleInfoSUR extends LocaleInfo
{
    protected $state_list = [
        'BR' => 'Brokopondo',
        'CM' => 'Commewijne',
        'CR' => 'Coronie',
        'MA' => 'Marowijne',
        'NI' => 'Nickerie',
        'PM' => 'Paramaribo',
        'PR' => 'Para',
        'SA' => 'Saramacca',
        'SI' => 'Sipaliwini',
        'WA' => 'Wanica',
    ];

    protected $currency_iso = 'SRD';
    protected $phone_code = '597';

}
