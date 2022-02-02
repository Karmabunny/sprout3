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
 * Locale info for Malaysia; see {@see LocaleInfo}
 */
class LocaleInfoMYS extends LocaleInfo
{
    protected $state_list = [
        'Johor',
        'Kedah',
        'Kelantan',
        'Melaka',
        'Negeri Sembilan',
        'Pahang',
        'Pulau Pinang',
        'Perak',
        'Perlis',
        'Selangor',
        'Terengganu',
        'Sabah',
        'Sarawak',
        'Wilayah Persekutuan Kuala Lumpur',
        'Wilayah Persekutuan Labuan',
        'Wilayah Persekutuan Putrajaya',
    ];

    protected $currency_iso = 'MYR';
}
