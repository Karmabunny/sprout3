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
 * Rate-limiting system, to prefent people form doing hacky stuff
 */
class RateLimit
{

    /**
     * Log a successful hit against the rate-limit system (e.g. user was logged in)
     *
     * @param string $event The event being done by the user, e.g. 'user-auth-action' or 'form-submit-action'
     * @param string $user The user who performed the event, may be an empty string for unauthenticated events
     */
    public static function logHitSuccess($event, $username = '')
    {
        self::logHit($event, true, $username);
    }


    /**
     * Log a failure hit against the rate-limit system (e.g. password incorrect)
     *
     * @param string $event The event being done by the user, e.g. 'user-auth-action' or 'form-submit-action'
     * @param string $user The user who performed the event, may be an empty string for unauthenticated events
     */
    public static function logHitFailure($event, $username = '')
    {
        self::logHit($event, false, $username);
    }


    /**
     * Log a hit against the rate-limit system
     *
     * @param string $event The event being done by the user, e.g. 'user-auth-action' or 'form-submit-action'
     * @param bool $success The status of the event, true for success, false for failure
     * @param string $user The user who performed the event
     */
    protected static function logHit($event, $success, $username)
    {
        $data = array();
        $data['date_added'] = Pdb::now();
        $data['event'] = trim($event);
        $data['success'] = ($success ? '1' : '0');
        $data['username'] = trim($username);
        $data['ip_address'] = Request::userIp();
        Pdb::insert('rate_limit_hits', $data);
    }


    /**
     * Return the number of hits matching given conditions for a given time period
     *
     * @param array $conditions Conditions to match, e.g. 'event', 'success', 'username', 'ip_address' fields
     * @param int $time Time to check in seconds, e.g. 3600 for one hour
     * @return int Number of hits
     */
    public static function getHitCount(array $conditions, $time)
    {
        $time = (int) $time;

        $params = [$time];
        $where = Pdb::buildClause($conditions, $params);

        $q = "SELECT COUNT(id) AS C
            FROM ~rate_limit_hits
            WHERE date_added > DATE_SUB(NOW(), INTERVAL ? SECOND)
            AND {$where}";
        return Pdb::q($q, $params, 'val');
    }


    /**
     * Check that a given event has only been hit by the request ip address an allowable number of times
     *
     * @param string $event Event to check
     * @param bool|null $success TRUE to check only successes, FALSE only failures, NULL all events
     * @param int $limit Maximum number of events
     * @param int $time Time to search, in seconds
     * @return bool TRUE if under the limit, FALSE if over the limit
     */
    public static function checkLimitIP($event, $success, $limit, $time)
    {
        $ip_address = Request::userIp();
        $conditions = ['event' => $event, 'ip_address' => $ip_address];
        if ($success !== null) $conditions['success'] = ($success ? '1' : '0');
        $count = self::getHitCount($conditions, $time);
        return ($count <= $limit);
    }

}
