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
 * Locale info for South Korea; see {@see LocaleInfo}
 */
class LocaleInfoKOR extends LocaleInfo
{
    protected $state_list = [
        'Seoul-teukbyeolsi',
        'Busan Gwang\'yeogsi',
        'Daegu Gwang\'yeogsi',
        'Incheon Gwang\'yeogsi',
        'Gwangju Gwang\'yeogsi',
        'Daejeon Gwang\'yeogsi',
        'Ulsan Gwang\'yeogsi',
        'Gyeonggido',
        'Gang\'weondo',
        'Chungcheongbugdo',
        'Chungcheongnamdo',
        'Jeonrabugdo',
        'Jeonranamdo',
        'Gyeongsangbugdo',
        'Gyeongsangnamdo',
        'Jeju-teukbyeoljachido',
        'Sejong',
    ];

    protected $currency_iso = 'KRW';
    protected $phone_code = '82';

}
