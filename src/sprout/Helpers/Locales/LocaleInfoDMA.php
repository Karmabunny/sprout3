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
 * Locale info for Dominica; see {@see LocaleInfo}
 */
class LocaleInfoDMA extends LocaleInfo
{
    protected $state_list = [
        'Saint Andrew',
        'Saint David',
        'Saint George',
        'Saint John',
        'Saint Joseph',
        'Saint Luke',
        'Saint Mark',
        'Saint Patrick',
        'Saint Paul',
        'Saint Peter',
    ];


    protected $currency_iso = 'XCD';
    protected $phone_code = '1767';

}
