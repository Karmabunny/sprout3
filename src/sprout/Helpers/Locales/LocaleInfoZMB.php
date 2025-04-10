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
 * Locale info for Zambia; see {@see LocaleInfo}
 */
class LocaleInfoZMB extends LocaleInfo
{
    protected $state_list = [
        'Western',
        'Central',
        'Eastern',
        'Luapula',
        'Northern',
        'North-Western',
        'Southern',
        'Copperbelt',
        'Lusaka',
        'Muchinga',
    ];

    protected $currency_iso = 'ZMW';
    protected $phone_code = '260';

}
