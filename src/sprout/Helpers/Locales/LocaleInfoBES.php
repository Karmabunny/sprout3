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
 * Locale info for Caribbean Netherlands; see {@see LocaleInfo}
 */
class LocaleInfoBES extends LocaleInfo
{
    protected $state_list = [
        'BO' => 'Bonaire',
        'SA' => 'Saba',
        'SE' => 'Sint Eustatius',
    ];


    protected $currency_iso = 'USD';
    protected $phone_code = '599';

}
