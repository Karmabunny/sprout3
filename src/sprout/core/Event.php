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

use karmabunny\kb\EventInterface;
use karmabunny\kb\Events;
use Sprout\Events\NotFoundEvent;
use Sprout\Events\PostControllerEvent;
use Sprout\Events\PostControllerConstructorEvent;
use Sprout\Events\PreControllerEvent;
use Sprout\Events\SendHeadersEvent;
use Sprout\Events\ShutdownEvent;
use Sprout\Events\DisplayEvent;
use Sprout\Events\RedirectEvent;
use Sprout\Events\SessionWriteEvent;

/**
 * Process queuing/execution class. Allows an unlimited number of callbacks
 * to be added to 'events'. Events can be run multiple times, and can also
 * process event-specific data. By default, Kohana has several system events.
 *
 * $Id: Event.php 4390 2009-06-04 03:05:36Z zombor $
 *
 * @deprecated
 * @package    Core
 * @author     Kohana Team
 * @copyright  (c) 2007 Kohana Team
 * @license    http://kohanaphp.com/license.html
 * @link       http://docs.kohanaphp.com/general/events
 */
final class Event {

    static $EVENTS = [
        'system.404' => NotFoundEvent::class,
        'system.shutdown' => ShutdownEvent::class,
        'system.pre_controller' => PreControllerEvent::class,
        'system.post_controller_constructor' => PostControllerConstructorEvent::class,
        'system.post_controller' => PostControllerEvent::class,
        'system.send_headers' => SendHeadersEvent::class,
        'system.display' => DisplayEvent::class,
        'system.session_write' => SessionWriteEvent::class,
        'system.redirect' => RedirectEvent::class,
    ];

    /**
     * Data that can be processed during events
     *
     * @deprecated
     */
    public static $data;

    /**
     *
     * @param string $name
     * @return string
     * @throws InvalidArgumentException
     */
    private static function getEventClass(string $name)
    {
        $class = self::$EVENTS[$name] ?? null;

        if (!$class) {
            throw new InvalidArgumentException("Unknown event: '{$name}' - this interface is deprecated, please don't add to it.");
        }

        if (!is_a($class, EventInterface::class, true)) {
            throw new InvalidArgumentException("Event '{$class}' is not an EventInterface.");
        }

        return $class;
    }

    /**
     * Add a callback to an event queue.
     *
     * @deprecated
     * @param   string    $name      event name
     * @param   callable  $callback  http://php.net/callback
     * @return  bool
     */
    public static function add($name, $callback)
    {
        $class = self::getEventClass($name);

        if (is_a($class, DisplayEvent::class, true)) {
            Events::on(Kohana::class, function(DisplayEvent $event) use ($callback) {
                Event::$data = &$event->output;

                $callback();

                $clear_data = null;
                Event::$data = &$clear_data;
            });

            return true;
        }


        if (is_a($class, RedirectEvent::class, true)) {
            Events::on(Kohana::class, function(RedirectEvent $event) use ($callback) {
                Event::$data = &$event->uri;

                $callback();

                $clear_data = null;
                Event::$data = &$clear_data;
            });

            return true;
        }

        Events::on(Kohana::class, $class, $callback);
        return true;
    }

    /**
     * Add a callback to an event queue, before a given event.
     *
     * @deprecated
     * @param   string    $name      event name
     * @param   array     $existing  existing event callback
     * @param   callable  $callback  event callback
     * @return  bool
     */
    public static function addBefore($name, $existing, $callback)
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * Add a callback to an event queue, after a given event.
     *
     * @deprecated
     * @param   string    $name      event name
     * @param   array     $existing  existing event callback
     * @param   callable  $callback  event callback
     * @return  bool
     */
    public static function addAfter($name, $existing, $callback)
    {
        throw new BadMethodCallException('Not implemented');
    }


    /**
     * Replaces an event with another event.
     *
     * @deprecated
     * @param   string    $name      event name
     * @param   callable  $existing  event to replace
     * @param   callable  $callback  new callback
     * @return  bool
     */
    public static function replace($name, $existing, $callback)
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * Get all callbacks for an event.
     *
     * @deprecated
     * @param   string  $name  event name
     * @return  array
     */
    public static function get($name)
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * Clear some or all callbacks from an event.
     *
     * @deprecated
     * @param   string          $name      event name
     * @param   callable|false  $callback  specific callback to remove, FALSE for all callbacks
     * @return  void
     */
    public static function clear($name, $callback = FALSE)
    {
        throw new BadMethodCallException('Not implemented');
    }

    /**
     * Execute all of the callbacks attached to an event.
     *
     * @deprecated
     * @param   string  $name  event name
     * @param   mixed   $data  data can be processed as Event::$data by the callbacks
     * @return  void
     */
    public static function run($name, & $data = NULL)
    {
        $class = self::getEventClass($name);

        /** @var EventInterface */
        $event = new $class();

        if ($event instanceof DisplayEvent) {
            if (!is_string($data)) {
                $data = '';
            }

            $event->output = &$data;
        }

        else if ($event instanceof RedirectEvent) {
            if (!is_string($data)) {
                $data = '';
            }

            $event->uri = &$data;
        }

        Events::trigger(Kohana::class, $event);
    }

    /**
     * Check if a given event has been run.
     *
     * @deprecated
     * @param   string   $name  event name
     * @return  bool
     */
    public static function hasRun($name)
    {
        $class = self::getEventClass($name);
        $log = Events::getLogs([
            'sender' => Kohana::class,
            'event' => $class,
        ]);

        return !empty($log);
    }

} // End Event
