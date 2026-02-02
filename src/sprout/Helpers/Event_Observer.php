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
 * Kohana event observer. Uses the SPL observer pattern.
 */
abstract class Event_Observer {

    // Calling object
    protected $caller;

    /**
     * Initializes a new observer and attaches the subject as the caller.
     *
     * @param   Event_Subject $caller
     * @return  void
     */
    public function __construct(Event_Subject $caller)
    {
        // Update the caller
        $this->update($caller);
    }

    /**
     * Updates the observer subject with a new caller.
     *
     * @chainable
     * @param   Event_Subject $caller
     * @return  $this
     */
    public function update(Event_Subject $caller)
    {
        if ( ! ($caller instanceof Event_Subject))
            throw new Kohana_Exception('event.invalid_subject', get_class($caller), get_class($this));

        // Update the caller
        $this->caller = $caller;

        return $this;
    }

    /**
     * Detaches this observer from the subject.
     *
     * @chainable
     * @return  object
     */
    public function remove()
    {
        // Detach this observer from the caller
        $this->caller->detach($this);

        return $this;
    }

    /**
     * Notify the observer of a new message. This function must be defined in
     * all observers and must take exactly one parameter of any type.
     *
     * @param   mixed $message Message string, object, or array
     * @return  void
     */
    abstract public function notify($message);

} // End Event Observer
