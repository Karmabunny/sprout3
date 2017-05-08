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

use Kohana;


/**
* Has methods for outputting links for submitting to a number of social networking sites
**/
class SocialNetworking
{
    private static $url;
    private static $title;
    private static $desc;


    /**
    * Set the share details to use when submitting to the various websites.
    **/
    public static function details($title, $desc, $url = null)
    {
        if (! $url) $url = 'http://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];

        $title = Text::limitChars(strip_tags($title), 100, '...');
        $desc = Text::limitChars(strip_tags($desc), 1000, '...');

        self::$url = $url;
        self::$title = $title;
        self::$desc = $desc;
    }


    /**
    * Checks if there are details available
    **/
    public static function hasdetails()
    {
        if (self::$url and self::$title) return true;
        return false;
    }


    public static function getUrl()
    {
        return self::$url;
    }

    public static function getTitle()
    {
        return self::$title;
    }

    public static function getDesc()
    {
        return self::$desc;
    }


    /**
    * Get the A tag for a facebook share link
    **/
    public static function facebookLink($class = '')
    {
        $share_url = 'http://www.facebook.com/sharer.php?u=' . Enc::url(self::$url) . '&t=' . Enc::url(self::$title);

        $share_url = Enc::html($share_url);
        $class = Enc::html($class);

        return "<a href=\"{$share_url}\" onclick=\"window.open(this.href, 'Share', 'width=800,height=500'); return false;\" class=\"{$class}\">";
    }

    /**
    * Get a facebook share button
    **/
    public static function facebook($icon = null)
    {
        if (! $icon) $icon = 'SKIN/images/icon_facebook-white.svg';

        echo "<div class='share-link share-link--facebook'>";
        echo self::facebookLink();
        echo "<img src=\"{$icon}\" alt=\"Facebook\" title=\"Facebook\">";
        echo "</a>";
        echo "</div>";
    }


    /**
    * Get the A tag for a twitter share link
    **/
    public static function twitterLink($tweet = null, $class = '')
    {
        if (! $tweet) $tweet = self::$title . ' - ' . Kohana::config('sprout.site_title') . ' ' . self::$url;
        $share_url = 'http://twitter.com/home?status=' . Enc::url($tweet);

        $share_url = Enc::html($share_url);
        $class = Enc::html($class);

        return "<a href=\"{$share_url}\" onclick=\"window.open(this.href, 'Share', 'width=800,height=500'); return false;\" class=\"{$class}\">";
    }

    /**
    * Output a twitter share button
    **/
    public static function twitter($icon = null, $tweet = null)
    {
        if (! $icon) $icon = 'SKIN/images/icon_twitter-white.svg';

        echo "<div class='share-link share-link--twitter'>";
        echo self::twitterLink($tweet);
        echo "<img src=\"{$icon}\" alt=\"Twitter\" title=\"Twitter\">";
        echo "</a>";
        echo "</div>";
    }


    /**
    * Output a linkedin share button
    **/
    public static function linkedin($icon = null)
    {
        $url = Enc::url(self::$url);
        $title = Enc::url(self::$title);
        $desc = Enc::url(self::$desc);

        if (! $icon) $icon = 'SKIN/images/icon_linkedin-white.svg';

        $share_url = "http://www.linkedin.com/shareArticle?mini=true&url={$url}&title={$title}&summary={$desc}";
        $share_url = Enc::html($share_url);

        echo "<div class='share-link share-link--linkedin'>";
        echo "<a href=\"{$share_url}\" onclick=\"window.open(this.href, 'Share', 'width=800,height=500'); return false;\">";
        echo "<img src=\"{$icon}\" alt=\"LinkedIn\" title=\"LinkedIn\">";
        echo "</a>";
        echo "</div>";
    }


    /**
    * Get the A tag for an email share link
    **/
    public static function emailLink()
    {
        $share_url = 'email_share/share?url=' . Enc::url(self::$url) . '&title=' . Enc::url(self::$title);
        $share_url = Enc::html($share_url);

        return '<a href="' . $share_url . '">';
    }

    /**
    * Output a email share button
    **/
    public static function email($icon = null)
    {
        if (! $icon) $icon = 'SKIN/images/icon_email-white.svg';

        echo "<div class='share-link share-link--email'>";
        echo self::emailLink();
        echo "<img src=\"{$icon}\" alt=\"Email\" title=\"Email\">";
        echo "</a>";
        echo "</div>";
    }


    /**
    * Return a link to the main page for a given social media platform
    *
    * @param string $type The network type to display, e.g. 'facebook'
    * @param bool $new_window Should the link be in a new window?
    * @return string The opening A link HTML
    **/
    public static function pageLink($type, $new_window = true)
    {
        $link = Kohana::config('sprout.social_media.' . $type);

        if (! $type or ! $link) {
            $link = 'javascript:;" style="box-shadow: 0 0 5px 5px #f00" title="no link target set in skin config!';
        }
        $out = "<a href=\"{$link}\"";
        if ($new_window) $out .= ' target="_blank" ';
        $out .= ">";

        return $out;
    }

}


