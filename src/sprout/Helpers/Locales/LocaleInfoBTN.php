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
 * Locale info for Bhutan; see {@see LocaleInfo}
 */
class LocaleInfoBTN extends LocaleInfo
{
    protected $state_list = [
        'Paro',
        'Chhukha',
        'Ha',
        'Samtse',
        'Thimphu',
        'Tsirang',
        'Dagana',
        'Punakha',
        'Wangdue Phodrang',
        'Sarpang',
        'Trongsa',
        'Bumthang',
        'Zhemgang',
        'Trashigang',
        'Monggar',
        'Pemagatshel',
        'Lhuentse',
        'Samdrup Jongkha',
        'Gasa',
        'Trashi Yangtse',
    ];


    protected $currency_iso = 'BTN';
}
