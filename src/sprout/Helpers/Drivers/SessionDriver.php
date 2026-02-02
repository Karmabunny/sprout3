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
namespace Sprout\Helpers\Drivers;


/**
 * Session driver interface
 */
interface SessionDriver {

    /**
     * Opens a session.
     *
     * @param   string $path Save path
     * @param   string $name Session name
     * @return  boolean
     */
    public function open($path, $name);

    /**
     * Closes a session.
     *
     * @return  boolean
     */
    public function close();

    /**
     * Reads a session.
     *
     * @param   string $id Session id
     * @return  string
     */
    public function read($id);

    /**
     * Writes a session.
     *
     * @param   string $id Session id
     * @param   string $data Session data
     * @return  boolean
     */
    public function write($id, $data);

    /**
     * Destroys a session.
     *
     * @param   string $id Session id
     * @return  boolean
     */
    public function destroy($id);

    /**
     * Regenerates the session id.
     *
     * @return  string
     */
    public function regenerate();

    /**
     * Garbage collection.
     *
     * @param   int|string $maxlifetime Session expiration period
     * @return  boolean
     */
    public function gc($maxlifetime);

} // End Session Driver Interface
