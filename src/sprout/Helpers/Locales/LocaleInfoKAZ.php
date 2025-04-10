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
 * Locale info for Kazakhstan; see {@see LocaleInfo}
 */
class LocaleInfoKAZ extends LocaleInfo
{
    protected $state_list = [
        'AKM' => 'Aqmola oblysy',
        'AKT' => 'Aqtöbe oblysy',
        'ALA' => 'Almaty',
        'ALM' => 'Almaty oblysy',
        'AST' => 'Astana',
        'ATY' => 'Atyraū oblysy',
        'KAR' => 'Qaraghandy oblysy',
        'KUS' => 'Qostanay oblysy',
        'KZY' => 'Qyzylorda oblysy',
        'MAN' => 'Mangghystaū oblysy',
        'PAV' => 'Pavlodar oblysy',
        'SEV' => 'Soltüstik Qazaqstan oblysy',
        'VOS' => 'Shyghys Qazaqstan oblysy',
        'YUZ' => 'Ongtüstik Qazaqstan oblysy',
        'ZAP' => 'Batys Qazaqstan oblysy',
        'ZHA' => 'Zhambyl oblysy',
    ];

    protected $currency_iso = 'KZT';
    protected $phone_code = '7';

}
