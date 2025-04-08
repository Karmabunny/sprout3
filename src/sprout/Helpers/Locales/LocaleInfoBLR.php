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
 * Locale info for Belarus; see {@see LocaleInfo}
 */
class LocaleInfoBLR extends LocaleInfo
{
    protected $state_list = [
        'BR' => 'Bresckaja voblasć',
        'HM' => 'Horad Minsk',
        'HO' => 'Homyel\'skaya voblasts\'',
        'HR' => 'Hrodzenskaya voblasts\'',
        'MA' => 'Mahilyowskaya voblasts\'',
        'MI' => 'Minskaya voblasts\'',
        'VI' => 'Vitsyebskaya voblasts\'',
    ];


    protected $currency_iso = 'BYN';
    protected $phone_code = '375';

}
