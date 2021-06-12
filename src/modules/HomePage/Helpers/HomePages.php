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

namespace SproutModules\Karmabunny\HomePage\Helpers;

use Sprout\Helpers\Pdb;
use Sprout\Helpers\SubsiteSelector;


/**
 * Functions for dealing with promo and banner images on the home page(s)
 */
class HomePages
{
    /**
     * Return home page record for given sub-site
     * @param int $subsite_id Sub-site record ID. Default of zero/auto
     * @return array Single database row
     * @throws QueryException
     */
    public static function getForSubSite($subsite_id = 0)
    {
        $subsite_id = (int) $subsite_id;
        if ($subsite_id < 1) $subsite_id = SubsiteSelector::$subsite_id;

        $q = "SELECT home.*
            FROM ~homepages AS home
            WHERE home.subsite_id = ?";

        return Pdb::query($q, [$subsite_id], 'row');
    }


    /**
     * Return list of active banners for given home page
     *
     * @param int $homepage_id Home page record ID
     * @param int $limit Optional number of results. Default of zero/unlimited
     * @return array Multiple database rows; heading, description, link, link_label, filename
     */
    public static function getActiveBanners($homepage_id, $limit = 0)
    {
        $limit = (int) $limit;

        $q = "SELECT
                banner.heading,
                banner.description,
                banner.link,
                banner.link_label,
                file.filename
            FROM
                ~homepage_banners AS banner
            LEFT JOIN
                ~files AS file
                ON file.id = banner.file_id
            WHERE
                banner.active != 0
                AND banner.homepage_id = ?
            GROUP BY
                banner.id
            ORDER BY
                banner.record_order";

        if ($limit > 0) $q .= sprintf(' LIMIT %u', $limit);

        return Pdb::query($q, [$homepage_id], 'arr');
    }


    /**
     * Return list of active promos for given home page
     *
     * @param int $homepage_id Home page record ID
     * @param int $limit Optional number of results. Default of zero/unlimited
     * @return array Multiple database rows; heading, description, link, link_label, filename
     */
    public static function getActivePromos($homepage_id, $limit)
    {
        $limit = (int) $limit;

        $q = "SELECT
                promo.heading,
                promo.description,
                promo.link,
                promo.link_label,
                file.filename
            FROM
                ~homepage_promos AS promo
            LEFT JOIN
                ~files AS file
                ON file.id = promo.file_id
            WHERE
                promo.active != 0
                AND promo.homepage_id = ?
            GROUP BY
                promo.id
            ORDER BY
                promo.record_order";

        if ($limit > 0) $q .= sprintf(' LIMIT %u', $limit);

        return Pdb::query($q, [$homepage_id], 'arr');
    }


    /**
     * Return a randomly selected active banner for given home page
     *
     * @param int $homepage_id Home page record ID
     * @return array Single database row; heading, description, link, link_label, filename
     * @throws QueryException
     */
    public static function getRandomActiveBanner($homepage_id)
    {
        $q = "SELECT
                banner.heading,
                banner.description,
                banner.link,
                banner.link_label,
                file.filename
            FROM
                ~homepage_banners AS banner
            INNER JOIN
                ~files AS file
                ON file.id = banner.file_id
            WHERE
                banner.active != 0
                AND banner.homepage_id = ?
            GROUP BY
                banner.id
            ORDER BY
                RAND()
            LIMIT 1";

        return Pdb::query($q, [$homepage_id], 'row');
    }
}
