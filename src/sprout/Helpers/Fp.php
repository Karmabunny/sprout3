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

use PDO;
use PDOException;


/* ***** BEGIN LICENSE BLOCK *****
 *
 * This file is part of FirePHP (http://www.firephp.org/).
 *
 * Software License Agreement (New BSD License)
 *
 * Copyright (c) 2006-2010, Christoph Dorn
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright notice,
 *       this list of conditions and the following disclaimer.
 *
 *     * Redistributions in binary form must reproduce the above copyright notice,
 *       this list of conditions and the following disclaimer in the documentation
 *       and/or other materials provided with the distribution.
 *
 *     * Neither the name of Christoph Dorn nor the names of its
 *       contributors may be used to endorse or promote products derived from this
 *       software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
 * ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * ***** END LICENSE BLOCK *****
 *
 * @copyright   Copyright (C) 2007-2009 Christoph Dorn
 * @author      Christoph Dorn <christoph@christophdorn.com>
 * @license     http://www.opensource.org/licenses/bsd-license.php
 * @package     FirePHPCore
 */


/**
 * Static methods which implement FirePHP logging and Sprout's own hit log
 * See http://www.firephp.org/
 */
class Fp
{
    protected static $use_hit_log = false;


    /**
     * Enable and disable logging to Firebug
     *
     * @see FirePHP->setEnabled()
     * @param boolean $Enabled TRUE to enable, FALSE to disable
     * @return void
     */
    public static function setEnabled($Enabled)
    {
        $instance = FirePHP::getInstance(true);
        $instance->setEnabled($Enabled);
    }

    /**
     * Check if logging is enabled
     *
     * @see FirePHP->getEnabled()
     * @return boolean TRUE if enabled
     */
    public static function getEnabled()
    {
        $instance = FirePHP::getInstance(true);
        return $instance->getEnabled();
    }


    /**
     * Enable/disable logging to the hit log (in the database)
     * @param bool $enabled true to enable
     * @return void
     */
    public static function setHitLogEnabled($enabled)
    {
        self::$use_hit_log = (bool) $enabled;
    }

    /**
     * Check if hit log is enabled (i.e. in the database)
     * @param bool $enabled true to enable
     * @return void
     */
    public static function getHitLogEnabled()
    {
        return self::$use_hit_log;
    }


    /**
     * Specify a filter to be used when encoding an object
     *
     * Filters are used to exclude object members.
     *
     * @see FirePHP->setObjectFilter()
     * @param string $Class The class name of the object
     * @param array $Filter An array or members to exclude
     * @return void
     */
    public static function setObjectFilter($Class, $Filter)
    {
      $instance = FirePHP::getInstance(true);
      $instance->setObjectFilter($Class, $Filter);
    }

    /**
     * Set some options for the library
     *
     * @see FirePHP->setOptions()
     * @param array $Options The options to be set
     * @return void
     */
    public static function setOptions($Options)
    {
        $instance = FirePHP::getInstance(true);
        $instance->setOptions($Options);
    }

    /**
     * Get options for the library
     *
     * @see FirePHP->getOptions()
     * @return array The options
     */
    public static function getOptions()
    {
        $instance = FirePHP::getInstance(true);
        return $instance->getOptions();
    }

    /**
     * Log object to firebug
     *
     * @see http://www.firephp.org/Wiki/Reference/Fb
     * @param mixed $Object
     * @return true
     * @throws Exception
     */
    public static function send()
    {
        $instance = FirePHP::getInstance(true);
        $args = func_get_args();
        return call_user_func_array(array($instance,'fb'),$args);
    }

    /**
     * Start a group for following messages
     *
     * Options:
     *   Collapsed: [true|false]
     *   Color:     [#RRGGBB|ColorName]
     *
     * @param string $Name
     * @param array $Options OPTIONAL Instructions on how to log the group
     * @return true
     */
    public static function group($Name, $Options=null)
    {
        $instance = FirePHP::getInstance(true);
        return $instance->group($Name, $Options);
    }

    /**
     * Ends a group you have started before
     *
     * @return true
     * @throws Exception
     */
    public static function groupEnd()
    {
        return self::send(null, null, FirePHP::GROUP_END);
    }


    /**
     * Logs data against a page hit.
     * Used to store Fp::log and Fp::trace data in the DB, in case e.g. dev browser isn't Firefox
     * @param mixed $obj Data to log
     * @param string $label Label to refer to the data
     * @param bool $trace Whether to log a stack trace
     * @return void
     */
    public static function logHitData($obj, $label, $trace)
    {
        static $hit_id = null;
        static $start;

        if (IN_PRODUCTION) return;
        if (defined('PHPUNIT')) return;
        if ($hit_id === false) return;

        // Get PDO connection directly to prevent logging itself
        try {
            $pdo = Pdb::getConnection();
            $pf = Pdb::prefix();
        } catch (PDOException $ex) {
            $hit_id = false;
            return;
        }

        // Disable PDO error exceptions, in case the ~hit_log* tables don't exist
        $err_mode = $pdo->getAttribute(PDO::ATTR_ERRMODE);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);

