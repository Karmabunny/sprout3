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



/**
 * Various text helpers such as limiting.
 */
class Text
{

    /**
     * Limits a plain-text phrase to a given number of words.
     *
     * @param string $str Phrase to limit words of, in plain text
     * @param int $limit Number of words to limit to
     * @param string $end_char Characters to append if text is limited, e.g. '...'
     * @return string Plain text
     */
    public static function limitWords($str, $limit = 100, $end_char = NULL)
    {
        $limit = (int) $limit;
        $end_char = ($end_char === NULL) ? '…' : $end_char;
        $str = preg_replace('/^\s*|\s*$/u', '', $str);

        if (empty($str)) return $str;
        if ($limit <= 0) return $end_char;

        $result = preg_match('/^\s*+(?:\S++\s*+){1,'.$limit.'}/u', $str, $matches);

        if ($result) {
            // Only attach the end character if the matched string is shorter
            // than the starting string.
            return rtrim($matches[0]).(strlen($matches[0]) === strlen($str) ? '' : $end_char);
        }

        // Alternate method if something breaks.
        $words = preg_split("/\s+/", $str, $limit + 1);

        if ($words !== false) {
            if (empty($words)) {
                return '';
            }

            $words = array_slice($words, 0, $limit);
            return implode(' ', $words) . $end_char;
        }

        // It's just broken.
        return $end_char;
    }

    /**
     * Limits a plain-text phrase to a given number of characters.
     *
     * @param string $str Phrase to limit characters of, in plain text
     * @param int $limit Number of characters to limit to
     * @param string $end_char Characters to append if text is limited, e.g. '...'
     * @param bool $preserve_words True if whole words should be preserved; false to allow ending on a partial word
     * @return string Plain text
     */
    public static function limitChars($str, $limit = 100, $end_char = NULL, $preserve_words = FALSE)
    {
        $end_char = ($end_char === NULL) ? '…' : $end_char;

        $limit = (int) $limit;

        if (trim($str) === '' OR mb_strlen($str) <= $limit)
            return $str;

        if ($limit <= 0)
            return $end_char;

        if ($preserve_words == FALSE)
        {
            return rtrim(mb_substr($str, 0, $limit)).$end_char;
        }

        preg_match('/^.{'.($limit - 1).'}\S*/us', $str, $matches);

        return rtrim($matches[0]).(strlen($matches[0]) == strlen($str) ? '' : $end_char);
    }

    /**
    * Limits HTML to a certain number of words.
    * Is aware of tags etc and will not count them in the word-count, as well as closing them properly.
    *
    * This doesn't actually pass all unit tests at the moment - an exact match in num words will still put in ... part.
    **/
    public static function limitWordsHtml($text, $limit = 50)
    {
        $count = 0;
        $offset = 0;
        $over = 0;
        $out = '';
        $stack = array();

        // These shouldn't have an end tag
        $single_tags = '/^(?:br|wbr|area|hr|img|input)$/i';

        // Nuke HTML comments and duplicate space
        $text = preg_replace('/<!--.*?-->/s', '', $text);
        $text = preg_replace('/\s\s+/', ' ', $text);

        //                     opening tag       closing tag    words            non-words
        while (preg_match('!\G(<[a-z0-9]+[^>]*>)|(</[a-z0-9]+>)|([-_a-zA-Z0-9]+)|([^-_a-zA-Z0-9<>]+)!si', $text, $m, 0, $offset)) {
            if ($m[1]) {
                if ($over) { $out .= '...'; break; }
                preg_match('!^<([a-z0-9]+)[^>]*>$!i', $m[0], $matches);
                if (! preg_match($single_tags, $matches[1])) {
                    array_push($stack, '</' . strtolower($matches[1]) . '>');
                }
                $out .= $m[0];

            } elseif ($m[2]) {
                $m[0] = strtolower($m[0]);
                $pop = array_pop($stack);
                while ($pop != $m[0]) { $out .= $pop; $pop = array_pop($stack); }
                $out .= $pop;

            } elseif ($m[3]) {
                if ($over) { $out .= '...'; break; }
                $out .= $m[0];
                $count++;
                if ($count == $limit) {
                    $over++;
                }

            } else {
                if ($over) { $out .= '...'; break; }
                $out .= $m[0];
            }

            $offset += strlen($m[0]);
        }

        while ($pop = array_pop($stack)) { $out .= $pop; }

        return $out;
    }


    /**
     * Determines whether given HTML contains a FORM tag, which can cause nested-forms issues
     *
     * Not tested with malformed input - should not be used as an XSS filter
     *
     * @param string $html HTML to check
     * @return bool True if the string contains a FORM tag, false if it doesn't
     */
    public static function containsFormTag($html)
    {
        // Quick test before even doing string manipulation
        if (stripos($html, '<form') === false) {
            return false;
        }

        // These tags always contain CDATA so nuke them entirely
        $html = preg_replace('!<script[^>]*>.+?</script>!is', '', $html);
        $html = preg_replace('!<style[^>]*>.+?</style>!is', '', $html);

        return (stripos($html, '<form') !== false);
    }


