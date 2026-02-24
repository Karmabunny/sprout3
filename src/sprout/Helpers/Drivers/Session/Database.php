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
namespace Sprout\Helpers\Drivers\Session;

use Kohana;

use karmabunny\pdb\Exceptions\RowMissingException;
use karmabunny\pdb\Pdb as PdbPdb;
use Sprout\Helpers\Drivers\SessionDriver;
use Sprout\Helpers\Encrypt;
use Sprout\Helpers\Pdb as SproutPdb;


/**
 * Session database driver.
 */
class Database implements SessionDriver
{
    // Database settings
    protected $table = 'sessions';

    // Encryption
    protected $encrypt;

    /** @var PdbPdb */
    protected $pdb;

    // Session settings
    protected $session_id;
    protected $written = FALSE;

    public function __construct()
    {
        // Load configuration
        $config = Kohana::config('session');

        if ( ! empty($config['encryption']))
        {
            // Load encryption
            $this->encrypt = Encrypt::instance();
        }

        if (is_array($config['storage'])) {
            if (!empty($config['storage']['table'])) {
                // Set the table name
                SproutPdb::validateIdentifier($config['storage']['table']);
                $this->table = $config['storage']['table'];
            }
        }

        // Create a separate connection to avoid transaction conflicts.
        $config = SproutPdb::getConfig('default');
        $this->pdb = PdbPdb::create($config);

        Kohana::log('debug', 'Session Database Driver Initialized');
    }

    public function open($path, $name)
    {
        return TRUE;
    }

    public function close()
    {
        return TRUE;
    }

    public function read($id)
    {
        // Load the session
        try {
            $q = "SELECT data FROM ~{$this->table} WHERE session_id = ? LIMIT 1";
            $data = $this->pdb->query($q, [$id], 'val');
        } catch (RowMissingException $ex) {

            // No current session
            $this->session_id = NULL;
            return '';
        }

        // Set the current session id
        $this->session_id = $id;

        return ($this->encrypt === NULL) ? base64_decode($data) : $this->encrypt->decode($data);
    }

    public function write($id, $data)
    {
        $data = array
        (
            'session_id' => $id,
            'last_activity' => time(),
            'data' => ($this->encrypt === NULL) ? base64_encode($data) : $this->encrypt->encode($data)
        );

        if ($this->session_id === NULL)
        {
            // Insert a new session
            $count = $this->pdb->insert($this->table, $data);
        }
        elseif ($id === $this->session_id)
        {
            // Do not update the session_id
            unset($data['session_id']);

            // Update the existing session
            $count = $this->pdb->update($this->table, $data, array('session_id' => $id));
        }
        else
        {
            // Update the session and id
            $count = $this->pdb->update($this->table, $data, array('session_id' => $this->session_id));

            // Set the new session id
            $this->session_id = $id;
        }

        return (bool) $count;
    }

    public function destroy($id)
    {
        // Delete the requested session
        $this->pdb->delete($this->table, array('session_id' => $id));

        // Session id is no longer valid
        $this->session_id = NULL;

        return TRUE;
    }

    public function regenerate()
    {
        // Generate a new session id
        session_regenerate_id();

        // Return new session id
        return session_id();
    }

    public function gc($maxlifetime)
    {
        // Delete all expired sessions
        $this->pdb->delete($this->table, [['last_activity', '<', time() - $maxlifetime]]);
        return true;
    }

} // End Session Database Driver
