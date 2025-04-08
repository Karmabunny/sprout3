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
 * Locale info for Singapore; see {@see LocaleInfo}
 */
class LocaleInfoSGP extends LocaleInfo
{
    protected $state_list = [
        'Central Singapore',
        'North East',
        'North West',
        'South East',
        'South West',
    ];

    protected $currency_iso = 'SGD';
    protected $phone_code = '65';

}
