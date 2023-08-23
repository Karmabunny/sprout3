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

use Kohana;
use Kohana_Exception;


/**
 * The Encrypt library provides two-way encryption of text and binary strings
 * using the openSSL extension.
 */
class Encrypt
{

    // Configuration
    protected $config;

    /**
     * Returns a singleton instance of Encrypt.
     *
     * @param   array  configuration options
     * @return  Encrypt
     */
    public static function instance($config = NULL)
    {
        static $instance;

        // Create the singleton
        empty($instance) and $instance = new Encrypt((array) $config);

        return $instance;
    }

    /**
     * Loads encryption configuration and validates the data.
     *
     * @param   array|string      custom configuration or config group name
     * @throws  Kohana_Exception
     */
    public function __construct($config = FALSE)
    {
        if (is_string($config))
		{
			$name = $config;

			// Test the config group name
			if (($config = Kohana::config('encryption.'.$config)) === NULL)
				throw new Kohana_Exception('encrypt.undefined_group', $name);
		}

		if (is_array($config))
		{
			// Append the default configuration options
			$config += Kohana::config('encryption.default');
		}
		else
		{
			// Load the default group
			$config = Kohana::config('encryption.default');
		}

		if (empty($config['key']))
			throw new Kohana_Exception('encrypt.no_encryption_key');


		$ciphers = openssl_get_cipher_methods(true);

		if (!in_array($config['cipher'], $ciphers)) {
			throw new Kohana_Exception('encrypt.invalid_cipher', $config['cipher']);
		}

		// Cache the config in the object
		$this->config = $config;
    }

    /**
     * Encrypts a string and returns an encrypted string that can be decoded.
     *
     * @param   string  data to be encrypted
     * @return  string  encrypted data
     */
    public function encode($data)
    {
        $iv = openssl_random_pseudo_bytes($this->config['iv_size']);

		// Encrypt the data using the configured options and generated iv
		$data = openssl_encrypt($data, $this->config['cipher'], $this->config['key'], 0, $iv);

		// Use base64 encoding to convert to a string
		return base64_encode($iv.$data);
    }

    /**
     * Decrypts an encoded string back to its original value.
     *
     * @param   string  encoded string to be decrypted
     * @return  string  decrypted data
     */
    public function decode($data)
    {
        // Convert the data back to binary
		$data = base64_decode($data);

		// Extract the initialization vector from the data
		$iv = substr($data, 0, $this->config['iv_size']);

		// Remove the iv from the data
		$data = substr($data, $this->config['iv_size']);

		// Return the decrypted data, trimming the \0 padding bytes from the end of the data
		return rtrim(openssl_decrypt($data, $this->config['cipher'], $this->config['key'], 0, $iv), "\0");
    }

} // End Encrypt
