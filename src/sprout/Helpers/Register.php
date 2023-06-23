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

use Kohana;
use Exception;
use InvalidArgumentException;



/**
* This will one day have methods for various different aspects of the cms
* incl. widgets, search handlers, tabs, etc.
* Dare to dream
**/
class Register
{
    private static $moderators = array();
    private static $extra_pages = array();
    private static $pageattrs = array();
    private static $linkspecs = array();
    private static $docimports = array();
    private static $rtelibraries = array();
    private static $sitemap_generators = [];
    private static $emailtexts = array();
    private static $modules = [];
    private static $admin_controllers = [];
    private static $admin_tiles = [];
    private static $widget_tiles = [];
    private static $front_end_controllers = [];
    private static $features = [];
    private static $content_replace_chains = [];
    private static $cron_jobs = [];
    private static $display_conditions = [];
    private static $search_handlers = [];
    private static $dbtool_apis = [];
    private static $site_settings = [];


    /**
     * Register one or many services.
     *
     * ```
     * // Inline configurations.
     * Register::service([
     *    RemoteAuth::class => [
     *       'url' => 'http://example.com/auth',
     *    ],
     *    Trace::class => [
     *       'url' => 'http://example.com/trace',
     *    ],
     * ]);
     *
     * // Implicit configurations, loaded from 'services' (if required).
     * Register::service(RemoteAuth::class);
     *
     * // Multiple services (implicit configurations).
     * Register::service([
     *    RemoteAuth::class,
     *    Trace::class,
     * ]);
     * ```
     *
     * @param string|string[] $services class string or [class] or [class => config]
     * @return void
     * @throws Exception
     */
    public static function services($services)
    {
        if (!is_array($services)) {
            $services = (array) $services;
        }

        foreach ($services as $class_name => $config) {
            // Normalize it.
            if (is_numeric($class_name)) {
                $class_name = $config;
                $config = null;
            }

            Services::register($class_name, $config);
        }
    }


    /**
     * Register doomtools with find/replace tool.
     *
     * @see FindReplace
     * @see FindReplaceInterface
     *
     * @param FindReplaceInterface[] $replacers
     * @return void
     */
    public static function findReplace($replacers)
    {
        foreach ($replacers as $replace) {
            FindReplace::register($replace);
        }
    }


    /**
    * Register a moderation class
    **/
    public static function moderator($class_name)
    {
        self::$moderators[] = $class_name;
    }

    /**
    * Get all moderation classes
    **/
    public static function getModerators()
    {
        return self::$moderators;
    }



    /**
    * Register a type for an extra page
    **/
    public static function extraPage($name, $label)
    {
        self::$extra_pages[$name] = $label;
    }

    /**
    * Get all extra pages
    **/
    public static function getExtraPages()
    {
        return self::$extra_pages;
    }


    /**
     * Register a type for an extra page
     * @param string $name Name of the attribute (lowercase, no spaces), e.g 'sprout.lang'
     * @param string $label Human-readable label for the attribute, e.g. 'Language'
     * @param string $editor Class name of the AttrEditor used to edit the attribute. If not specified using a
     *        namespace, the 'Sprout\Helpers' namespace is assumed.
     **/
    public static function pageattr($name, $label, $editor = 'Sprout\\Helpers\\AttrEditorTextbox')
    {
        if (strpos($editor, '\\') === false) $editor = "Sprout\\Helpers\\{$editor}";

        self::$pageattrs[$name] = array($label, $editor);
    }

    /**
    * Get all extra pages
    **/
    public static function getPageattrs()
    {
        return self::$pageattrs;
    }


    /**
     * Register a front-end controller (also called a tool page)
     * @param string $name Fully namespaced class name of Controller which MUST implement FrontEndEntrance,
     *        e.g. 'SproutModules\\Karmabunny\\Users\\Controllers\\UserController'
     * @param string $label Human-readable short name, e.g. 'Users'
     * @return void
     * @throws InvalidArgumentException If controller doesn't implement FrontEndEntrance
     */
    public static function frontEndController($name, $label)
    {
        $reflect = new \ReflectionClass($name);
        if (!$reflect->implementsInterface('Sprout\\Helpers\\FrontEndEntrance')) {
            throw new InvalidArgumentException($name . " doesn't implement FrontEndEntrance");
        }
        self::$front_end_controllers[$name] = $label;
    }

