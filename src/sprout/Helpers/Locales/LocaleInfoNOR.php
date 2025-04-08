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
 * Locale info for Norway; see {@see LocaleInfo}
 */
class LocaleInfoNOR extends LocaleInfo
{
    protected $state_list = [
        'Østfold',
        'Akershus',
        'Oslo',
        'Hedmark',
        'Oppland',
        'Buskerud',
        'Vestfold',
        'Telemark',
        'Aust-Agder',
        'Vest-Agder',
        'Rogaland',
        'Hordaland',
        'Sogn og Fjordane',
        'Møre og Romsdal',
        'Sør-Trøndelag',
        'Nord-Trøndelag',
        'Nordland',
        'Troms',
        'Finnmark',
        'Svalbard',
        'Jan Mayen',
    ];
    protected $currency_iso = 'NOK';
    protected $phone_code = '47';

}
