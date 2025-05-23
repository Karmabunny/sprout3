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
 * Locale info for Gabon; see {@see LocaleInfo}
 */
class LocaleInfoGAB extends LocaleInfo
{
    protected $state_list = [
        'Estuaire',
        'Haut-Ogooué',
        'Moyen-Ogooué',
        'Ngounié',
        'Nyanga',
        'Ogooué-Ivindo',
        'Ogooué-Lolo',
        'Ogooué-Maritime',
        'Woleu-Ntem',
    ];


    protected $currency_iso = 'XAF';
    protected $phone_code = '241';

}
