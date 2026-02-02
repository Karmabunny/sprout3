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

use DOMDocument;


/**
* No description yet.
**/
class EmbedVideo
{

    const TYPE_YOUTUBE = 1;
    const TYPE_VIMEO = 2;


    /**
    * Return HTML for embedding a video, based on the URL.
    * Supports YouTube and Vimeo.
    *
    * Options include:
    *    bool    autoplay   Begin playing the video straight away
    *    string  title      Title attribute
    *
    * @param string $url
    * @param int $width
    * @param int $height
    * @param array $options
    **/
    public static function renderEmbed($url, $width = 400, $height = 300, array $options = array()) {
        $url = trim(Enc::cleanfunky($url));
        $width = (int) $width;
        $height = (int) $height;

        $idtype = self::getVideoIdType($url);
        if ($idtype == null) return null;

        // Determine URL for player
        list($type, $video_id) = $idtype;
        $embed_url = '';
        switch ($type) {
            case self::TYPE_YOUTUBE:
                $embed_url = '//www.youtube.com/embed/' . $video_id . '?rel=0&wmode=transparent&showinfo=0';
                break;

            case self::TYPE_VIMEO:
                $embed_url = '//player.vimeo.com/video/' . $video_id . '?title=0&byline=0&portrait=0&color=00ADEF&fullscreen=0';
                break;
            default:
                return null;
        }

        // Both players use the same GET param name for this flag
        if (isset($options['autoplay']) and $options['autoplay'] == true) {
            $embed_url .= '&autoplay=1';
        }

        // Ensure there is a title tag for WCAG compliance
        if (empty($options['title'])) {
            $options['title'] = 'Embedded video';
        }

        return '<iframe '
            . 'width="' . $width . '" height="' . $height. '" '
            . 'src="' . Enc::html($embed_url) . '" '
            . 'title="' . Enc::html($options['title']) . '" '
            . 'frameborder="0" webkitAllowFullScreen mozallowfullscreen allowFullScreen>'
            . '<a href="' . Enc::html($url) . '">Watch video</a>'
            . '</iframe>';
    }


    /**
    * Get a thumbnail as a full absolute URL)
    *
    * @param $quality Quality. Higher is better
    **/
    public static function getThumbFilename($url, $quality = 1)
    {
        $url = trim($url);

        $idtype = self::getVideoIdType($url);
        if ($idtype == null) return null;

        list($type, $video_id) = $idtype;
        switch ($type) {
            case self::TYPE_YOUTUBE:
                if ($quality == 2) {
                    return "//i1.ytimg.com/vi/{$video_id}/maxresdefault.jpg";
                } else {
                    return "//i1.ytimg.com/vi/{$video_id}/hqdefault.jpg";
                }
                break;

            case self::TYPE_VIMEO:
                $dom = new DOMDocument();
                $vimeo_html = @file_get_contents("http://vimeo.com/{$video_id}");
                @$dom->loadHTML($vimeo_html);

                $metas = $dom->getElementsByTagName('meta');
                foreach ($metas as $m) {
                    if ($m->getAttribute('property') == 'og:image') {
                        return trim($m->getAttribute('content'));
                    }
                }
                break;
        }

        return null;
    }


    /**
    * Return the URL for our dynamic thumbnail resizer for a given video url
    *
    * Arguments are the same as `File::resizeUrl`
    * Size spec only supports crop at this time (e.g. c200x150)
    **/
    public static function resizedThumb($url, $sizespec)
    {
        $url = trim($url);

        $idtype = self::getVideoIdType($url);
        if ($idtype == null) return null;

        list($type, $video_id) = $idtype;

        return 'embed_video/thumb/' . $sizespec . '/' . $type. '/' . $video_id;
    }


    /**
    * Parse the URL into a video-id and type
    **/
    public static function getVideoIdType($url)
    {
        $url = Enc::cleanfunky($url);
        if (! preg_match('!^https?://!', $url)) return null;

        // Get the host of the URL
        $urlparts = parse_url($url);
        if (! $urlparts['host']) return null;

        if (strpos($urlparts['host'], 'youtube.com') !== false) {
            // YouTube
            if (strpos($urlparts['path'], '/v/') === 0) {
                return array(self::TYPE_YOUTUBE, substr($urlparts['path'], 3));

            } else {
                $query_parts = explode('&', $urlparts['query'] ?? '');
                foreach ($query_parts as $part) {
                    @list($key, $value) = explode('=', $part, 2);
                    if ($key == 'v') {
                        return array(self::TYPE_YOUTUBE, $value);
                    }
                }
            }

        } else if (strpos($urlparts['host'], 'youtu.be') !== false) {
            // YouTube
            $path = parse_url ($url, PHP_URL_PATH);
            return array(self::TYPE_YOUTUBE, trim($path, '/'));


        } else if (strpos($urlparts['host'], 'vimeo.com') !== false) {
            // Vimeo
            $path = preg_replace('/[^0-9]/', '', $urlparts['path']);
            $path = (int) $path;

            if ($path != 0) {
                return array(self::TYPE_VIMEO, $path);
            }

        }

    }


}