    /**
     * Alternates between two or more strings.
     *
     * @param   string  $args strings to alternate between
     * @return  string
     */
    public static function alternate(...$args)
    {
        static $i;

        if (empty($args))
        {
            $i = 0;
            return '';
        }

        return $args[($i++ % count($args))];
    }

    /**
     * Reduces multiple slashes in a string to single slashes.
     *
     * @param   string  $str string to reduce slashes of
     * @return  string
     */
    public static function reduceSlashes($str)
    {
        return preg_replace('#(?<!:)//+#', '/', (string) $str);
    }

    /**
     * Replaces the given words with a string.
     *
     * @param string $str Phrase to replace words in
     * @param array $badwords Words to replace
     * @param string $replacement Replacement string
     * @param bool $replace_partial_words Replace words across word
     *        boundaries (space, period, etc). This probably doesn't do what
     *        you think it does; check the test suite.
     * @return string
     */
    public static function censor($str, array $badwords, $replacement = '#', $replace_partial_words = FALSE)
    {
        foreach ($badwords as $key => $badword) {
            $badwords[$key] = str_replace('\*', '\S*?', preg_quote((string) $badword));
        }

        $regex = '('.implode('|', $badwords).')';

        if ($replace_partial_words == TRUE)
        {
            // Just using \b isn't sufficient when we need to replace a badword that already contains word boundaries itself
            $regex = '(?<=\b|\s|^)'.$regex.'(?=\b|\s|$)';
        }

        $regex = '!'.$regex.'!ui';

        if (mb_strlen($replacement) == 1) {
            $replace = function($matches) use ($replacement) {
                return str_repeat($replacement, mb_strlen($matches[1]));
            };
            return preg_replace_callback($regex, $replace, $str);
        }

        return preg_replace($regex, $replacement, $str);
    }

    /**
     * Finds the text that is similar between a set of words.
     *
     * @param   array   $words to find similar text of
     * @return  string
     */
    public static function similar(array $words)
    {
        // First word is the word to match against
        $word = current($words);

        for ($i = 0, $max = strlen($word); $i < $max; ++$i)
        {
            foreach ($words as $w)
            {
                // Once a difference is found, break out of the loops
                if ( ! isset($w[$i]) OR $w[$i] !== $word[$i])
                    break 2;
            }
        }

        // Return the similar text
        return substr($word, 0, $i);
    }

    /**
     * Converts text email addresses and anchors into links.
     *
     * @param   string   $text to auto link
     * @return  string
     */
    public static function autoLink($text)
    {
        // Auto link emails first to prevent problems with "www.domain.com@example.com"
        return Text::autoLinkUrls(Text::autoLinkEmails($text));
    }

    /**
     * Converts text anchors into links.
     *
     * @param   string   $text to auto link
     * @return  string
     */
    public static function autoLinkUrls($text)
    {
        // Finds all http/https/ftp/ftps links that are not part of an existing html anchor
        if (preg_match_all('~\b(?<!href="|">)(?:ht|f)tps?://\S+(?:/|\b)~i', $text, $matches))
        {
            foreach ($matches[0] as $match)
            {
                // Replace each link with an anchor
                $text = str_replace($match, Html::anchor($match), $text);
            }
        }

        // Find all naked www.links.com (without http://)
        if (preg_match_all('~\b(?<!://)www(?:\.[a-z0-9][-a-z0-9]*+)+\.[a-z]{2,6}\b~i', $text, $matches))
        {
            foreach ($matches[0] as $match)
            {
                // Replace each link with an anchor
                $text = str_replace($match, Html::anchor('http://'.$match, $match), $text);
            }
        }

        return $text;
    }

    /**
     * Converts text email addresses into links.
     *
     * @param   string   $text to auto link
     * @return  string
     */
    public static function autoLinkEmails($text)
    {
        // Finds all email addresses that are not part of an existing html mailto anchor
        // Note: The "58;" negative lookbehind prevents matching of existing encoded html mailto anchors
        //       The html entity for a colon (:) is &#58; or &#058; or &#0058; etc.
        if (preg_match_all('~\b(?<!href="mailto:|">|58;)(?!\.)[-+_a-z0-9.]++(?<!\.)@(?![-.])[-a-z0-9.]+(?<!\.)\.[a-z]{2,6}\b~i', $text, $matches))
        {
            foreach ($matches[0] as $match)
            {
                // Replace each email with an encoded mailto
                $text = str_replace($match, Html::mailto($match), $text);
            }
        }

        return $text;
    }

