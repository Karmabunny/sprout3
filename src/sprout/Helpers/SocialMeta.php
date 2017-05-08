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


/**
 * Management of social meta data tags, such as Facebook OpenGraph and Twitter Cards
 */
class SocialMeta
{
    /**
     * Tags of the form <meta property=":key" content=":val">
     */
    private static $meta_property = [];

    /**
     * Tags of the form <meta name=":key" content=":val">
     */
    private static $meta_name = [];


    /**
     * Set a title for this page. Don't include the site name
     *
     * @param string $val
     */
    public static function setTitle($val)
    {
        self::$meta_property['og:title'] = $val;
    }


    /**
     * Has a page title been set?
     *
     * @return bool True if a title has been set
     */
    public static function hasTitle()
    {
        return !empty(self::$meta_property['og:title']);
    }


    /**
     * Set the image for this page - should be unique to this page; not shared across the site
     *
     * @param string $url Relative or absolute url
     */
    public static function setImage($url)
    {
        if (preg_match('!^[0-9]+$!', $url)) {
            $url = File::url($url);
        }
        if (!preg_match('!^https?://!', $url)) {
            $url = Sprout::absRoot() . $url;
        }
        self::$meta_property['og:image'] = $url;
    }


    /**
     * Set a short (up to two sentence) description for this page
     *
     * @param string $val
     */
    public static function setDescription($val)
    {
        self::$meta_property['og:description'] = $val;
    }


    /**
     * Set the canonical url for this page
     *
     * @param string $url Relative or absolute url
     */
    public static function setUrl($url)
    {
        if (!preg_match('!^https?://!', $url)) {
            $url = Sprout::absRoot() . $url;
        }
        self::$meta_property['og:url'] = $url;
    }


    /**
     * The page type, i.e. "og:type" property
     *
     * Current options are:
     *   article, book, profile, website, music.song, music.album, music.playlist,
     *   music.radio_station, video.movie, video.episode, video.tv_show, video.other
     *
     * @param string $val
     */
    public static function setPageType($val)
    {
        self::$meta_property['og:type'] = $val;
    }


    /**
     * The twitter card type, i.e. "twitter:card" property
     *
     * Current options are:
     *   summary, summary_large_image, app, player
     * Note that the player card requires approval from Twitter prior to use
     *
     * @param string $val
     */
    public static function setTwitterCardType($val)
    {
        self::$meta_property['twitter:card'] = $val;
    }


    /**
     * Set a custom OpenGraph string property
     *
     * @param string Property name, e.g. 'article:section'
     * @param string $value Property value, e.g. 'Technology'
     */
    public static function setOpenGraphString($property, $value)
    {
        self::$meta_property[$property] = trim($value);
    }


    /**
     * Set a custom OpenGraph integer property
     *
     * @param string Property name, e.g. 'music:album:track'
     * @param int $value Property value, e.g. 4
     */
    public static function setOpenGraphInteger($property, $value)
    {
        self::$meta_property[$property] = (int)$value;
    }


    /**
     * Set a custom OpenGraph date property
     *
     * @param string $property Property name, e.g. 'article:published_time'
     * @param DateTime|string $value A DateTime or anything paresable by the DateTime constructor
     */
    public static function setOpenGraphDate($property, $value)
    {
        if (!($value instanceof DateTime)) {
            $value = new DateTime($value);
        }
        self::$meta_property[$property] = $value->format('c');
    }


    /**
     * Auto-generate missing OpenGraph and Twitter cards meta data.
     */
    protected static function autoGenerateMissing()
    {
        if (empty(self::$meta_property['og:url'])) {
            self::$meta_property['og:url'] = Sprout::absRoot() . Url::current(true);
        }
        if (empty(self::$meta_property['og:site_name'])) {
            self::$meta_property['og:site_name'] = Kohana::config('sprout.site_title');
        }
        if (empty(self::$meta_property['og:type'])) {
            self::$meta_property['og:type'] = 'website';
        }
        if (empty(self::$meta_name['twitter:card'])) {
            self::$meta_name['twitter:card'] = 'summary';
        }
        if (empty(self::$meta_name['twitter:site'])) {
            $acct = Kohana::config('sprout.site_twitter');
            if ($acct) {
                self::$meta_name['twitter:site'] = '@' . ltrim($acct, '@');
            }
        }
    }


    /**
     * Render the social metadata
     *
     * @return string HTML
     */
    public static function render()
    {
        static::autoGenerateMissing();
        $out = '';
        foreach (self::$meta_property as $property => $content) {
            $out .= '<meta property="' . Enc::html($property) . '" content="' . Enc::html($content) . '">' . PHP_EOL;
        }
        foreach (self::$meta_name as $name => $content) {
            $out .= '<meta name="' . Enc::html($name) . '" content="' . Enc::html($content) . '">' . PHP_EOL;
        }
        return $out;
    }

}
