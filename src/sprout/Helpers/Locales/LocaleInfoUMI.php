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
 * Locale info for United States Minor Outlying Islands; see {@see LocaleInfo}
 */
class LocaleInfoUMI extends LocaleInfo
{
    protected $state_list = [
        'Johnston Atoll',
        'Midway Islands',
        'Navassa Island',
        'Wake Island',
        'Baker Island',
        'Howland Island',
        'Jarvis Island',
        'Kingman Reef',
        'Palmyra Atoll',
    ];

    protected $currency_iso = 'USD';
    protected $phone_code = '1';

}
