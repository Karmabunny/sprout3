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

use OpenAI\Client;

/**
 * Allows for hot-swapping of other providers into the OpenAi ecosystem
 */
trait OpenAiClientTrait
{

    /** @var Client|null */
    private static $_client = null;


    /** @var string */
    private static $_client_key = '';


    /**
     * Create a new client instance
     *
     * @param string $key
     * @param string|null $organization
     * @param int|null $timeout
     * @return Client
     */
    abstract public static function createClient(string $key, string $organization = null, int $timeout = null): Client;


    /**
     * Set the client instance
     *
     * @param Client $client
     * @return void
     */
    public static function setOverrideClient(Client $client): void
    {
        self::$_client = $client;
    }


    /**
     * Get the override client instance, if set
     *
     * @return Client|null
     */
    public static function getOverrideClient(): ?Client
    {
        return self::$_client;
    }


    /**
     *
     * @return void
     */
    public static function clearOverrideClient(): void
    {
        self::$_client = null;
        self::$_client_key = '';
    }

}