    /**
    * Get all front-end controllers
    **/
    public static function getFrontEndControllers()
    {
        return self::$front_end_controllers;
    }


    /**
    * Register a LinkSpec
    **/
    public static function linkspec($name, $label)
    {
        self::$linkspecs[$name] = $label;
    }

    /**
    * Get all `LinkSpec`s
    **/
    public static function getLinkspecs()
    {
        return self::$linkspecs;
    }


    /**
    * Register a DocImport class
    **/
    public static function docImport($ext, $class, $label)
    {
        self::$docimports[$ext] = array($class, $label);
    }

    /**
    * Get all `DocImport`s
    **/
    public static function getDocImports()
    {
        return self::$docimports;
    }


    /**
    * Register a RteLibrary class
    **/
    public static function rteLibrary($class_name)
    {
        self::$rtelibraries[] = $class_name;
    }

    /**
    * Get all `RteLibrary`s
    **/
    public static function getRteLibraries()
    {
        return self::$rtelibraries;
    }


    /**
     * Register a sitemap.xml generator class
     */
    public static function sitemapGen($class_name)
    {
        self::$sitemap_generators[] = $class_name;
    }

    /**
     * Get a list of all sitemap.xml generator classes
     */
    public static function getSitemapGens()
    {
        return self::$sitemap_generators;
    }


    /**
    * Register an email text
    *
    * @param string $code The template code
    *        e.g. user.welcome
    *
    * @param array $field_defs An array of name => description field definitions, for the admin
    *        e.g. array('first_name' => 'First name of the new user)
    *
    * @param string $default_html_view The view name for the default text. Must be a .htm view
    *        e.g 'email/user_welcome'
    **/
    public static function emailText($code, array $field_defs, $default_html_view)
    {
        self::$emailtexts[$code] = new EmailTextReg($field_defs, $default_html_view);
    }

    /**
    * Get all registered email texts. Returns an array of EmailTextReg objects
    **/
    public static function getEmailTexts()
    {
        return self::$emailtexts;
    }

    /**
    * Get a single registered email texts. Returns an EmailTextReg object
    **/
    public static function getEmailText($code)
    {
        return self::$emailtexts[$code];
    }


    /**
     * Registers a list of modules
     * @param array $names The names of the modules, e.g. ['HomePage', 'Users']
     * @return void
     */
    public static function modules(array $names)
    {
        foreach ($names as $name) {
            self::module($name);
        }
    }

    /**
     * Registers a module
     * @param string $name The name of the module, e.g. 'home_page'
     * @return void
     */
    public static function module($name)
    {
        if (!preg_match('/^[-_a-z0-9]+$/i', $name)) {
            throw new Exception('Invalid module name');
        }
        if (in_array($name, self::$modules)) return;
        self::$modules[] = $name;
    }

    /**
     * Gets the list of active modules
     * @return array
     */
    public static function getModules()
    {
        return self::$modules;
    }

    /**
     * Gets a list of paths to the active modules
     * @return array
     */
    public static function getModuleDirs()
    {
        $dirs = [];
        foreach (self::$modules as $module) {
            $dirs[] = DOCROOT . 'modules/' . $module;
        }
        return $dirs;
    }


