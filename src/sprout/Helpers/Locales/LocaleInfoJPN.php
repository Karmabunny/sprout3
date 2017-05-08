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
 * Locale info for Japan; see {@see LocaleInfo}
 */
class LocaleInfoJPN extends LocaleInfo
{
    protected $state_name = 'Prefecture';
    protected $state_list = array(
        'Hokkaidō' => array(
            'Hokkaidō' => 'Hokkaidō',
        ),
        'Tōhoku' => array(
            'Aomori-ken' => 'Aomori',
            'Iwate-ken' => 'Iwate',
            'Miyagi-ken' => 'Miyagi',
            'Akita-ken' => 'Akita',
            'Yamagata-ken' => 'Yamagata',
            'Fukushima-ken' => 'Fukushima',
        ),
        'Kantō' => array(
            'Ibaraki-ken' => 'Ibaraki',
            'Tochigi-ken' => 'Tochigi',
            'Gunma-ken' => 'Gunma',
            'Saitama-ken' => 'Saitama',
            'Chiba-ken' => 'Chiba',
            'Tōkyō-to' => 'Tōkyō',
            'Kanagawa-ken' => 'Kanagawa',
        ),
        'Chūbu' => array(
            'Niigata-ken' => 'Niigata',
            'Toyama-ken' => 'Toyama',
            'Ishikawa-ken' => 'Ishikawa',
            'Fukui-ken' => 'Fukui',
            'Yamanashi-ken' => 'Yamanashi',
            'Nagano-ken' => 'Nagano',
            'Gifu-ken' => 'Gifu',
            'Shizuoka-ken' => 'Shizuoka',
            'Aichi-ken' => 'Aichi',
        ),
        'Kansai' => array(
            'Mie-ken' => 'Mie',
            'Shiga-ken' => 'Shiga',
            'Kyōto-fu' => 'Kyōto',
            'Ōsaka-fu' => 'Ōsaka',
            'Hyōgo-ken' => 'Hyōgo',
            'Nara-ken' => 'Nara',
            'Wakayama-ken' => 'Wakayama',
        ),
        'Chūgoku' => array(
            'Tottori-ken' => 'Tottori',
            'Shimane-ken' => 'Shimane',
            'Okayama-ken' => 'Okayama',
            'Hiroshima-ken' => 'Hiroshima',
            'Yamaguchi-ken' => 'Yamaguchi',
        ),
        'Shikoku' => array(
            'Tokushima-ken' => 'Tokushima',
            'Kagawa-ken' => 'Kagawa',
            'Ehime-ken' => 'Ehime',
            'Kōchi-ken' => 'Kōchi',
        ),
        'Kyushu' => array(
            'Fukuoka-ken' => 'Fukuoka',
            'Saga-ken' => 'Saga',
            'Nagasaki-ken' => 'Nagasaki',
            'Kumamoto-ken' => 'Kumamoto',
            'Ōita-ken' => 'Ōita',
            'Miyazaki-ken' => 'Miyazaki',
            'Kagoshima-ken' => 'Kagoshima',
            'Okinawa-ken' => 'Okinawa',
        ),
    );

    protected $currency_symbol = '¥';
    protected $currency_decimal = 0;
    protected $currency_name = 'Yen';
}


