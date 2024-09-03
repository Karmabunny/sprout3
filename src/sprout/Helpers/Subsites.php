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
use InvalidArgumentException;

use Kohana;

use karmabunny\pdb\Exceptions\QueryException;


/**
* Provides functions for getting information about subsites
**/
class Subsites
{
    private static $subsites = null;
    private static $configs = null;
    private static $default = null;

    /**
    * Loads all the subsites into memory, ready for further processing
    **/
    static private function loadSubsites()
    {
        if (self::$subsites !== null) return;

        self::$subsites = [];

        $q = "SELECT id, cond_directory, cond_domain, content_id, name, code, mobile
            FROM ~subsites
            ORDER BY record_order";
        try {
            $result = Pdb::query($q, [], 'pdo');

            foreach ($result as $sub) {
                $sub['cond_domains'] = array_filter(explode("\n", $sub['cond_domain'] ?? ''));
                self::$subsites[$sub['id']] = $sub;
            }

            $result->closeCursor();

        } catch (QueryException $ex) {
            // Nothing.
        } finally {
            // Well, that didn't work.
            if (empty(self::$subsites)) {
                self::checkRequireSubsite();

                self::$subsites = array();
                self::$subsites[1] = [
                    'id' => 1,
                    'cond_directory' => '',
                    'cond_domains' => [],
                    'content_id' => '',
                    'name' => 'Site with no DB',
                    'code' => 'default',
                    'mobile' => false,
                ];
            }

            self::$default = reset(self::$subsites);
        }
    }

    /**
     * Get the default subsite.
     *
     * This is the 'first' subsite in the list.
     *
     * OR if the database is not available, a pseudo 'default' subsite instead.
     *
     * @return array db row
     */
    public static function getDefaultSubsite(): array
    {
        self::loadSubsites();
        return self::$default;
    }


    /**
     * Get a subsite by ID.
     *
     * @param int $id
     * @return null|array db row
     */
    public static function getSubsiteById(int $id): ?array
    {
        self::loadSubsites();
        return self::$subsites[$id] ?? null;
    }


    /**
     * Get a subsite by code.
     *
     * @param string $code
     * @return null|array db row
     */
    public static function getSubsiteByCode(string $code): ?array
    {
        self::loadSubsites();

        foreach (self::$subsites as $subsite) {
            if ($subsite['code'] === $code) {
                return $subsite;
            }
        }

        return null;
    }


    /**
    * Determines if multiple subsites can be accessed by the currently logged in administrator
    *
    * @return bool True if multiple subsites are available, false if they are not
    **/
    public static function hasMultiple()
    {
        self::loadSubsites();
        AdminAuth::checkLogin();

        $count = 0;
        foreach (self::$subsites as $sub) {
            if (AdminPerms::canAccessSubsite($sub['id'])) $count++;
        }

        return ($count > 1);
    }


    /**
    * Outputs a select UL for choosing a subsite from within the admin area
    *
    * @param int $selected_id The currently selected subsite, if any
    **/
    public static function listSelector($selected_id = null)
    {
        self::loadSubsites();
        AdminAuth::checkLogin();

        echo '<form action="admin/set_active_subsite" method="post">';
        echo Csrf::token();
        echo "<div class=\"field-element field-element--white field-element--select\">";
            echo "<div class=\"field-label -vis-hidden\">";
                echo "<label for=\"subsite-selector\">Current site</label>";
            echo "</div>";
            echo "<div class=\"field-input\">";
                echo "<select id=\"subsite-selector\" name=\"subsite\">";

                    foreach (self::$subsites as $site) {
                        if (AdminPerms::canAccessSubsite($site['id'])) {
                            $site_name_html = Enc::html($site['name']);

                            // It's a shared-content subsite, they can't be edited
                            if ($site['content_id'] != 0) {
                                $shared_name_html = 'another subsite';
                                foreach (self::$subsites as $ss) {
                                    if ($ss['id'] == $site['content_id']) { $shared_name_html = Enc::html($ss['name']); break; }
                                }

                                echo "<option value=\"\">{$site_name_html} (content from {$shared_name_html})</option>\n";
                                continue;
                            }

                            // Regular subsite with on-state
                            if ($site['id'] == $selected_id) {
                                echo "<option value=\"\" selected>Editing {$site_name_html}</option>\n";
                            } else {
                                echo "<option value=\"{$site['id']}\">{$site_name_html}</option>\n";
                            }
                        }
                    }

                echo "</select>";
            echo "</div>";
        echo "</div>";
        echo '</form>';
    }


    /**
    * Returns the id of the first subsite in the list (alphabetical) that the user is able to access.
    * Returns null if there are no subsites the user can access.
    **/
    public static function getFirstAccessable()
    {
        self::loadSubsites();

        foreach (self::$subsites as $sub) {
            if (AdminPerms::canAccessSubsite($sub['id'])) return $sub['id'];
        }

        return null;
    }


    /**
    * Returns the name of the specified subsite
    **/
    public static function getName($id)
    {
        self::loadSubsites();

        if (!isset(self::$subsites[$id])) {
            throw new InvalidArgumentException("Subsite #{$id} not found");
        }

        return self::$subsites[$id]['name'];
    }

