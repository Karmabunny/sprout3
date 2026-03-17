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
 * Locale info for Portugal; see {@see LocaleInfo}
 */
class LocaleInfoPRT extends LocaleInfoEurozone
{
    protected $state_list = [
        'Aveiro',
        'Beja',
        'Braga',
        'Bragança',
        'Castelo Branco',
        'Coimbra',
        'Évora',
        'Faro',
        'Guarda',
        'Leiria',
        'Lisboa',
        'Portalegre',
        'Porto',
        'Santarém',
        'Setúbal',
        'Viana do Castelo',
        'Vila Real',
        'Viseu',
        'Região Autónoma dos Açores',
        'Região Autónoma da Madeira',
    ];


    protected $phone_code = '351';

}
