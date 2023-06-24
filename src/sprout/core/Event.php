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

/**
 * Process queuing/execution class. Allows an unlimited number of callbacks
 * to be added to 'events'. Events can be run multiple times, and can also
 * process event-specific data. By default, Kohana has several system events.
 *
 * $Id: Event.php 4390 2009-06-04 03:05:36Z zombor $
 *
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * @link       http://docs.kohanaphp.com/general/events
 */
final class Event {

    // Event callbacks
    private static $events = array();

    // Cache of events that have been run
    private static $has_run = array();

    // Data that can be processed during events
    public static $data;

    /**
     * Add a callback to an event queue.
     *
     * @param   string    $name      event name
     * @param   callable  $callback  http://php.net/callback
     * @return  boolean
     */
    public static function add($name, $callback)
    {
        if ( ! isset(self::$events[$name]))
        {
            // Create an empty event if it is not yet defined
            self::$events[$name] = array();
        }
        elseif (in_array($callback, self::$events[$name], TRUE))
        {
            // The event already exists
            return FALSE;
        }

        // Add the event
        self::$events[$name][] = $callback;

        return TRUE;
    }

    /**
     * Add a callback to an event queue, before a given event.
     *
     * @param   string    $name      event name
     * @param   array     $existing  existing event callback
     * @param   callable  $callback  event callback
     * @return  boolean
     */
    public static function addBefore($name, $existing, $callback)
    {
        if (empty(self::$events[$name]) OR ($key = array_search($existing, self::$events[$name])) === FALSE)
        {
            // Just add the event if there are no events
            return self::add($name, $callback);
        }
        else
        {
            // Insert the event immediately before the existing event
            return self::insertEvent($name, $key, $callback);
        }
    }

    /**
     * Add a callback to an event queue, after a given event.
     *
     * @param   string    $name      event name
     * @param   array     $existing  existing event callback
     * @param   callable  $callback  event callback
     * @return  boolean
     */
    public static function addAfter($name, $existing, $callback)
    {
        if (empty(self::$events[$name]) OR ($key = array_search($existing, self::$events[$name])) === FALSE)
        {
            // Just add the event if there are no events
            return self::add($name, $callback);
        }
        else
        {
            // Insert the event immediately after the existing event
            return self::insertEvent($name, $key + 1, $callback);
        }
    }

    /**
     * Inserts a new event at a specfic key location.
     *
     * @param   string    $name      event name
     * @param   int       $key       key to insert new event at
     * @param   callable  $callback  event callback
     * @return  boolean
     */
    private static function insertEvent($name, $key, $callback)
    {
        if (in_array($callback, self::$events[$name], TRUE))
            return FALSE;

        // Add the new event at the given key location
        self::$events[$name] = array_merge
        (
            // Events before the key
            array_slice(self::$events[$name], 0, $key),
            // New event callback
            array($callback),
            // Events after the key
            array_slice(self::$events[$name], $key)
        );

        return TRUE;
    }

    /**
     * Replaces an event with another event.
     *
     * @param   string    $name      event name
     * @param   callable  $existing  event to replace
     * @param   callable  $callback  new callback
     * @return  boolean
     */
    public static function replace($name, $existing, $callback)
    {
        if (empty(self::$events[$name]) OR ($key = array_search($existing, self::$events[$name], TRUE)) === FALSE)
            return FALSE;

        if ( ! in_array($callback, self::$events[$name], TRUE))
        {
            // Replace the exisiting event with the new event
            self::$events[$name][$key] = $callback;
        }
        else
        {
            // Remove the existing event from the queue
            unset(self::$events[$name][$key]);

            // Reset the array so the keys are ordered properly
            self::$events[$name] = array_values(self::$events[$name]);
        }

        return TRUE;
    }

    /**
     * Get all callbacks for an event.
     *
     * @param   string  $name  event name
     * @return  array
     */
    public static function get($name)
    {
        return empty(self::$events[$name]) ? array() : self::$events[$name];
    }

    /**
     * Clear some or all callbacks from an event.
     *
     * @param   string          $name      event name
     * @param   callable|false  $callback  specific callback to remove, FALSE for all callbacks
     * @return  void
     */
    public static function clear($name, $callback = FALSE)
    {
        if ($callback === FALSE)
        {
            self::$events[$name] = array();
        }
        elseif (isset(self::$events[$name]))
        {
            // Loop through each of the event callbacks and compare it to the
            // callback requested for removal. The callback is removed if it
            // matches.
            foreach (self::$events[$name] as $i => $event_callback)
            {
                if ($callback === $event_callback)
                {
                    unset(self::$events[$name][$i]);
                }
            }
        }
    }

    /**
     * Execute all of the callbacks attached to an event.
     *
     * @param   string  $name  event name
     * @param   array   $data  data can be processed as Event::$data by the callbacks
     * @return  void
     */
    public static function run($name, & $data = NULL)
    {
        if ( ! empty(self::$events[$name]))
        {
            // So callbacks can access Event::$data
            self::$data =& $data;
            $callbacks  =  self::get($name);

            foreach ($callbacks as $callback)
            {
                call_user_func($callback);
            }

            // Do this to prevent data from getting 'stuck'
            $clear_data = '';
            self::$data =& $clear_data;
        }

        // The event has been run!
        self::$has_run[$name] = $name;
    }

    /**
     * Check if a given event has been run.
     *
     * @param   string   $name  event name
     * @return  boolean
     */
    public static function hasRun($name)
    {
        return isset(self::$has_run[$name]);
    }

} // End Event