    /**
     * Registers a module's shorthand controller names for the admin controller
     *
     * Two invocations:
     *
     * ```
     * // 1. Explicit namespace (recommended)
     * Register::adminControllers([
     *    'my-controller' => MyController::class,
     *    'something-else' => SomeController::class,
     * ]);
     *
     * // 2. Fragment namespaces (deprecated):
     * Register::adminControllers('Namespace\To\Module', [
     *    'my-controller' => 'Admin\MyController',
     *    'something-else' => 'Other\Fragment\To\SomeController',
     * ]);
     * ```
     *
     * In the second form, the namespace is forced into a
     * `SproutModules\\Author\\ModuleName\\` format. There's no requirement
     * for this but has been convention until now.
     *
     * To also encourage better readability and static analysis, the fully
     * namespaced form is recommended.
     *
     * @param string|array $namespace namespace including both developer and module
     *        name but not 'Controllers' segment, e.g. Karmabunny\HomePage\Admin
     * @param array|null $controllers map of lowercased shorthand names to class
     *        names within the specified namespace, e.g. ['home' => 'Admin\HomePageAdminController']
     * @return void
     * @throws InvalidArgumentException
     */
    public static function adminControllers($namespace, array $controllers = null)
    {
        $prefix = '';

        // 1st form.
        if (is_array($namespace)) {
            $controllers = $namespace;
            $namespace = null;
        }

        // 2nd form, apply a namespace prefix.
        if (is_string($namespace)) {
            if (strpos($namespace, '\\') === false) {
                throw new Exception("Invalid namespace: '{$namespace}'");
            }

            $prefix = "SproutModules\\{$namespace}\\Controllers\\";
        }

        // Technically there's a 3rd (valid) form:
        // Register::adminControllers(null, [ ... ]);

        if ($controllers === null) {
            throw new InvalidArgumentException("Missing 'controllers' map");
        }

        foreach ($controllers as $shorthand => $class) {
            $full_class = $prefix . $class;

            if (!class_exists($full_class)) {
                throw new InvalidArgumentException("Class not found: '{$full_class}'");
            }

            if (isset(self::$admin_controllers[$shorthand])) {
                throw new InvalidArgumentException("Duplicate shorthand: {$shorthand}");
            }

            self::$admin_controllers[$shorthand] = $full_class;
        }
    }

    /**
     * Converts an shorthand admin controller name to its full class name,
     * including modular namespace
     * @param string $shorthand
     * @return string class name
     * @throws InvalidArgumentException
     */
    public static function getAdminController($shorthand)
    {
        if (!isset(self::$admin_controllers[$shorthand])) {
            throw new InvalidArgumentException("Unrecognised shorthand: {$shorthand}");
        }
        return self::$admin_controllers[$shorthand];
    }

    /**
     * Get the shorthand for a given admin controller class.
     *
     * @param string $class
     * @return string shorthand
     * @throws InvalidArgumentException
     */
    public static function getAdminControllerShorthand(string $class): string
    {
        $shorthand = array_search($class, self::$admin_controllers);

        if ($shorthand === false) {
            throw new InvalidArgumentException("Unrecognised admin controller: {$shorthand}");
        }

        return $shorthand;
    }

    /**
     * Gets the list of modular admin controllers with registered shorthands
     * @return string[] shorthand => full class name
     */
    public static function getAdminControllers()
    {
        return self::$admin_controllers;
    }


    /**
     * Register a "tile", which is shown in the modules menu in the admin
     *
     * A list of available icons can be found in the file {@see sprout/media/fonts/iconfont/demo.html}
     *
     * @param string $name The name (title) of the tile
     * @param string $icon The icon class, sans the 'icon-' part. Examples: 'list', 'grid', 'home', etc.
     * @param string $text Explanation text. Plaintext.
     * @param array $controllers Controllers to show as links, in format 'shorthand' => 'label'
     * @param int $sort_order Tiles are sorted by the sort order, then by the name alphabetically. Lower = earlier.
     */
    public static function adminTile($name, $icon, $text, array $controllers, $sort_order = 10)
    {
        $hidden = \Kohana::config('sprout.admin_tile_hidden');
        if (AdminAuth::isSuper()) {
            foreach ($hidden as $shorthand) {
                if (isset($controllers[$shorthand])) $controllers[$shorthand] .= ' [hidden]';
            }
        } else {
            foreach ($hidden as $shorthand) {
                unset($controllers[$shorthand]);
            }
        }

        if (count($controllers) === 0) return;

        self::$admin_tiles[$sort_order . $name] = [
            'name' => $name,
            'icon' => $icon,
            'text' => $text,
            'controllers' => $controllers,
        ];
    }


    /**
     * Return an array of admin tiles.
     * Each tile has four keys, 'name', 'icon', 'text', and 'controllers'.
     *
     * @return array
     */
    public static function getAdminTiles()
    {
        return self::$admin_tiles;
    }


