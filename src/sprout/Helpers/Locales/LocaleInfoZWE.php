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
 * Locale info for Zimbabwe; see {@see LocaleInfo}
 */
class LocaleInfoZWE extends LocaleInfo
{
    protected $state_list = [
        'BU' => 'Bulawayo',
        'HA' => 'Harare',
        'MA' => 'Manicaland',
        'MC' => 'Mashonaland Central',
        'ME' => 'Mashonaland East',
        'MI' => 'Midlands',
        'MN' => 'Matabeleland North',
        'MS' => 'Matabeleland South',
        'MV' => 'Masvingo',
        'MW' => 'Mashonaland West',
    ];

    protected $currency_iso = 'ZWL';
    protected $phone_code = '263';

}
