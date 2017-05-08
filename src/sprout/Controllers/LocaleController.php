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

namespace Sprout\Controllers;

use Sprout\Helpers\Locales\LocaleInfo;


/**
 * For things related to Locales
 */
class LocaleController extends Controller
{
    /**
     * Extracts a prefix for use with address fields from $_GET['prefix']
     *
     * If the get param is invalid, it is ignored
     *
     * @return string
     */
    protected function getPrefix()
    {
        $prefix = (string) @$_GET['prefix'];
        if (!preg_match('/^[a-z][_a-z0-9]*$/i', $prefix)) {
            return '';
        }
        return $prefix;
    }


    /**
     * Outputs fields for an address which is not mandatory
     *
     * @param string $country The country to return fields for
     */
    public function getAddressFields($country)
    {
        echo LocaleInfo::get($country)->outputAddressFields($this->getPrefix(), false);
    }


    /**
     * Outputs fields for an address which is mandatory
     *
     * @param string $country The country to return fields for
     */
    public function getAddressFieldsRequired($country)
    {
        echo LocaleInfo::get($country)->outputAddressFields($this->getPrefix(), true);
    }
}
