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

use Kohana_Exception;


/**
 * Kohana event subject. Uses the SPL observer pattern.
 */
abstract class Event_Subject {

    // Attached subject listeners
    protected $listeners = array();

    /**
     * Attach an observer to the object.
     *
     * @chainable
     * @param   object  Event_Observer
     * @return  object
     */
    public function attach(Event_Observer $obj)
    {
        if ( ! ($obj instanceof Event_Observer))
            throw new Kohana_Exception('eventable.invalid_observer', get_class($obj), get_class($this));

        // Add a new listener
        $this->listeners[spl_object_hash($obj)] = $obj;

        return $this;
    }

    /**
     * Detach an observer from the object.
     *
     * @chainable
     * @param   object  Event_Observer
     * @return  object
     */
    public function detach(Event_Observer $obj)
    {
        // Remove the listener
        unset($this->listeners[spl_object_hash($obj)]);

        return $this;
    }

    /**
     * Notify all attached observers of a new message.
     *
     * @chainable
     * @param   mixed   message string, object, or array
     * @return  object
     */
    public function notify($message)
    {
        foreach ($this->listeners as $obj)
        {
            $obj->notify($message);
        }

        return $this;
    }

} // End Event Subject
