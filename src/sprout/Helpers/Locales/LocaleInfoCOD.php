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
 * Locale info for DR Congo; see {@see LocaleInfo}
 */
class LocaleInfoCOD extends LocaleInfo
{
    protected $state_list = [
        'BC' => 'Bas-Congo',
        'BN' => 'Bandundu',
        'EQ' => 'Équateur',
        'KA' => 'Katanga',
        'KE' => 'Kasai-Oriental',
        'KN' => 'Kinshasa',
        'KW' => 'Kasai-Occidental',
        'MA' => 'Maniema',
        'NK' => 'Nord-Kivu',
        'OR' => 'Orientale',
        'SK' => 'Sud-Kivu',
    ];


    protected $currency_iso = 'CDF';
    protected $phone_code = '243';

}
