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
 * Locale info for Pakistan; see {@see LocaleInfo}
 */
class LocaleInfoPAK extends LocaleInfo
{
    protected $state_list = [
        'BA' => 'Balochistan',
        'GB' => 'Gilgit-Baltistan',
        'IS' => 'Islamabad',
        'JK' => 'Azad Kashmir',
        'KP' => 'Khyber Pakhtunkhwa',
        'PB' => 'Punjab',
        'SD' => 'Sindh',
        'TA' => 'Federally Administered Tribal Areas',
    ];

    protected $currency_iso = 'PKR';
    protected $phone_code = '92';

}
