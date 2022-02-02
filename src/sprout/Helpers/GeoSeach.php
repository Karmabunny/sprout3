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
namespace Sprout\Helpers;

use DateTime;
use Kohana;

use Sprout\Exceptions\RowMissingException;
use Sprout\Helpers\HttpReq;
use Sprout\Helpers\Pdb;


class GeoSeach
{
    /**
     * Return lat, lng by given query
     *
     * @param string $request Typically an address
     * @return array [lat => float, lng => float]
     * @return null No result found
     */
    public static function getByQuery($request)
    {
        try {
            $q = "SELECT geo.lat, geo.lng
                FROM ~geosearch_cache AS geo
                WHERE query = ?
                LIMIT 1";
            $row = Pdb::query($q, [$request], 'row');

            if ($row['lat'] == 0 and $row['lng'] == 0) {
                return null;
            } else {
                return $row;
            }

        } catch (RowMissingException $ex) {
            // No cache record, so do a lookup instead
        }

        $opts = [];
        $opts['method'] = 'GET';
        $opts['headers'] = [
            'User-Agent' => sprintf('PHP/%s Sprout/%u (%s)', PHP_VERSION, Kohana::config('core.version_brand'), Kohana::config('sprout.site_title')),
        ];

        $params = [];
        $params['q'] = $request;
        $params['format'] = 'json';
        $url = 'https://nominatim.openstreetmap.org/search?' . http_build_query($params);

        $result = HttpReq::req($url, $opts);
        $result = json_decode($result);

        $dt = new DateTime();
        $dt->modify('+ 6 months');

        $data = [];
        $data['query'] = $request;
        $data['lat'] = !empty($result[0]->lat) ? $result[0]->lat : 0;
        $data['lng'] = !empty($result[0]->lon) ? $result[0]->lon : 0;
        $data['date_expiry'] = $dt->format('Y-m-d');
        Pdb::insert('geosearch_cache', $data);

        if (count($result) == 0) {
            return null;
        } else {
            return ['lat' => $result[0]->lat, 'lng' => $result[0]->lon];
        }
    }
}
