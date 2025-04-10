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
 * Locale info for Mexico; see {@see LocaleInfo}
 */
class LocaleInfoMEX extends LocaleInfo
{
    protected $state_list = [
        'AGU' => 'Aguascalientes',
        'BCN' => 'Baja California',
        'BCS' => 'Baja California Sur',
        'CAM' => 'Campeche',
        'CHH' => 'Chihuahua',
        'CHP' => 'Chiapas',
        'COA' => 'Coahuila',
        'COL' => 'Colima',
        'DIF' => 'Distrito Federal',
        'DUR' => 'Durango',
        'GRO' => 'Guerrero',
        'GUA' => 'Guanajuato',
        'HID' => 'Hidalgo',
        'JAL' => 'Jalisco',
        'MEX' => 'México',
        'MIC' => 'Michoacán',
        'MOR' => 'Morelos',
        'NAY' => 'Nayarit',
        'NLE' => 'Nuevo León',
        'OAX' => 'Oaxaca',
        'PUE' => 'Puebla',
        'QUE' => 'Querétaro',
        'ROO' => 'Quintana Roo',
        'SIN' => 'Sinaloa',
        'SLP' => 'San Luis Potosí',
        'SON' => 'Sonora',
        'TAB' => 'Tabasco',
        'TAM' => 'Tamaulipas',
        'TLA' => 'Tlaxcala',
        'VER' => 'Veracruz',
        'YUC' => 'Yucatán',
        'ZAC' => 'Zacatecas',
    ];

    protected $currency_iso = 'MXN';
    protected $phone_code = '52';

}
