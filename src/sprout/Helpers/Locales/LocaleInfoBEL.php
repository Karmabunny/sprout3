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
 * Locale info for Belgium; see {@see LocaleInfo}
 */
class LocaleInfoBEL extends LocaleInfo
{
    protected $state_list = [
        'BRU' => 'Bruxelles-Capitale, Région de',
        'VAN' => 'Antwerpen',
        'VBR' => 'Vlaams Brabant',
        'VLI' => 'Limburg',
        'VOV' => 'Oost-Vlaanderen',
        'VWV' => 'West-Vlaanderen',
        'WBR' => 'Brabant wallon',
        'WHT' => 'Hainaut',
        'WLG' => 'Liège',
        'WLX' => 'Luxembourg',
        'WNA' => 'Namur',
    ];


    protected $currency_iso = 'EUR';
    protected $phone_code = '32';

}
