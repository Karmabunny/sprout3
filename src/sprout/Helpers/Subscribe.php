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
* Base class for the content subscription handlers
**/
abstract class Subscribe {

    /**
    * Return a list of records for the provided settings
    *
    * @param array $handler_settings The settings provided when the user subscribed
    * @param int $since The start timestamp for returned events
    * @return array An array-of-arrays result set
    *
    * Each item in the list should be an array, with the following keys:
    *    name     The name of the item
    *    text     A short, plain-text description of the item
    *    url      The URL to provide to the user. Can be absolute (http://...) or relative (/...)
    *    ts       The event timestamp (unix format)
    **/
    abstract public function getList(array $handler_settings, $since);


    /**
    * Return a (string) name for this subscription.
    * You can use the settings to tweak the title (e.g. the category of articles).
    *
    * @param array $handler_settings The settings provided when the user subscribed
    * @return string An title shown to end users
    **/
    abstract public function getName(array $handler_settings);

}

