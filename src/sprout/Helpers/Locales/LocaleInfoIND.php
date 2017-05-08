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
 * Locale info for India; see {@see LocaleInfo}
 */
class LocaleInfoIND extends LocaleInfo
{
    protected $state_name = 'State';
    protected $state_list = array(
        'Andaman and Nicobar Islands',
        'Andhra Pradesh',
        'Arunachal Pradesh',
        'Assam',
        'Bihar',
        'Chandigarh',
        'Chhattisgarh',
        'Dadra and Nagar Haveli',
        'Daman and Diu',
        'Goa',
        'Gujarat',
        'Haryana',
        'Himachal Pradesh',
        'Jammu and Kashmir',
        'Jharkhand',
        'Karnataka',
        'Kerala',
        'Lakshadweep',
        'Madhya Pradesh',
        'Maharashtra',
        'Manipur',
        'Meghalaya',
        'Mizoram',
        'Nagaland',
        'National Capital Territory of Delhi',
        'Orissa',
        'Puducherry',
        'Punjab',
        'Rajasthan',
        'Sikkim',
        'Tamil Nadu',
        'Tripura',
        'Uttarakhand',
        'Uttar Pradesh',
        'West Bengal',
    );

    protected $town_name = 'Suburban area and City name';

    protected $postcode_name = 'Postal Index Number';

    protected $currency_symbol = 'Rs.';
    protected $currency_decimal = 0;
    protected $currency_name = 'Indian Rupee';


    /**
     * India uses a number formatting system where the first group is three digits,
     * and every subsequent group is two digits.
     * @param int|float The number to format
     * @param int $places The number of decimal places to render
     * @return string
     */
    public function numberFormat($number, $places = 0)
    {
        $matches = null;
        $parts = array();
        $offset = 0;

        $number = round($number, $places);
        list($number, $dec) = sscanf($number, '%d.%d');
        $dec = str_pad($dec, $places, '0');

        $number = strrev($number);
        if (preg_match('/\d\d\d/', $number, $matches)) {
            $parts[] = $matches[0];
            $offset += 3;
        } else {
            return strrev($number) . ($dec ? '.' . $dec : '');
        }

        while (true) {
            if (preg_match('/\d\d/', $number, $matches, 0, $offset)) {
                $parts[] = $matches[0];
                $offset += 2;
            } else {
                $part = substr($number, $offset);
                if ($part) $parts[] = $part;
                break;
            }
        }

        $number = strrev(implode(',', $parts)) . ($dec ? '.' . $dec : '');

        return $number;
    }
}


