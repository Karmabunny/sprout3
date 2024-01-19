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
use DateTimeInterface;
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
     * Add a needs entry for a meta 'name' tag of the specified name and value
     *
     * @param string $name The name of the tag
     * @param string $value The value of the tag
     *
     * @return void
     */
    private static function addMetaNeedName(string $name, string $value)
    {
        $value = trim($value);

        if (isset(self::$meta_name[$name])
            and self::$meta_name[$name] == $value)
        {
            return;
        }

        self::$meta_name[$name] = $value;

        Needs::addMetaName($name, $value);
    }

    /**
     * Add a needs entry for a meta 'property' tag of the specified property and value
     *
     * @param string $property The property name
     * @param string|int $value The property value
     *
     * @return void
     */
    private static function addMetaNeedProperty(string $property, string|int $value)
    {
        $value = trim($value);

        if (isset(self::$meta_property[$property])
            and self::$meta_property[$property] == $value)
        {
            return;
        }

        self::$meta_property[$property] = $value;

        Needs::addMetaProperty($property, $value);
    }


    /**
     * Set a title for this page. Don't include the site name
     *
     * @param string $val
     */
    public static function setTitle($val)
    {
        $val = (string) $val;
        self::addMetaNeedName('twitter:title', $val);
        self::addMetaNeedProperty('og:title', $val);
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
     * @param string|null $url Relative or absolute url
     */
    public static function setImage($url)
    {
        $url = (string) $url;

        if (preg_match('!^[0-9]+$!', $url)) {
            $url = File::url($url);
        }
        if (!preg_match('!^https?://!', $url)) {
            $url = Sprout::absRoot() . $url;
        }

        self::addMetaNeedName('twitter:image', $url);
        self::addMetaNeedProperty('og:image', $url);
    }


    /**
     * Set a short (up to two sentence) description for this page
     *
     * @param string $val
     */
    public static function setDescription($val)
    {
        $val = (string) $val;
        self::addMetaNeedName('twitter:description', $val);
        self::addMetaNeedProperty('og:description', $val);
    }


    /**
     * Set the canonical url for this page
     *
     * @param string $url Relative or absolute url
     */
    public static function setUrl($url)
    {
        $url = (string) $url;

        if (!preg_match('!^https?://!', $url)) {
            $url = Sprout::absRoot() . $url;
        }

        self::addMetaNeedName('twitter:url', $url);
        self::addMetaNeedProperty('og:url', $url);
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
        $val = (string) $val;
        self::addMetaNeedProperty('og:type', $val);
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
        $val = (string) $val;
        self::addMetaNeedName('twitter:card', $val);
    }


    /**
     * Set a custom OpenGraph string property
     *
     * @param string $property Property name, e.g. 'article:section'
     * @param string $value Property value, e.g. 'Technology'
     */
    public static function setOpenGraphString($property, $value)
    {
        self::addMetaNeedProperty((string) $property, (string) $value);
    }


    /**
     * Set a custom OpenGraph integer property
     *
     * @param string $property Property name, e.g. 'music:album:track'
     * @param int $value Property value, e.g. 4
     */
    public static function setOpenGraphInteger($property, $value)
    {
        self::addMetaNeedProperty((string) $property, (int) $value);
    }


    /**
     * Set a custom OpenGraph date property
     *
     * @param string $property Property name, e.g. 'article:published_time'
     * @param DateTimeInterface|string $value A DateTime or anything paresable by the DateTime constructor
     */
    public static function setOpenGraphDate($property, $value)
    {
        if (!($value instanceof DateTimeInterface)) {
            $value = new DateTime($value);
        }

        $property = (string) $property;
        self::addMetaNeedProperty($property, $value->format('c'));
    }


    /**
     * Auto-generate missing OpenGraph and Twitter cards meta data.
     */
    protected static function autoGenerateMissing()
    {
        if (empty(self::$meta_property['og:url'])) {
            $val = Sprout::absRoot() . Url::current(true);
            self::addMetaNeedName('twitter:url', $val);
            self::addMetaNeedProperty('og:url', $val);
        }
        if (empty(self::$meta_property['og:site_name'])) {
            $val = Kohana::config('sprout.site_title');
            self::addMetaNeedProperty('og:site_name', $val);
        }
        if (empty(self::$meta_property['og:type'])) {
            self::addMetaNeedProperty('og:type', 'website');

        }
        if (empty(self::$meta_name['twitter:card'])) {
            self::addMetaNeedName('twitter:card', 'summary');
        }
        if (empty(self::$meta_name['twitter:site'])) {
            $acct = Kohana::config('sprout.site_twitter');
            if ($acct) {
                $val = '@' . ltrim($acct, '@');
                self::addMetaNeedName('twitter:site', $val);
            }
        }
    }


    /**
     * Render the social metadata
     *
     * @deprecated Handled by Needs already
     */
    public static function render()
    {
        return '';
    }


    /**
     *
     * @return array [ properties[], names[] ]
     */
    public static function get()
    {
        return [
            'properties' => self::$meta_property,
            'names' => self::$meta_name,
        ];
    }

}
