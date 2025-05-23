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
 * Locale info for Guyana; see {@see LocaleInfo}
 */
class LocaleInfoGUY extends LocaleInfo
{
    protected $state_list = [
        'BA' => 'Barima-Waini',
        'CU' => 'Cuyuni-Mazaruni',
        'DE' => 'Demerara-Mahaica',
        'EB' => 'East Berbice-Corentyne',
        'ES' => 'Essequibo Islands-West Demerara',
        'MA' => 'Mahaica-Berbice',
        'PM' => 'Pomeroon-Supenaam',
        'PT' => 'Potaro-Siparuni',
        'UD' => 'Upper Demerara-Berbice',
        'UT' => 'Upper Takutu-Upper Essequibo',
    ];

    protected $currency_iso = 'GYD';
    protected $phone_code = '592';

}