    /**
     * Automatically applies <p> and <br /> markup to text. Basically nl2br() on steroids.
     *
     * @param   string   $str subject
     * @return  string
     */
    public static function autoP($str)
    {
        // Trim whitespace
        if (($str = trim($str)) === '')
            return '';

        // Standardize newlines
        $str = str_replace(array("\r\n", "\r"), "\n", $str);

        // Trim whitespace on each line
        $str = preg_replace('~^[ \t]+~m', '', $str);
        $str = preg_replace('~[ \t]+$~m', '', $str);

        // The following regexes only need to be executed if the string contains html
        if ($html_found = (strpos($str, '<') !== FALSE))
        {
            // Elements that should not be surrounded by p tags
            $no_p = '(?:p|div|h[1-6r]|ul|ol|li|blockquote|d[dlt]|pre|t[dhr]|t(?:able|body|foot|head)|c(?:aption|olgroup)|form|s(?:elect|tyle)|a(?:ddress|rea)|ma(?:p|th))';

            // Put at least two linebreaks before and after $no_p elements
            $str = preg_replace('~^<'.$no_p.'[^>]*+>~im', "\n$0", $str);
            $str = preg_replace('~</'.$no_p.'\s*+>$~im', "$0\n", $str);
        }

        // Do the <p> magic!
        $str = '<p>'.trim($str).'</p>';
        $str = preg_replace('~\n{2,}~', "</p>\n\n<p>", $str);

        // The following regexes only need to be executed if the string contains html
        if ($html_found !== FALSE)
        {
            // Remove p tags around $no_p elements
            $str = preg_replace('~<p>(?=</?'.$no_p.'[^>]*+>)~i', '', $str);
            $str = preg_replace('~(</?'.$no_p.'[^>]*+>)</p>~i', '$1', $str);
        }

        // Convert single linebreaks to <br />
        $str = preg_replace('~(?<!\n)\n(?!\n)~', "<br />\n", $str);

        return $str;
    }

    /**
     * Returns human readable sizes.
     * @see  Based on original functions written by:
     * @see  Aidan Lister: http://aidanlister.com/repos/v/function.size_readable.php
     * @see  Quentin Zervaas: http://www.phpriot.com/d/code/strings/filesize-format/
     *
     * @param   int      $bytes size in bytes
     * @param   string   $force_unit a definitive unit
     * @param   string   $format the return string format
     * @param   bool     $si whether to use SI prefixes or IEC
     * @return  string
     */
    public static function bytes($bytes, $force_unit = '', $format = NULL, $si = TRUE)
    {
        // Format string
        $format = ($format === NULL) ? '%01.2f %s' : (string) $format;

        // IEC prefixes (binary)
        if ($si == FALSE OR strpos($force_unit, 'i') !== FALSE)
        {
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
            $mod   = 1024;
        }
        // SI prefixes (decimal)
        else
        {
            $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
            $mod   = 1000;
        }

        // Determine unit to use
        if (($power = array_search((string) $force_unit, $units)) === FALSE)
        {
            $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
        }

        return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
    }

    /**
     * Prevents widow words by inserting a non-breaking space between the last two words.
     * @see  http://www.shauninman.com/archive/2006/08/22/widont_wordpress_plugin
     *
     * @param   string  $str string to remove widows from
     * @return  string
     */
    public static function widont($str)
    {
        $str = rtrim($str);
        $space = strrpos($str, ' ');

        if ($space !== FALSE)
        {
            $str = substr($str, 0, $space).'&nbsp;'.substr($str, $space + 1);
        }

        return $str;
    }


    /**
    * Returns a number with an english suffix appended (e.g. 1st, 5th, 12th, 123rd)
    * @param int $number
    * @return string
    **/
    public static function ordinalize($number)
    {
        if ($number % 100 == 11 or $number % 100 == 12 or $number % 100 == 13) {
            return $number . 'th';
        }

        switch ($number % 10) {
            case 1:
                return $number . 'st';
            case 2:
                return $number . 'nd';
            case 3:
                return $number . 'rd';
            default:
                return $number . 'th';
        }
    }


