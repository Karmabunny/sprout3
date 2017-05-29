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

use DateInterval;
use DateTime;


class SessionStats
{

    /**
     * Track a page view in the session
     */
    public static function trackPageView()
    {
        if (PHP_SAPI == 'cli') return false;

        // Only track non-ajax GET requests
        if (Request::isAjax()) return false;
        if (Request::method() != 'get') return false;

        // Don't track admin and database tools
        if (strpos(Url::current(), 'admin') === 0) return false;
        if (strpos(Url::current(), 'dbtools') === 0) return false;

        Session::instance();

        if (empty($_SESSION['stats'])) {
            $_SESSION['stats'] = [
                'start' => new DateTime(),
                'pageviews' => [],
            ];
        }

        $url = Url::current(false);
        if (empty($_SESSION['stats']['pageviews'][$url])) {
            $_SESSION['stats']['pageviews'][$url] = 1;
        } else {
            $_SESSION['stats']['pageviews'][$url] += 1;
        }
    }


    /**
     * Return the total number of page views
     *
     * @return int Num page views
     */
    public static function totalPageviews()
    {
        if (!empty($_SESSION['stats'])) {
            return array_sum($_SESSION['stats']['pageviews']);
        } else {
            return 0;
        }
    }


    /**
     * Return the total number of unique page views
     *
     * @return int Num page views
     */
    public static function uniquePageviews()
    {
        if (!empty($_SESSION['stats'])) {
            return count($_SESSION['stats']['pageviews']);
        } else {
            return 0;
        }
    }


    /**
     * Return the number of page views for a given url
     *
     * @param int $url URL to return stats for; query strings are stripped
     * @return int Num page views
     */
    public static function numPageviews($url)
    {
        // Strip query string
        $url = preg_replace('!\?.+!', '', $url);

        if (!empty($_SESSION['stats']['pageviews'][$url])) {
            return $_SESSION['stats']['pageviews'][$url];
        } else {
            return 0;
        }
    }


    /**
     * Time the session started
     *
     * @return DateTime
     */
    public static function timeStart()
    {
        if (!empty($_SESSION['stats'])) {
            return $_SESSION['stats']['start'];
        } else {
            return new DateTime();
        }
    }


    /**
     * How long the user has been on the site
     *
     * @return DateInterval
     */
    public static function timeOnSite()
    {
        if (!empty($_SESSION['stats'])) {
            $start = self::timeStart();
            return $start->diff(new DateTime());
        } else {
            return new DateInterval('PT0S');
        }
    }

}
