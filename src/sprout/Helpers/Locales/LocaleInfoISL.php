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
 * Locale info for Iceland; see {@see LocaleInfo}
 */
class LocaleInfoISL extends LocaleInfo
{
    protected $state_list = [
        'Höfuðborgarsvæði utan Reykjavíkur',
        'Suðurnes',
        'Vesturland',
        'Vestfirðir',
        'Norðurland vestra',
        'Norðurland eystra',
        'Austurland',
        'Suðurland',
    ];

    protected $currency_iso = 'ISK';
    protected $phone_code = '354';

}
