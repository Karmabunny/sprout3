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

use Sprout\Exceptions\ValidationException;
use Sprout\Helpers\Validator;


/**
 * Locale info for China; see {@see LocaleInfo}
 */
class LocaleInfoCHN extends LocaleInfo
{
    protected $state_list = [
        'Beijing',
        'Tianjin',
        'Hebei',
        'Shanxi',
        'Nei Mongol',
        'Liaoning',
        'Jilin',
        'Heilongjiang',
        'Shanghai',
        'Jiangsu',
        'Zhejiang',
        'Anhui',
        'Fujian',
        'Jiangxi',
        'Shandong',
        'Henan',
        'Hubei',
        'Hunan',
        'Guangdong',
        'Guangxi',
        'Hainan',
        'Chongqing',
        'Sichuan',
        'Guizhou',
        'Yunnan',
        'Xizang',
        'Shaanxi',
        'Gansu',
        'Qinghai',
        'Ningxia',
        'Xinjiang',
        'Taiwan',
        'Xianggang',
        'Aomen',
    ];


    /**
     * Validate a Chinese postcode, which is a 6-digit number
     *
     * @param string $code The postcode to validate
     * @throws ValidationException If the format isn't correct
     */
    public static function validatePostcode($code)
    {
        if (!preg_match('/^[0-9]{6}$/i', $code)) {
            throw new ValidationException('Incorrect format');
        }
    }


    /**
     * Validate address fields
     *
     * @param Validator $valid The validation object to add rules to
     * @param bool $required Are the address fields required?
     */
    public function validateAddress(Validator $valid, $required = false)
    {
        parent::validateAddress($valid, $required);

        $valid->check('postcode', __CLASS__ . '::validatePostcode');
        $valid->check('postcode', 'Validity::length', 6, 6);
    }


    protected $currency_iso = 'CNY';
}
