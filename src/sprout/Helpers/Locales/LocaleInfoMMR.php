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
 * Locale info for Myanmar; see {@see LocaleInfo}
 */
class LocaleInfoMMR extends LocaleInfo
{
    protected $state_list = [
        'Sagaing',
        'Bago',
        'Magway',
        'Mandalay',
        'Taninthayi',
        'Yangon',
        'Ayeyawady',
        'Kachin',
        'Kayah',
        'Kayin',
        'Chin',
        'Mon',
        'Rakhine',
        'Shan',
        'Nay Pyi Taw',
    ];

    protected $currency_iso = 'MMK';
    protected $phone_code = '95';

}
