<?php
/**
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 *
 * This class was originally from Kohana 2.3.4
 * Copyright 2007-2008 Kohana Team
 */
namespace Sprout\Helpers;

use DateTime;
use SimpleXMLElement;


class RssFeed
{

    /**
     * Download and parse a RSS feed
     *
     * Supports a subset of RSS 2.0 and also a subset of MRSS (Media RSS) 2.0
     *
     * News items are returned as arrays, with the following keys
     *     name     Title
     *     text     Short desc of the news post. Plain text
     *     date     DateTime object
     *     url      Link URL
     *     image    Optional image URL
     *
     * @param string $url Feed to load and parse
     * @return array News items
     */
    public static function parse($url)
    {
        $feed = HttpReq::get($url);

        $simple = new SimpleXMLElement($feed);

        $out = [];
        foreach ($simple->channel->item as $rss_item) {
            $out_item = [
                'name' => (string)$rss_item->title,
                'date' => new DateTime($rss_item->pubDate),
                'url' => (string)$rss_item->link,
            ];

            $text = (string)$rss_item->description;
            $text = html_entity_decode($text);
            $text = strip_tags($text);
            $out_item['text'] = $text;

            $media = $rss_item->children('http://search.yahoo.com/mrss/');
            foreach($media as $media_item) {
                $attrs = $media_item->attributes();
                if ((string)$attrs['medium'] == 'image') {
                    $out_item['image'] = (string)$attrs['url'];
                }
            }

            $out[] = $out_item;
        }

        return $out;
    }

}
