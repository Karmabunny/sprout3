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
     * Prefixes of URLs to ignore
     */
    private static $untracked = [
        'admin',
        'dbtools',
        'email_share',
        'embed_video',
        'file',
        'page',
        'result',
        'robots.txt',
        'search',
        'seo/xmlSitemap',
        'tinymce4',
    ];


    /**
     * Track a page view in the session
     */
    public static function trackPageView()
    {
        if (PHP_SAPI == 'cli') return false;

        // Only track non-ajax GET requests
        if (Request::isAjax()) return false;
        if (Request::method() != 'get') return false;

        // Prefixes of URLs to ignore
        foreach (self::$untracked as $prefix) {
            if (strpos(Url::current(), $prefix) === 0) return false;
        }

        Session::instance();

        if (empty($_SESSION['stats'])) {
            $_SESSION['stats'] = [
                'start' => new DateTime(),
                'pageviews' => [],
                'referrer' => Request::referrer(),
            ];
        }

        $url = Url::current(false);
        if (empty($_SESSION['stats']['pageviews'][$url])) {
            $_SESSION['stats']['pageviews'][$url] = 1;
        } else {
            $_SESSION['stats']['pageviews'][$url] += 1;
        }

        // Store any provided UTM parameters
        $utm_params = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
        foreach ($utm_params as $param) {
            if (isset($_GET[$param])) {
                $_SESSION['stats'][$param] = $_GET[$param];
            }
        }

        // If no UTM params but the referrer gives it away, then auto-generate the params
        if (!empty($_SESSION['stats']['referrer']) and empty($_SESSION['stats']['utm_source'])) {
            list($source, $medium) = self::autoDetectSourceMedium($_SESSION['stats']['referrer']);
            if (!empty($source)) {
                $_SESSION['stats']['utm_source'] = $source;
            }
            if (!empty($medium)) {
                $_SESSION['stats']['utm_medium'] = $medium;
            }
        }
    }


    /**
     * Auto-generate UTM params based on the initial request referrer
     *
     * @param string $referrer Full URL, e.g. 'https://facebook.com/post/987654321
     * @return array Two strings or NULL if not known; 0 => source, 1 => medium
     */
    private static function autoDetectSourceMedium($referrer)
    {
        $host = parse_url($referrer, PHP_URL_HOST);
        $host = preg_replace('/^(www|m)\./', '', $host);

        switch ($host) {
            case 'facebook.com': return ['Facebook', 'social'];
            case 'twitter.com': return ['Twitter', 'social'];
            case 'youtube.com': return ['YouTube', 'social'];
        }

        return [$host, 'referral'];
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


    /**
     * Value of the 'utm_source' query string parameter
     * from the most recent request which contained this parameter
     *
     * @return string UTM request source, e.g. 'google', 'bing', 'facebook', etc
     */
    public static function utmSource()
    {
        return @$_SESSION['stats']['utm_source'];
    }


    /**
     * Value of the 'utm_medium' query string parameter
     * from the most recent request which contained this parameter
     *
     * @return string UTM request medium, e.g. 'cpc', 'organic', 'social', 'email', etc
     */
    public static function utmMedium()
    {
        return @$_SESSION['stats']['utm_medium'];
    }


    /**
     * Value of the 'utm_campaign' query string parameter
     * from the most recent request which contained this parameter
     *
     * @return string UTM request campaign, e.g. 'spring_sale'
     */
    public static function utmCampaign()
    {
        return @$_SESSION['stats']['utm_campaign'];
    }


    /**
     * Value of the 'utm_term' query string parameter
     * from the most recent request which contained this parameter
     *
     * @return string UTM request term, e.g. 'running shoes'
     */
    public static function utmTerm()
    {
        return @$_SESSION['stats']['utm_term'];
    }


    /**
     * Value of the 'utm_content' query string parameter
     * from the most recent request which contained this parameter
     *
     * @return string UTM request term, e.g. 'logolink' or 'textlink'
     */
    public static function utmContent()
    {
        return @$_SESSION['stats']['utm_content'];
    }

}
