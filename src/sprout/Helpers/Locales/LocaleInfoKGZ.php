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
 * Locale info for Kyrgyzstan; see {@see LocaleInfo}
 */
class LocaleInfoKGZ extends LocaleInfo
{
    protected $state_list = [
        'Batken',
        'Chü',
        'Bishkek',
        'Osh',
        'Jalal-Abad',
        'Naryn',
        'Osh',
        'Talas',
        'Ysyk-Köl',
    ];

    protected $currency_iso = 'KGS';
    protected $phone_code = '996';

}
