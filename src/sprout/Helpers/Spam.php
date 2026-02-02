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
 * Helper for a simple mechanism to dissuade and annoy entry-level spammers.
 *
 * For rigorous protection, use a CAPTCHA. @see Captcha
 */
class Spam
{

    /**
     * Generates a div containing an input which should never be filled in, in order to trap spam bots.
     * To be used with {@see Spam::check}
     * @return string HTML
     */
    public static function glue()
    {
        $code = 'f_' . Security::randStr(12);
        $out = '<div style="display:none">';
        $out .= '  <p>Leave the following field blank, it is an anti-spam field.</p>';
        $out .= '  <p><b><label for="' . $code . '">Email address:</label></b><br><input name="_email" id="' . $code . '" value=""></p>';
        $out .= '</div>';
        return $out;
    }

    /**
     * Checks that the spam field was not submitted with the form
     *
     * @return bool true on success, false otherwise
     */
    public static function check()
    {
        if (!empty($_POST['_email'])) return false;
        return true;
    }

    /**
     * Checks that the spam field was not submitted with the form
     *
     * If the check fails the user will be redirected to the URL base ('/') along
     * with a notification. Normal users will never encounter this scenario.
     * @return void If the check succeeded.
     * @noreturn If the check failed.
     */
    public static function checkOrDie()
    {
        if (self::check()) return;

        Notification::error('Are you a spam bot?');
        Url::redirect('result/error');
    }


    /**
     * Checks text for bad words, like 'tax' and 'capsicum'
     *
     * @see config sprout.censor_level to set the desired censor level
     * @note Disallowed sequences are collated from /media/text/bannedwords_level{level}.txt which
     *       must take the form of a list of new-line separated character sequences.
     * @param string $text The text to censor
     * @return string The censored text, with disallowed sequences replaced with '*'
     */
    public static function censor($text)
    {
        static $words = null;

        if ($words == null) {
            $words = array();

            $level = Kohana::config('sprout.censor_level');
            if ($level === null or $level > 3) $level = 3;

            if ($level > 0) {
                for ($i = 1; $i <= $level; $i++) {
                    $file = file_get_contents(COREPATH . "media/text/bannedwords_level{$i}.txt");
                    $lines = explode("\n", $file);
                    foreach ($lines as $l) {
                        $l = trim($l);
                        if ($l != '') $words[] = $l;
                    }
                    unset ($file, $lines);
                }
            }
        }

        return Text::censor($text, $words, '*');
    }


    /**
     * Looks at a block of text to see if it looks like spam.
     *
     * @note Do not rely on this to provide rigorous protection against spam.
     * @param string $text The text to check for spam
     * @return array An array of errors. An empty array signals no unusual sequences were detected
     */
    public static function detect($text)
    {
        $errors = [];

        // HTML tags
        if (strip_tags($text) != $text) {
            $errors['html'] = 'Cannot contain HTML';
        }

        // URLs
        if (preg_match('!(https?|ftp)://[-_.a-zA-Z0-9]+!', $text)) {
            $errors['url'] = 'Cannot contain a url';
        } else if (preg_match('!\b[-_a-zA-Z0-9]+[.][-_.a-zA-Z0-9]+\b!', $text)) {
            $errors['url'] = 'Cannot contain a url';
        }

        // Email addresses
        if (preg_match('![-_.a-zA-Z0-9]+[@][-_.a-zA-Z0-9]+!', $text)) {
            $errors['email'] = 'Cannot contain an email address';
        }

        return $errors;
    }

}
