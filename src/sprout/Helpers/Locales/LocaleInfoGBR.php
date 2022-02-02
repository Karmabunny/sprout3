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
 * Locale info for United Kingdom; see {@see LocaleInfo}
 */
class LocaleInfoGBR extends LocaleInfo
{
    protected $state_name = 'County';
    protected $state_list = array(
        'Aberdeenshire',
        'Anglesey',
        'Angus',
        'Antrim',
        'Argyll (Argyllshire)',
        'Armagh',
        'Ayrshire',
        'Banffshire',
        'Bedfordshire',
        'Berkshire',
        'Berwickshire',
        'Brecknockshire (Breconshire)',
        'Buckinghamshire',
        'Buteshire',
        'Caernarfonshire (Carnarvonshire)',
        'Caithness',
        'Cambridgeshire',
        'Cardiganshire',
        'Carmarthenshire',
        'Cheshire',
        'Clackmannanshire',
        'Cornwall',
        'Cromartyshire',
        'Cumberland',
        'Denbighshire',
        'Derbyshire',
        'Devon',
        'Dorset',
        'Down',
        'Dumbartonshire',
        'Dumfriesshire',
        'Durham',
        'East Lothian',
        'Essex',
        'Fermanagh',
        'Fife',
        'Flintshire',
        'Glamorgan',
        'Gloucestershire',
        'Hampshire',
        'Herefordshire',
        'Hertfordshire',
        'Huntingdonshire',
        'Inverness-shire',
        'Kent',
        'Kincardineshire',
        'Kirkcudbrightshire',
        'Lanarkshire',
        'Lancashire',
        'Leicestershire',
        'Lincolnshire',
        'Londonderry',
        'Merionethshire',
        'Middlesex',
        'Midlothian',
        'Monmouthshire',
        'Montgomeryshire',
        'Morayshire',
        'Nairnshire',
        'Norfolk',
        'Northamptonshire',
        'Northumberland',
        'Nottinghamshire',
        'Orkney',
        'Oxfordshire',
        'Peeblesshire',
        'Pembrokeshire',
        'Perthshire',
        'Radnorshire',
        'Renfrewshire',
        'Ross-shire',
        'Roxburghshire',
        'Rutland',
        'Selkirkshire',
        'Shetland',
        'Shropshire',
        'Somerset',
        'Staffordshire',
        'Stirlingshire',
        'Suffolk',
        'Surrey',
        'Sussex',
        'Sutherland',
        'Tyrone',
        'Warwickshire',
        'West Lothian (Linlithgowshire)',
        'Westmorland',
        'Wigtownshire',
        'Wiltshire',
        'Worcestershire',
        'Yorkshire',
    );

    protected $town_name = 'Suburb';

    protected $postcode_name = 'Postcode';

    protected $currency_symbol = '£';
    protected $currency_name = 'Pound';


    /**
     * Validate a UK postcode
     *
     * The supported formats are as follows:
     * AA9A 9AA
     * A9A 9AA
     * A9 9AA
     * A99 9AA
     * AA9 9AA
     * AA99 9AA
     *
     * @param string $code The postcode to validate
     * @throws ValidationException If the format isn't correct
     */
    public static function validatePostcode($code)
    {
        $allowed_formats = ['AA9A 9AA', 'A9A 9AA', 'A9 9AA', 'A99 9AA', 'AA9 9AA', 'AA99 9AA'];

        foreach ($allowed_formats as $short_format) {
            $format = '/^' . str_replace(['A', '9'], ['[A-Z]', '[0-9]'], $short_format) . '$/';
            if (preg_match($format, $code)) {
                return;
            }
        }

        $err = 'Incorrect format';

        $details = [];
        if (strpos($code, ' ') === false) {
            $details[] = 'space required';
        }
        if (preg_match('/[a-z]/', $code)) {
            $details[] = 'must be uppercase';
        }

        if (count($details) > 0) {
            $err .= ' - ' . implode(', ', $details);
        }

        throw new ValidationException($err);
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
        $valid->check('postcode', 'Validity::length', 6, 8);
    }


    protected $currency_iso = 'GBP';
}