    /**
    * Make a chunk of valid HTML into plain text, and (optionally) limit the number of words.
    *
    * @param string $html The original HTML
    * @param int $max_words The maximum number of words. Use 0 for no limit.
    * @return string Plain text
    **/
    public static function plain($html, $max_words = 50)
    {
        $html = Enc::cleanfunky($html);

        // Normalise newlines into spaces
        $html = str_replace(["\r", "\n"], ' ', $html);

        // Replace some HTML tags with newlines
        $html = preg_replace('!<(p|div|h[1-6]|pre|ol|ul)[^>]*?>!i', "\n\n", $html);
        $html = preg_replace('!<(br|li)[^>]*?>!i', "\n", $html);

        // Remove inline style and script tags
        $html = preg_replace('!<style[^>]*>.+?<\/style>!i', '', $html);
        $html = preg_replace('!<script[^>]*>.+?<\/script>!i', '', $html);

        // Remove all other tags, and decode entities
        $html = strip_tags($html);
        $html = html_entity_decode($html, ENT_COMPAT, 'UTF-8');

        // Combine runs of multiple whitespace
        $html = preg_replace("![ \t][ \t]+!", ' ', $html);

        // Trim whitespace on each line
        $lines = explode("\n", $html);
        $lines = array_map('trim', $lines);

        $html = implode("\n", $lines);

        if ($max_words) {
            $html = Text::limitWords($html, $max_words, '...');
        }

        // Tidy up nbsp characters that break iconv.
        $html = str_replace("\u{00a0}", ' ', $html);

        return trim($html);
    }


    /**
    * Make a chunk of plain text into HTML rich text
    * The text will be wrapped within a block element (default is a P tag)
    *
    * @param string $text The original plain text
    * @param string|null $block_elem The block element to use. Default is a P tag (i.e. 'p').
    *        Use null or empty string to get the result without it being wrapped in a tag.
    * @return string A HTML representation of the plain text
    **/
    public static function richtext(string $text, $block_elem = 'p'): string
    {
        $block_elem = strtolower(trim($block_elem ?? ''));

        $text = Enc::cleanfunky($text);
        $text = Enc::html($text);
        $text = str_replace(array("\r\n", "\r", "\n"), '<br>', $text);

        if (!$block_elem) return $text;

        return "<{$block_elem}>{$text}</{$block_elem}>";
    }


    /**
    * Convert a lower_case names into CamelCaps names
    *
    * @param string $name
    * @return string
    **/
    public static function lc2camelcaps($name)
    {
        $name = preg_replace_callback(
            '/([a-z0-9])_([a-z0-9])/i',
            function($matches) {
                return $matches[1] . strtoupper($matches[2]);
            },
            $name
        );
        $name = ucfirst($name);
        return $name;
    }


    /**
    * Convert a lower_case names into camelCase names
    *
    * @param string $name
    * @return string
    **/
    public static function lc2camelcase($name)
    {
        $name = preg_replace_callback(
            '/([a-z0-9])_([a-z0-9])/i',
            function($matches) {
                return $matches[1] . strtoupper($matches[2]);
            },
            $name
        );
        $name = lcfirst($name);
        return $name;
    }


    /**
    * Convert a CamelCaps or camelCase name into a lower_case names
    *
    * @param string $name
    * @return string
    **/
    public static function camel2lc($name)
    {
        $name = preg_replace_callback(
            '/[A-Z0-9]/',
            function($matches) {
                return '_' . strtolower($matches[0]);
            },
            $name
        );
        $name = ltrim($name, '_');
        return $name;
    }


    /**
     * Encode HTML so it's suitable for direct output, but allow some HTML tags to be left as-is
     *
     * Only a limited subset of tags are left alone, all other tags are stripped.
     * Allowed tags: A, B, I, STRONG, EM, BR, IMG, SPAN, ABBR, SUP, SUB
     *
     * The algorithm used in this method is quite simple, so this method should not be used
     * as a defence against XSS attacks; it should only be used on trusted input such as Form helptext.
     *
     * @param string $html Plain text or HTML which may contain various tags
     * @return string HTML which only contains safe tags
     */
    public static function limitedSubsetHtml($html)
    {
        static $allowed = ['a', 'b', 'i', 'strong', 'em', 'br', 'img', 'span', 'abbr', 'sup', 'sub'];

        $offset = 0;
        $out = '';

        //                     opening tag       closing tag    content
        while (preg_match('!\G(<[a-z0-9]+[^>]*>)|(</[a-z0-9]+>)|([^<>]+|<|>)!si', (string) $html, $m, 0, $offset)) {
            if ($m[1]) {
                preg_match('!^<([a-z0-9]+)[^>]*>$!i', $m[0], $matches);
                if (in_array($matches[1], $allowed)) {
                    $out .= $m[0];
                }

            } else if ($m[2]) {
                if (in_array(substr($m[0], 2, -1), $allowed)) {
                    $out .= $m[0];
                }

            } else {
                $out .= Enc::html($m[0]);
            }

            $offset += strlen($m[0]);
        }

        return $out;
    }


    /**
     * Returns current year or original year and current year of copyright
     * @param string $year The original year of copyright
     * @return string Current year, or Original year - Current year
     */
    public static function copyright($year)
    {
        if (empty($year)) {
            return date('Y');
        }

        $year = date('Y', strtotime($year . '-01-01'));

        if ($year == date('Y')) {
            return $year;
        }

        return $year . ' - ' . date('Y');
    }
}

