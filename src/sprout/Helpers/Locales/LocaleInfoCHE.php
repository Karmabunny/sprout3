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
 * Locale info for Switzerland; see {@see LocaleInfo}
 */
class LocaleInfoCHE extends LocaleInfo
{
    protected $state_list = [
        'AG' => 'Aargau',
        'AI' => 'Appenzell Innerrhoden',
        'AR' => 'Appenzell Ausserrhoden',
        'BE' => 'Bern',
        'BL' => 'Basel-Landschaft',
        'BS' => 'Basel-Stadt',
        'FR' => 'Fribourg',
        'GE' => 'Genève',
        'GL' => 'Glarus',
        'GR' => 'Graubünden',
        'JU' => 'Jura',
        'LU' => 'Luzern',
        'NE' => 'Neuchâtel',
        'NW' => 'Nidwalden',
        'OW' => 'Obwalden',
        'SG' => 'Sankt Gallen',
        'SH' => 'Schaffhausen',
        'SO' => 'Solothurn',
        'SZ' => 'Schwyz',
        'TG' => 'Thurgau',
        'TI' => 'Ticino',
        'UR' => 'Uri',
        'VD' => 'Vaud',
        'VS' => 'Valais',
        'ZG' => 'Zug',
        'ZH' => 'Zürich',
    ];


    protected $currency_iso = 'CHF';
    protected $phone_code = '41';

}
