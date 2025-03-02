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

use Exception;
use Kohana;
use Kohana_Exception;
use Sprout\Services\AdminAuthInterface;
use Sprout\Services\RemoteAuthInterface;
use Sprout\Services\ServiceInterface;
use Sprout\Services\TraceInterface;
use Sprout\Services\UserAuthInterface;
use Sprout\Services\UserPermsInterface;

/**
 * External services helper.
 *
 * Other packages or custom modules can register concrete implementations of
 * the interfaces defined in the Sprout\Services namespace.
 */
class Services
{
    const SERVICES = [
        'admin-auth' => AdminAuthInterface::class,
        'user-auth' => UserAuthInterface::class,
        'user-perms' => UserPermsInterface::class,
        'remote' => RemoteAuthInterface::class,
        'trace' => TraceInterface::class,
    ];

    /**
     * Registered services.
     *
     * @var array [interface => class]
     */
    private static $services = [];


    /**
     * Configs.
     *
     * @var array [interface => config]
     */
    private static $configs = [];


    /**
     * Service instances.
     *
     * @var array [interface => object]
     */
    private static $instances = [];


    /**
     * Lock the service registry.
     *
     * This is to prevent rogue code adding services after the fact.
     *
     * @var bool
     */
    private static $locked = false;


    /**
     * Arm the 'locked' flag to prevent further service registrations.
     *
     * @return void
     */
    public static function lock()
    {
        self::$locked = true;
    }


    /**
     * Register a service.
     *
     * @param string $class_name
     * @param array $config
     * @return void
     * @throws Exception
     */
    public static function register(string $class_name, ?array $config = null)
    {
        if (self::$locked) {
            throw new Exception("Service registration locked, please call during loading events.");
        }

        foreach (self::SERVICES as $key => $abstract) {
            if (!is_subclass_of($class_name, $abstract)) continue;

            self::$services[$abstract] = $class_name;
            self::$configs[$abstract] = $config;
            return;
        }

        throw new Exception("Unknown service class: {$class_name}");
    }


    /**
     * Get the config key for a service.
     *
     * @param string $interface
     * @return null|string
     */
    public static function key(string $interface): ?string
    {
        static $keys;
        $keys = $keys ?? array_flip(self::SERVICES);
        return $keys[$interface] ?? null;
    }


    /**
     * The config for a given service.
     *
     * These can be specified _inline_ when registering the service.
     *
     * Or, they can be specified in the 'service' config file. Inline
     * configurations have priority.
     *
     * @param string $interface
     * @return null|array
     * @throws Kohana_Exception
     */
    public static function config(string $interface): ?array
    {
        $config = self::$configs[$interface] ?? null;

        if (is_array($config)) {
            return $config;
        }

        $key = self::key($interface);
        $config = Kohana::config('services.' . $key, false, false);

        return $config;
    }


    /**
     * Get a service instance.
     *
     * @param string $interface
     * @return object|null
     */
    public static function get(string $interface)
    {
        if ($service = self::$instances[$interface] ?? null) {
            return $service;
        }

        if ($class = self::$services[$interface] ?? null) {
            $config = self::config($interface);

            $service = Sprout::instance($class, ServiceInterface::class);
            $service::configure($config ?? []);

            self::$instances[$interface] = $service;
            return $service;
        }

        return null;
    }


    /**
     * Get the user auth service.
     *
     * Sprout provides a default implementation. It just says no most of the time.
     *
     * @return UserAuthInterface
     */
    public static function getUserAuth(): UserAuthInterface
    {
        return self::get(UserAuthInterface::class) ?? new UserAuth();
    }

    /**
     * Get the user permissions helper.
     *
     * Sprout provides a default implementation.
     *
     * @return UserPermsInterface
     */
    public static function getUserPermissions(): UserPermsInterface
    {
        return self::get(UserPermsInterface::class) ?? new UserPerms();
    }


    /**
     * Get the remote auth service.
     *
     * This has no default implementation.
     *
     * @return RemoteAuthInterface|null
     */
    public static function getRemoteAuth(): ?RemoteAuthInterface
    {
        return self::get(RemoteAuthInterface::class);
    }


    /**
     * Get the tracing service.
     *
     * This has no default implementation.
     *
     * @return TraceInterface|null
     */
    public static function getTrace(): ?TraceInterface
    {
        return self::get(TraceInterface::class);
    }
}