    /**
     * Register a widget "tile", which is shown when adding widgets (content blocks) to a page
     *
     * If a tile already exists with that name, the widgets will be added to that tile
     *
     * A list of available icons can be found in the file {@see sprout/media/fonts/iconfont/demo.html}
     *
     * @param string $area_name The area to register the tile (e.g. 'embedded')
     * @param string $name The name (title) of the tile
     * @param string $icon The icon class, sans the 'icon-' part. Examples: 'list', 'grid', 'home', etc.
     * @param string $text Explanation text. Plaintext.
     * @param array $widgets Widgets to show as links, in format 'name' => 'label'
     */
    public static function widgetTile($area_name, $name, $icon, $text, array $widgets)
    {
        $area = WidgetArea::findAreaByName($area_name);
        if (!$area) return;

        if (!isset(self::$widget_tiles[$area_name])) {
            self::$widget_tiles[$area_name] = [];
        }

        if (isset(self::$widget_tiles[$area_name][$name])) {
            self::$widget_tiles[$area_name][$name]['widgets'] = array_merge(
                self::$widget_tiles[$area_name][$name]['widgets'],
                $widgets
            );
        } else {
            self::$widget_tiles[$area_name][$name] = [
                'name' => $name,
                'icon' => $icon,
                'text' => $text,
                'widgets' => $widgets,
            ];
        }

        foreach ($widgets as $name => $label) {
            $area->addWidget($name);
        }
    }


    /**
     * Return an array of widget tiles for a given widget area
     * Each tile has four keys, 'name', 'icon', 'text', and 'widgets'.
     *
     * @return array
     */
    public static function getWidgetTiles($area_name)
    {
        if (!isset(self::$widget_tiles[$area_name])) {
            self::$widget_tiles[$area_name] = [];
        }

        return self::$widget_tiles[$area_name];
    }


    /**
     * Register an available feature, and the root namespace where the helpers are found
     *
     * Features allow the Sprout core to access code provided by modules without needing
     * to hard-code the namespace of these modules
     *
     * @example
     *      Register::feature('users', 'SproutModules\Karmabunny\Users');
     *
     * @param string $code The feature code. Only one in use at this time, 'users'
     * @param string $namespace The root namespace (without 'Helpers\' part) of the feature
     */
    public static function feature($code, $namespace)
    {
        self::$features[$code] = $namespace;
    }


    /**
     * Is the given feature currently available?
     *
     * @param string $code The feature code. Only one in use at this time, 'users'
     * @return bool True if the feature is available
     */
    public static function hasFeature($code)
    {
        return isset(self::$features[$code]);
    }


    /**
     * Return the namespace for a given feature
     *
     * @param string $code The feature code. Only one in use at this time, 'users'
     * @return string Namespace
     */
    public static function getFeatureNamespace($code)
    {
        return self::$features[$code];
    }


    /**
     * Register a method for a content replacement chain
     *
     * Registered method will receive one string argument and should return a string.
     * For the 'inner_html' chain, the input and output should be HTML
     *
     * Common chains:
     *      inner_html      The "inside" HTML of widgets (richtext, blog content, etc)
     *      main_content    The main content of inner and wide templates
     *
     * @example
     *      // register method
     *      Register::contentReplace('inner_html', ['Sprout\\Helpers\\ContentReplace', 'intlinks']);
     *
     *      // execute chain
     *      $new_html = ContentReplace::executeChain('inner_html', $old_html);
     *
     * @param string $chain The chain to register the method for, e.g. 'inner_html'
     * @param string callable $func The method to register
     */
    public static function contentReplace($chain, callable $func)
    {
        if (!isset(self::$content_replace_chains[$chain])) {
            self::$content_replace_chains[$chain] = [];
        }
        self::$content_replace_chains[$chain][] = $func;
    }


    /**
     * Return the methods for a given content replace chain
     *
     * @param string $chain The chain to return, e.g. 'inner_html'
     * @return array Callables
     */
    public static function getContentReplaceMethods($chain)
    {
        return self::$content_replace_chains[$chain];
    }


