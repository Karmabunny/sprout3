<?php
/* Copyright (C) 2024 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://sproutcms.com.au>.
 */

namespace Sprout\Helpers;

use Sprout\Exceptions\QueryException;
use Sprout\Exceptions\RowMissingException;
use Sprout\Helpers\Register;


class SiteSettings
{
    /**
     * Return a list of settings matching the given key
     *
     * @param string $key Setting to find
     * @return array List of matching values
     */
    public static function getList($key)
    {
        $q = "SELECT value FROM ~site_settings WHERE name = ?";

        try {
            return Pdb::query($q, [$key], 'col');
        } catch (QueryException $ex) {
            return [];
        }
    }


    /**
     * Return a single setting matching the given key first listed by record order
     *
     * @param string $key Setting to find
     * @return string|null value of setting or null if no match
     */
    public static function getSingle($key)
    {
        $q = "SELECT value FROM ~site_settings WHERE name = ? ORDER BY record_order ASC LIMIT 1";

        try {
            return Pdb::query($q, [$key], 'val');
        } catch (RowMissingException $ex) {
            return null;
        } catch (QueryException $ex) {
            return null;
        }
    }


    /**
     * Fetch site settings as key value pairs
     *
     * @return array [key => value] pairs
     */
    public static function getListAsKeyValues()
    {
        return array_combine(Register::getSiteSettings(), Register::getSiteSettings());
    }
}
