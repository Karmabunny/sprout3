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
 * Locale info for Saint Vincent and the Grenadines; see {@see LocaleInfo}
 */
class LocaleInfoVCT extends LocaleInfo
{
    protected $state_list = [
        'Charlotte     ',
        'Saint Andrew  ',
        'Saint David   ',
        'Saint George  ',
        'Saint Patrick ',
        'Grenadines    ',
    ];

    protected $currency_iso = 'XCD';
    protected $phone_code = '1784';

}