    /**
     * Register a method to be called for cron job processing
     *
     * By default there is only one schedule, 'daily', but it's easy to add
     * another schedule by duplicating the cron_daily.sh file and making the new
     * file call the new schedule
     *
     * @example
     *     Register::cronJob('daily', 'Sprout\\Controllers\\Admin\\PageAdminController', 'cronPageActivate');
     *
     * @param string $schedule The schedule for the job; corresponds to call in the shell script
     *     e.g. 'daily' will be called by the request 'cron_job/run/daily'
     * @param string $class The class containing the cron job method. Must be a controller
     * @param string $func The funciton name to call
     */
    public static function cronJob($schedule, $class, $func)
    {
        if (!isset(self::$cron_jobs[$schedule])) {
            self::$cron_jobs[$schedule] = [];
        }
        self::$cron_jobs[$schedule][] = [$class, $func];
    }


    /**
     * Return the methods for a given cron job schedule
     *
     * @param string $schedule The jobs to return, e.g. 'daily' or 'weekly'
     * @return array Each row has [0] => class, [1] => func
     */
    public static function getCronJobs($schedule)
    {
        return @self::$cron_jobs[$schedule] ?: [];
    }


    /**
     * Return all registered cron jobs
     *
     * @return array Key is schedule, and array is a list of jobs like {@see Register::getCronJobs}
     */
    public static function getAllCronJobs()
    {
        return self::$cron_jobs;
    }


    /**
     * Register an helper to expose to twig templates via the 'sprout' namespace.
     *
     * @param string $name
     * @param mixed $item
     * @return void
     */
    public static function templateVariable($name, $item)
    {
        SproutVariable::register($name, $item);
    }


    /**
     * Register a display condition (this is part of the Context Engine on widgets)
     *
     * Condition classes must extend the base class {@see Sprout\Helpers\DisplayConditions\DisplayCondition}
     *
     * @param string $class Class name, including namespace
     * @param string $group Label of the group, e.g. 'Platform'
     * @param string $label Label of the condtion, e.g. 'Device category'
     */
    public static function displayCondition($class, $group, $label)
    {
        if (empty(self::$display_conditions[$group])) {
            self::$display_conditions[$group] = [];
        }

        self::$display_conditions[$group][$class] = $label;
    }


    /**
     * Return a list of all display condition registrations
     *
     * @return array Key is group label, value is array of conditions [ class => label ]
     */
    public static function getDisplayConditions()
    {
        return self::$display_conditions;
    }


    /**
     * Register search handler
     *
     * @param string $class Controller to register which implements the
     *        FrontEndSearch interface. Must be fully namespaced.
     * @param string $table The name of the keywords table, e.g. page_keywords
     * @param array $where Optional list of where clauses @see SearchHandler->addWhere()
     * @return void
     */
    public static function searchHandler($class, $table, $where = [])
    {
        $handler = new SearchHandler($table, $class);

        if (!empty($where) and count($where) > 0) {
            foreach ($where as $clause) {
                $handler->addWhere($clause);
            }
        }

        self::$search_handlers[] = $handler;
    }


    /**
     * Return list of SearchHandler objects
     *
     * @return array List of SearchHandler instances
     */
    public static function getSearchHandlers()
    {
        $handlers = self::$search_handlers;

        $conf = Kohana::config('sprout.search_handlers');
        if (is_array($conf)) {
            $handlers = array_merge($handlers, $conf);
        }

        return $handlers;
    }


    /**
     * Register an API test form
     *
     * @param array $api [
     *      title => (string) API name,
     *      desc => (string) subtitle,
     *      class => (string) => class name,
     *      method => (string) class method to load form
     * ]
     * @return void
     */
    public static function addDbtoolsApi($api)
    {
        self::$dbtool_apis[] = $api;
    }


    /**
     * Return list of dbtool api tests
     * @return array
     */
    public static function getDbtoolsApi()
    {
        usort(self::$dbtool_apis, function ($a, $b)
        {
            return strcmp($a['title'], $b['title']);
        });
        return self::$dbtool_apis;
    }


    /**
     * Register a site setting
     *
     * @param string $key Setting name
     * @return void
     */
    public static function addSiteSetting($key)
    {
        self::$site_settings[] = $key;
    }


    /**
     * Fetch list of registered site settings
     *
     * @return string[]
     */
    public static function getSiteSettings()
    {
        sort(self::$site_settings);
        return self::$site_settings;
    }
}
