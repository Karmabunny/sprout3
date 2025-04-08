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
 * Locale info for Czech Republic; see {@see LocaleInfo}
 */
class LocaleInfoCZE extends LocaleInfo
{
    protected $state_list = [
        'JC' => 'Jihočeský kraj',
        'JM' => 'Jihomoravský kraj',
        'KA' => 'Karlovarský kraj',
        'KR' => 'Královéhradecký kraj',
        'LI' => 'Liberecký kraj',
        'MO' => 'Moravskoslezský kraj',
        'OL' => 'Olomoucký kraj',
        'PA' => 'Pardubický kraj',
        'PL' => 'Plzeňský kraj',
        'PR' => 'Praha, hlavní město',
        'ST' => 'Středočeský kraj',
        'US' => 'Ústecký kraj',
        'VY' => 'Vysočina',
        'ZL' => 'Zlínský kraj',
    ];


    protected $currency_iso = 'CZK';
    protected $phone_code = '420';

}
