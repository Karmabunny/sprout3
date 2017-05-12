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


class HomePages
{

    /**
     * Return a randomly selected active banner for given home page
     *
     * @param int $homepage_id ID of the home page record to return promos for
     * @return array Database row; heading, description, link, link_label, filename
     */
    public static function getRandomActiveBanner($homepage_id)
    {
        $q = "SELECT banner.heading, banner.description, banner.link, banner.link_label, file.filename
            FROM ~homepage_banners AS banner
            INNER JOIN ~files AS file ON file.id = banner.file_id
            WHERE banner.active = 1 AND banner.homepage_id = ?
            GROUP BY banner.id
            ORDER BY RAND()
            LIMIT 1";
        return Pdb::query($q, [$homepage_id], 'row');
    }


    /**
     * Return list of active promos for given home page
     *
     * @param int $homepage_id ID of the home page record to return promos for
     * @param int $limit Number of promos to return
     * @return array Multiple database rows; heading, description, link, link_label, filename
     */
    public static function getActivePromos($homepage_id, $limit)
    {
        $q = "SELECT promo.heading, promo.description, promo.link, promo.link_label, file.filename
            FROM ~homepage_promos AS promo
            LEFT JOIN ~files AS file ON file.id = promo.file_id
            WHERE promo.active = 1 AND promo.homepage_id = ?
            GROUP BY promo.id
            ORDER BY promo.record_order
            LIMIT ?";
        return Pdb::query($q, [$homepage_id, $limit], 'arr');
    }

}
