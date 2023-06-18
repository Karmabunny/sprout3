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

use InvalidArgumentException;

/**
 *
 */
class Modules
{

    /** @var ModuleInterface[] */
    private static $modules = [];



    /**
     * Register a module.
     *
     * @param string $module class name
     * @return void
     */
    public static function register(string $module)
    {
        if (!class_exists($module)) {
            throw new InvalidArgumentException("Module class does not exist: '{$module}'");
        }

        if (!is_a($module, ModuleInterface::class, true)) {
            throw new InvalidArgumentException("Not a module: '{$module}'");
        }

        $name = $module::getName();

        if ($existing = self::$modules[$name] ?? null) {
            // It's unlikely.
            // But hella confusing if it did happen and we said nothing.
            if ($module !== $existing::class) {
                throw new InvalidArgumentException("Module name collision: '{$name}'");
            }

            return;
        }

        $instance = new $module();
        self::$modules[$name] = $instance;
    }


    /**
     * Gets the list of active modules
     *
     * @return ModuleInterface[]
     */
    public static function getModules()
    {
        return self::$modules;
    }


    /**
     * Is this module installed?
     *
     * @param string $name
     * @return bool
     */
    public static function isInstalled(string $name): bool
    {
        return isset(self::$modules[$name]);
    }


    /**
     * Get a module by it's name.
     *
     * @param string $name shorthand name
     * @return null|ModuleInterface
     */
    public static function getModule(string $name): ?ModuleInterface
    {
        return self::$modules[$name] ?? null;
    }


    /**
     * Find which module this path belongs to.
     *
     * @param string $path a file path, for a class or asset
     * @return null|ModuleInterface
     */
    public static function getModuleByPath(string $path): ?ModuleInterface
    {
        foreach (self::$modules as $module) {
            if (strpos($path, $module->getPath()) === 0) {
                return $module;
            }
        }

        return null;
    }


    /**
     * Find which module this class belongs to.
     *
     * @param object|string $target
     * @return null|ModuleInterface
     */
    public static function getModuleFor($target): ?ModuleInterface
    {
        if (is_object($target)) {
            $target = get_class($target);
        }

        $path = Sprout::determineFilePath($target);
        return self::getModuleByPath($path);
    }

}
