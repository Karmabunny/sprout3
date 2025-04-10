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
 * Locale info for Kuwait; see {@see LocaleInfo}
 */
class LocaleInfoKWT extends LocaleInfo
{
    protected $state_list = [
        'AH' => 'Al Aḩmadī',
        'FA' => 'Al Farwānīyah',
        'HA' => 'Ḩawallī',
        'JA' => 'Al Jahrā’',
        'KU' => 'Al ‘Āşimah',
        'MU' => 'Mubārak al Kabīr',
    ];

    protected $currency_iso = 'KWD';
    protected $phone_code = '965';

}
