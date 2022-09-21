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


/**
* Provides user event notification functions
**/
class Notification
{
    const TYPE_CONFIRM = 1;
    const TYPE_ERROR = 2;
    const TYPE_POPUP = 3;


    // CSS classes for the different types
    static $classes = array(
        self::TYPE_CONFIRM => 'confirm',
        self::TYPE_ERROR => 'error'
    );


    /**
    * Renders notification messages for display to the user
    *
    * @param string $scope If set the the messages are pulled from a different session key
    * @return string HTML
    **/
    public static function checkMessages($scope = 'default')
    {
        Session::Instance();
        $out = '';

        if (empty($_SESSION['notify'][$scope])) {
            return;
        }

        $has_type = array();
        foreach ($_SESSION['notify'][$scope] as $idx => $message) {
            if ($message[0] == self::TYPE_POPUP) continue;
            $has_type[$message[0]] = $message[0];
        }

        // If all messages are of a certain type, add a class for that type
        $class = 'messages';
        if (count($has_type) == 1) {
            reset($has_type);
            $class .= ' all-type-' . self::$classes[key($has_type)];
        } else {
            $class .= ' mixed-type';
        }

        $out .= "<ul class=\"{$class}\">\n";

        foreach ($_SESSION['notify'][$scope] as $idx => $message) {
            // Ignore popups at this point and render out regular notifications
            if ($message[0] == self::TYPE_POPUP) continue;

            $out .= "<li class=\"" . self::$classes[$message[0]] . "\">";
            $out .= "<span class=\"notification--text\">{$message[1]}</span>";

            // render action buttons
            foreach ($message[2] as $action => $anchor) {
                $anchor = Enc::html($anchor);
                $out .= "<span class=\"notification--action\"><a href=\"{$action}\">{$anchor}</a></span>";
            }
            $out .= "</li>";
            unset ($_SESSION['notify'][$scope][$idx]);
        }

        $out .= "</ul>\n";

        if (empty($_SESSION['notify'][$scope])) {
            unset($_SESSION['notify'][$scope]);
            return $out;
        }

        // If we still have notifications they must be pop-ups - render them
        foreach ($_SESSION['notify'][$scope] as $idx => $message) {
            $popup = '<div id="notification-box">';
            $popup .= '<div id="notification-box-content">';
            $popup .= '<h2 class="notification-box-msg">' . Enc::html($message[1]) . '</h2>';
            $popup .= '<div class="info">Additional actions are available:</div>';
            $popup .= '<div id="notification-box-links">';
            foreach ($message[2] as $action => $anchor) {
                $anchor = Enc::html($anchor);
                $popup .= "<span class=\"action-link\"><a href=\"{$action}\">{$anchor}</a></span>";
            }

            $popup .= '</div>';
            $popup .= '</div>';
            $popup .= '</div>';

            $out .=  '<script type="text/javascript">';
            $out .=  '$(document).ready(function() {';
            $out .=  '  $.facebox("' . Enc::js($popup) . '");';
            $out .=  '})';
            $out .=  '</script>';
        }

        unset($_SESSION['notify'][$scope]);

        return $out;
    }



    /**
     * Return and then clear notifications in the session for a given scope
     *
     * @param string $scope
     * @return array
     */
    public function retrieveMessages(string $scope = 'default'): array
    {
        Session::Instance();

        $notifications = $_SESSION['notify'][$scope] ?? [];
        unset($_SESSION['notify'][$scope]);

        return $notifications;
    }


    /**
     * Checks to see if a notification of a particular type has been set
     * @param int $type One of the Notification::TYPE_* constants
     * @param string $scope System to allow for multiple notification areas
     * @return bool True if a notification has been set
     */
    public static function has($type, $scope = 'default')
    {
        Session::instance();
        if (empty($_SESSION['notify'][$scope])) return false;
        foreach ($_SESSION['notify'][$scope] as $record) {
            list($rec_type, $message, $actions) = $record;
            if ($type == $rec_type) return true;
        }
        return false;
    }


    /**
    * Adds a confirmation message to the list of messages that are shown to the user
    *
    * @param string $message The message to show
    * @param string $format Either 'plain' for plain-text or 'html' for HTML which is limited to a safe subset
    * @param string $scope System to allow for multiple notification areas
    **/
    public static function confirm($message, $format = 'plain', $scope = 'default')
    {
        Session::Instance();
        if ($format === 'html') {
            $_SESSION['notify'][$scope][] = array(self::TYPE_CONFIRM, Text::limitedSubsetHtml($message), []);
        } else {
            $_SESSION['notify'][$scope][] = array(self::TYPE_CONFIRM, Enc::html($message), []);
        }
    }


    /**
    * Adds an error message to the list of messages that are shown to the user
    *
    * @param string $message The message to show
    * @param string $message_format Either 'plain' for plain-text or 'html' for HTML which is limited to a safe subset
    * @param string $scope System to allow for multiple notification areas
    **/
    public static function error($message, $format = 'plain', $scope = 'default')
    {
        Session::Instance();
        if ($format === 'html') {
            $_SESSION['notify'][$scope][] = array(self::TYPE_ERROR, Text::limitedSubsetHtml($message), []);
        } else {
            $_SESSION['notify'][$scope][] = array(self::TYPE_ERROR, Enc::html($message), []);
        }
    }


    /**
    * Adds a popup message to the list of messages that are shown to the user
    *
    * @param string $message The message to show
    * @param array $actions Additional buttons to show with the message. In the format [ url => label ]
    * @param string $scope System to allow for multiple notification areas
    **/
    public static function popup($message, array $actions = [], $scope = 'default') {
        Session::Instance();
        $_SESSION['notify'][$scope][] = array(self::TYPE_POPUP, Enc::html($message), $actions);
    }

}


