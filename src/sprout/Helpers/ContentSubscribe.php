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

class ContentSubscribe
{

    /**
    * Subscribe a user to a specified handler.
    *
    * @param string $handler_class The name of the handler class
    * @param array $handler_settings The settings to be passed to the handler; can be an empty array
    * @param string $name The name of the user
    * @param string $email The email address of the user
    **/
    public static function subscribe($handler_class, array $handler_settings, $name, $email)
    {
        $code = md5(strtolower(trim($email)));

        $update_data = array();
        $update_data['handler_class'] = $handler_class;
        $update_data['handler_settings'] = json_encode($handler_settings);
        $update_data['name'] = $name;
        $update_data['email'] = $email;
        $update_data['code'] = $code;
        $update_data['subsite_id'] = SubsiteSelector::$subsite_id;
        $update_data['date_added'] = Pdb::now();
        $update_data['date_modified'] = Pdb::now();

        Pdb::insert('content_subscriptions', $update_data);
    }


    /**
    * Called by a usort() in the cronjob to sort by the timestamp field
    **/
    public static function tsSort($a, $b)
    {
        if ($a['ts'] == $b['ts']) return 0;
        return ($a['ts'] < $b['ts']) ? -1 : 1;
    }

}