    /**
    * Returns the name of the specified subsite
    **/
    public static function getCode($id)
    {
        self::loadSubsites();

        if (!isset(self::$subsites[$id])) {
            throw new InvalidArgumentException("Subsite #{$id} not found");
        }

        return self::$subsites[$id]['code'];
    }


    /**
    * For a given list of domains, determine the best match based on the current HTTP_HOST
    *
    * If there is an exact match, it is used
    * If there is an ends-with match, it is used
    * Failing all that, the first domain in the list is used
    **/
    static private function determineBestDomain($domains)
    {
        if (empty($_SERVER['HTTP_HOST'])) return $domains[0];

        foreach ($domains as $d) {
            if ($d === $_SERVER['HTTP_HOST']) return $d;
        }

        foreach ($domains as $d) {
            if (strrpos($d, $_SERVER['HTTP_HOST']) === strlen($d) - strlen($_SERVER['HTTP_HOST'])) return $d;
        }

        return $domains[0];
    }


    /**
    * Returns the abs root (including protocol and server name) of the specified subsite
    * Falls back to the current root if no abs root can be found in the database
    * @param int $id The subsite ID, e.g. SubsiteSelector::$subsite_id
    * @param string $protocol The protocol to use for the link. Specifying null (the default)
    *        will cause the protocol to be automatically determined based on the request.
    * @return string An absolute URL including protocol
    **/
    public static function getAbsRoot($id, $protocol = null)
    {
        self::loadSubsites();
        if (!isset(self::$subsites[$id])) {
            throw new InvalidArgumentException("Subsite #{$id} not found");
        }

        if (count(self::$subsites[$id]['cond_domains'])) {
            $domain = self::determineBestDomain(self::$subsites[$id]['cond_domains']);
        } else {
            $domain = $_SERVER['HTTP_HOST'];
        }

        if (!$protocol) {
            if (PHP_SAPI != 'cli') {
                $protocol = Request::protocol();
            } else {
                $protocol = 'http';
            }
        }

        if (!empty(self::$subsites[$id]['cond_directory'])) {
            $path = rtrim(self::$subsites[$id]['cond_directory'] ?? '', '/') . '/';
        } else {
            $path = '';
        }

        return $protocol . '://' . $domain . Kohana::config('config.site_domain') . $path;
    }


    /**
    * Returns the abs root (including protocol and server name) of the current admin subsite
    **/
    public static function getAbsRootAdmin()
    {
        AdminAuth::checkLogin();
        return self::getAbsRoot($_SESSION['admin']['active_subsite'] ?? 0);
    }


    /**
     * Does this request require a subsite?
     *
     * @return bool
     */
    static public function requireSubsite(): bool
    {
        return !preg_match('!^(admin|admin_ajax|testing|dbtools|_media|media_tools)!', Router::$current_uri);
    }


    /**
     * Assert that this request requires a valid subsite.
     *
     * @return void
     * @throws Exception
     */
    static public function checkRequireSubsite()
    {
        if (self::requireSubsite()) {
            throw new Exception('This website does not have any accessible subsites defined');
        }
    }


    /**
    * Get a config value for a given subsite
    *
    * Use this instead of Kohana::config, if you need to target a specific subsite.
    * It always looks in the "sprout" config file, doesn't support other files.
    *
    * @param string $key Configuration key
    * @param int $subsite_id Subsite to get config value for
    * @return mixed Configuration value
    **/
    public static function getConfig($key, $subsite_id)
    {
        $code = self::getCode($subsite_id);

        if (!$code) {
            throw new Exception('Subsite #'. $subsite_id . ' does not have a valid code specified');
        }

        $configuration = self::loadConfig($code);
        return @$configuration[$key];
    }


    /**
    * Admin version of the getConfig function, uses the currently active subsite
    **/
    public static function getConfigAdmin($key)
    {
        AdminAuth::checkLogin();
        return self::getConfig($key, @$_SESSION['admin']['active_subsite']);
    }


    /**
    * Load the configuration for a subsite code.
    * It will only be loaded once per request - it gets cached in a static var
    *
    * @param string $subsite_code The subsite code to load and return config for
    * @return array Configuration for that subsite
    **/
    public static function loadConfig($subsite_code)
    {
        if (isset(self::$configs[$subsite_code])) {
            return self::$configs[$subsite_code];
        }

        if (! file_exists(DOCROOT . 'skin/' . $subsite_code . '/')) {
            throw new Exception('Invalid subsite "' . $subsite_code . '"; skin directory not found.');
        }

        $files = array(
            DOCROOT . 'skin/' . $subsite_code . '/config/sprout.php',
        );

        $configuration = array();
        foreach ($files as $file) {
            if (file_exists($file)) {
                include $file;
                /** @var mixed $config */
            }

            if (isset($config) AND is_array($config)) {
                $configuration = array_merge($configuration, $config);
            }
        }

        self::$configs[$subsite_code] = $configuration;

        return $configuration;
    }


    /**
     * Gets the list of subsite codes which are available, by reading the appropriate directory
     * @return array
     */
    public static function getCodes()
    {
        $codes = [];
        $skin_dir = DOCROOT . 'skin' . DIRECTORY_SEPARATOR;
        $files = scandir($skin_dir);
        foreach ($files as $file) {
            if ($file[0] == '.') continue;
            if ($file == 'unavailable') continue;
            if (!is_dir($skin_dir . $file)) continue;
            $codes[$file] = $file;
        }
        return $codes;
    }

}

