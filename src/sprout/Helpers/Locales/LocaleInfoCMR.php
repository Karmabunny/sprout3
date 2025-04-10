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
 * Locale info for Cameroon; see {@see LocaleInfo}
 */
class LocaleInfoCMR extends LocaleInfo
{
    protected $state_list = [
        'AD' => 'Adamaoua',
        'CE' => 'Centre',
        'EN' => 'Far North',
        'ES' => 'East',
        'LT' => 'Littoral',
        'NO' => 'North',
        'NW' => 'North-West',
        'OU' => 'West',
        'SU' => 'South',
        'SW' => 'South-West',
    ];


    protected $currency_iso = 'XAF';
    protected $phone_code = '237';

}
