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

class BbCode
{

    /**
    * Convert an inline run of plain text into HTML.
    *
    * Supports the following bbcode tags:
    *    [b] [i] [u] [s] [code] [url=...]
    *
    * @param string $text Plain text to convert
    * @param array $tags Optional array of tags names (e.g. 'b') to process. Provide NULL for defaults (b, i, code, url)
    * @return string HTML
    **/
    public static function inline($text, $tags = null)
    {
        $text = Enc::cleanfunky($text);
        $text = Enc::html($text);

        if ($tags === null) {
            $tags = array('b', 'i', 'code', 'url');
        }

        if (in_array('b', $tags)) $text = preg_replace('/\[b\](.+?)\[\/b\]/', '<b>$1</b>', $text);
        if (in_array('i', $tags)) $text = preg_replace('/\[i\](.+?)\[\/i\]/', '<i>$1</i>', $text);
        if (in_array('u', $tags)) $text = preg_replace('/\[u\](.+?)\[\/u\]/', '<u>$1</u>', $text);
        if (in_array('s', $tags)) $text = preg_replace('/\[s\](.+?)\[\/s\]/', '<s>$1</s>', $text);
        if (in_array('code', $tags)) $text = preg_replace('/\[code\](.+?)\[\/code\]/', '<code>$1</code>', $text);
        if (in_array('url', $tags)) $text = preg_replace('/\[url=(.+?)\](.+?)\[\/url\]/', '<a href="$1">$2</a>', $text);

        return $text;
    }


    /**
    * Convert multi-line text into HTML, with support for bbcode.
    * The output will be surrounded by one or more P tags as appropriate
    *
    * @param string $text Plain text to convert
    * @param array $tags Optional array of tags names (e.g. 'b') to process. Provide NULL for defaults (b, i, code, url)
    * @return string HTML
    **/
    public static function block($text, $tags = null)
    {
        $text = self::inline($text, $tags);
        $text = Text::widont($text);
        $text = Text::autoP($text);
    }

}