        // Initialise hit_log
        if (!$hit_id) {
            $ip = (string) @$_SERVER['REMOTE_ADDR'];
            if (!$ip) $ip = '127.0.0.1';

            if (php_sapi_name() == 'cli') {
                $agent = 'CLI';
            } else {
                $agent = (string) @$_SERVER['HTTP_USER_AGENT'];
            }

            $uri = Router::$complete_uri;
            if (!$uri) {
                if (php_sapi_name() == 'cli') {
                    $uri = $_SERVER['argv'][1];
                } else {
                    $uri = $_SERVER['REQUEST_URI'];
                }
            }

            if (preg_match('!^/admin/heartbeat!', $uri) or preg_match('!^/dbtools/!', $uri)) {
                $hit_id = false;

                // Restore error reporting
                $pdo->setAttribute(PDO::ATTR_ERRMODE, $err_mode);
                return;
            }

            // Clear out entries older than two hours
            // Try to prevent auto-increment from counting up forever by using TRUNCATE if no recent data
            $min_age = 60 * 60 * 2;

            $q = "SELECT COUNT(*) FROM {$pf}hit_log WHERE date_added > ?";
            $statement = $pdo->prepare($q);
            $statement->execute([date('Y-m-d H:i:s', time() - $min_age)]);
            $num_entries = $statement->fetchColumn();

            if ($num_entries == 0) {
                $pdo->query("TRUNCATE {$pf}hit_log");
                $pdo->query("TRUNCATE {$pf}hit_log_data");
            } else {
                $q = "DELETE FROM {$pf}hit_log WHERE date_added < ?";
                $statement = $pdo->prepare($q);
                $statement->execute([date('Y-m-d H:i:s', time() - $min_age)]);

                $q = "DELETE FROM {$pf}hit_log_data WHERE date_added < ?";
                $statement = $pdo->prepare($q);
                $statement->execute([date('Y-m-d H:i:s', time() - $min_age)]);
            }

            $q = "INSERT INTO {$pf}hit_log (date_added, uri, ip, session_id, agent)
                VALUES (?, ?, ?, ?, ?)";
            $statement = $pdo->prepare($q);
            $statement->execute([Pdb::now(), $uri, $ip, session_id(), $agent]);
            $hit_id = $pdo->lastInsertId();
            $start = $_SERVER['REQUEST_TIME_FLOAT'];
        }

        // Log watchpoint data
        $data = [
            'date_added' => Pdb::now(),
            'delay' => (microtime(true) - $start) / 1000.0,
            'log_id' => $hit_id,
            'label' => (string) $label,
            'data' => json_encode($obj),
        ];
        if ($trace) {
            $backtrace = debug_backtrace();
            array_shift($backtrace);
            $data['trace'] = json_encode($backtrace);
        }

        $fields = implode(', ', array_keys($data));
        $placeholders = trim(str_repeat('?, ', count($data)), ', ');
        $q = "INSERT INTO {$pf}hit_log_data ({$fields})
            VALUES ({$placeholders})";
        $statement = $pdo->prepare($q);
        $statement->execute(array_values($data));

        // Restore error reporting
        $pdo->setAttribute(PDO::ATTR_ERRMODE, $err_mode);
    }


    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::LOG
     * @param mixes $Object
     * @param string $Label
     * @return true
     * @throws Exception
     */
    public static function log($Object, $Label=null)
    {
        if (self::getHitLogEnabled()) {
            self::logHitData($Object, $Label, false);
        }

        $instance = FirePHP::getInstance(true);
        if ($instance->getEnabled() == false) {
            return;
        }
        return self::send($Object, $Label, FirePHP::LOG);
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::INFO
     * @param mixes $Object
     * @param string $Label
     * @return true
     * @throws Exception
     */
    public static function info($Object, $Label=null)
    {
        return self::send($Object, $Label, FirePHP::INFO);
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::WARN
     * @param mixes $Object
     * @param string $Label
     * @return true
     * @throws Exception
     */
    public static function warn($Object, $Label=null)
    {
        return self::send($Object, $Label, FirePHP::WARN);
    }

    /**
     * Log object with label to firebug console
     *
     * @see FirePHP::ERROR
     * @param mixes $Object
     * @param string $Label
     * @return true
     * @throws Exception
     */
    public static function error($Object, $Label=null)
    {
        return self::send($Object, $Label, FirePHP::ERROR);
    }

    /**
     * Dumps key and variable to firebug server panel
     *
     * @see FirePHP::DUMP
     * @param string $Key
     * @param mixed $Variable
     * @return true
     * @throws Exception
     */
    public static function dump($Key, $Variable)
    {
        return self::send($Variable, $Key, FirePHP::DUMP);
    }

    /**
     * Log a trace in the firebug console
     *
     * @see FirePHP::TRACE
     * @param string $Label
     * @return true
     * @throws Exception
     */
    public static function trace($Label)
    {
        self::logHitData($Object, $Label, true);
        return self::send($Label, FirePHP::TRACE);
    }

    /**
     * Log a table in the firebug console
     *
     * @see FirePHP::TABLE
     * @param string $Label
     * @param string $Table
     * @return true
     * @throws Exception
     */
    public static function table($Label, $Table)
    {
        return self::send($Table, $Label, FirePHP::TABLE);
    }

}
