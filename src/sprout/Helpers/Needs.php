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
use Sprout\Events\DisplayEvent;

/**
* Provides a system for injecting CSS and Javascript includes into the head of a document even after the head has been outputted.
* Also does replacement for the string "SITE/", which gets changed into the Kohana root directory.
**/
class Needs
{
    private static $needs = array();
    private static $needs_footer = array();


    /**
    * Adds a generic need.
    * If a key is specified, the need is added with that key
    * allowing for updating of the need later.
    *
    * @param string $need The HTML for the need.
    * @param string $key The key to use for the need to avoid duplication.
    * @param string $location Where to add the need (head or footer)
    **/
    public static function addNeed($need, $key = null, $location = 'head')
    {
        switch ($location) {
        case 'head':
            self::addHeadNeed($need, $key);
            break;
        case 'footer':
            self::addFooterNeed($need, $key);
            break;
        default:
            throw new Exception('Invalid <needs> location: ' . $location);
        }
    }


    /**
    * Adds a generic <head> need.
    * If a key is specified, the need is added with that key
    * allowing for updating of the need later.
    *
    * @param string $need The HTML for the need.
    * @param string $key The key to use for the need to avoid duplication.
    *
    * @return void
    **/
    public static function addHeadNeed($need, $key = null)
    {
        if (in_array($need, self::$needs)) return;
        if ($key) {
            self::$needs[$key] = $need;
        } else {
            self::$needs[] = $need;
        }
    }


    /**
    * Adds a generic <footer> need.
    * If a key is specified, the need is added with that key
    * allowing for updating of the need later.
    *
    * @param string $need The HTML for the need.
    * @param string $key The key to use for the need to avoid duplication.
    *
    * @return void
    **/
    public static function addFooterNeed($need, $key = null)
    {
        if (in_array($need, self::$needs_footer)) return;
        if ($key) {
            self::$needs_footer[$key] = $need;
        } else {
            self::$needs_footer[] = $need;
        }
    }

    /**
    * Removes a module
    *
    * e.g. Needs::fileGroup('facebox') can be removed with Needs::removeModule('facebox')
    *
    * @param string $key The key to use for the need to avoid duplication.
    *
    * @return void
    **/
    public static function removeModule($key)
    {
        unset (self::$needs[$key . '-js']);
        unset (self::$needs[$key . '-css']);
        unset (self::$needs_footer[$key . '-js']);
        unset (self::$needs_footer[$key . '-css']);
    }

    /**
    * Adds a CSS include need.
    *
    * @param string $url The URL of the CSS file to include
    * @param array $extra_attrs Extra attributes to add to the LINK tag
    * @param string $key The key to use for the need to avoid duplication.
    * @param string $location Where to add the need (head or footer)
    *
    * @return void
    **/
    public static function addCssInclude($url, $extra_attrs = null, $key = null, $location = 'head')
    {
        if (! isset($extra_attrs['href'])) $extra_attrs['href'] = $url;
        if (! isset($extra_attrs['rel'])) $extra_attrs['rel'] = 'stylesheet';

        $need = '<link' . Html::attributes($extra_attrs) . '>';

        self::addNeed($need, $key, $location);
    }

    /**
    * Adds a JS include need.
    *
    * @param string $url The URL of the CSS file to include
    * @param array $extra_attrs Extra attributes to add to the JAVASCRIPT tag
    * @param string $key The key to use for the need to avoid duplication.
    * @param string $location Where to add the need (head or footer)
    *
    * @return void
    **/
    public static function addJavascriptInclude($url, $extra_attrs = null, $key = null, $location = 'head')
    {
        if (! isset($extra_attrs['src'])) $extra_attrs['src'] = $url;
        if (! isset($extra_attrs['type'])) $extra_attrs['type'] = 'text/javascript';

        $need = '<script' . Html::attributes($extra_attrs) . '></script>';

        self::addNeed($need, $key, $location);
    }


    /**
     * Loads a specific file group.
     * The files in a group are JS and CSS files with matching names, e.g. file.js and file.css
     * The JS files can be minified, so 'file' will match file.min.js; minified files take precedence.
     *
     * The group can be:
     * 1. a straight file basename e.g. 'my_file', or
     * 2. sprout plus a file basename, e.g. 'sprout/my_file', or
     * 3. a module name plus a file basename, e.g. 'MyModule/my_file'
     *
     * The files must be in the 'media/(js/css)/' directory beneath the relevant path.
     * As per the following exaples:
     *
     * If the group name 'fb' is requested, the following two files will be included, if they are found:
     * - media/css/fb.css
     * - media/js/fb.min.js OR media/js/fb.js
     *
     * If 'sprout/admin_layout' is requested, the following two files will be included, if they are found:
     * - sprout/media/css/admin_layout.css
     * - sprout/media/js/admin_layout.min.js OR sprout/media/js/admin_layout.js
     *
     * If 'Users/users' is requested, the following two files will be included, if found:
     * - modules/Users/media/css/users.css
     * - modules/Users/media/js/users.min.js OR modules/Users/media/js/users.js
     *
     * @param string $name The name of the file group, e.g. 'Forms/admin_fields'
     * @param string $location Where to add the need (head or footer)
     *
     * @return void
     * @throws Exception if there are no matching JS or CSS files
     */
    public static function fileGroup($name, $location = 'head')
    {
        if (Router::$controller != 'Sprout\\Controllers\\AdminController' and in_array($name, Kohana::config('sprout.dont_need') ?? [])) return;

        // Backwards compat.
        if (strpos($name, '/') === false) {
            $name = 'core/' . $name;
        }

        // Hack in module section prefixes.
        if (!preg_match('!^(core|sprout|skin|module)!', $name)) {
            $name = 'module/' . $name;
        }

        $media = Media::parse($name);

        // JS files, minified take precedence.
        if (file_exists($media->root . "js/{$media->name}.min.js")) {
            $media->name = "js/{$media->name}.min.js";
            $js_file = 'ROOT/' . $media->generateUrl();

        } else if (file_exists($media->root . "js/{$media->name}.js")) {
            $media->name = "js/{$media->name}.js";
            $js_file = 'ROOT/' . $media->generateUrl();
        }

        // CSS file, minified take precedence.
        if (file_exists($media->root . "css/{$media->name}.min.css")) {
            $media->name = "js/{$media->name}.min.css";
            $css_file = 'ROOT/' . $media->generateUrl();

        } else if (file_exists($media->root . "css/{$media->name}.css")) {
            $media->name = "js/{$media->name}.css";
            $css_file = 'ROOT/' . $media->generateUrl();
        }

        if (!empty($js_file)) {
            self::addJavascriptInclude($js_file, null, $name . '-js', $location);
        }
        if (!empty($css_file)) {
            self::addCssInclude($css_file, null, $name . '-css', $location);
        }
        if (empty($js_file) and empty($css_file)) {
            throw new Exception("No matching JS or CSS files for '{$media->name}' in: '{$media->section}'");
        }
    }


    /**
     * Alias for {@see Needs::fileGroup}
     * @deprecated Since the nomenclature makes no sense
     * @param string $name
     * @param string $location Where to add the need (head or footer)
     *
     * @return void
     */
    public static function module($name, $location = 'head')
    {
        self::fileGroup($name, $location);
    }


    /**
     * Load the Google Maps JavaScript API, including an api key from the sprout config
     *
     * Always loaded in the head
     */
    public static function googleMaps()
    {
        $key = Kohana::config('sprout.google_maps_key');
        if ($key === 'please_generate_me') {
            throw new Exception('Google Maps API key has not been specified');
        } else if (empty($key)) {
            self::addJavascriptInclude('https://maps.google.com/maps/api/js');
        } else {
            self::addJavascriptInclude('https://maps.google.com/maps/api/js?key=' . $key);
        }
    }


    /**
     * Load the Google Maps JavaScript API using sprout config key and given callback JS function name
     *
     * @param string $callback Javascript function name
     * @return void
     */
    public static function googleMapsAsync($callback)
    {
        $key = Kohana::config('sprout.google_maps_key');
        if ($key === 'please_generate_me') {
            throw new Exception('Google Maps API key has not been specified');
        }

        $params = [
            'v' => 3,
            'key' => $key,
            'callback' => $callback,
        ];
        $url = '//maps.googleapis.com/maps/api/js?' . http_build_query($params);

        $need = '<script src="' . Enc::html($url) . '" async defer></script>';

        self::addNeed($need);
    }


    /**
     * Load Google Autocomplete API, including api key from sprout config
     */
    public static function googlePlaces()
    {
        $key = Kohana::config('sprout.google_places_key');

        if ($key == 'please_generate_me') {
            throw new Exception('Google Places API key has not been specified');
        } elseif (empty($key)) {
            self::addJavascriptInclude('https://maps.googleapis.com/maps/api/js?libraries=places');
        } else {
            self::addJavascriptInclude('https://maps.googleapis.com/maps/api/js?key=' . $key . '&libraries=places');
        }
    }


    /**
    * Adds a meta tag (compat wrapper for addMetaName)
    *
    * @deprecated Use addMetaName instead
    *
    * @param string $name The name of the meta element
    * @param string $content The content of the meta element
    * @param array $extra_attrs Extra attributes to add to the META tag
    **/
    public static function addMeta($name, $content, $extra_attrs = null)
    {
        return self::addMetaName($name, $content, $extra_attrs);
    }


    /**
    * Adds a meta tag for a meta 'name'
    *
    * @param string $name The name of the meta element
    * @param string $content The content of the meta element
    * @param array $extra_attrs Extra attributes to add to the META tag
    **/
    public static function addMetaName($name, $content, $extra_attrs = null)
    {
        if (! isset($extra_attrs['name'])) $extra_attrs['name'] = $name;
        if (! isset($extra_attrs['content'])) $extra_attrs['content'] = $content;

        $need = '<meta' . Html::attributes($extra_attrs) . '>';

        self::addNeed($need);
    }


    /**
     * Add a preload tag for a resource.
     *
     * @param string $type style|script|image|font|video|...
     * @param string $url
     * @return void
     */
    public static function addPreload(string $type, string $url)
    {
        $type = Enc::html($type);
        $url = Enc::html($url);

        $need = "<link rel='preload' as='{$type}' href='{$url}'/>";
        self::addNeed($need);
    }


    /**
    * Adds a meta tag for a meta 'property'
    *
    * @param string $property The property of the meta element
    * @param string $content The content of the meta element
    * @param array $extra_attrs Extra attributes to add to the META tag
    **/
    public static function addMetaProperty($property, $content, $extra_attrs = null)
    {
        if (! isset($extra_attrs['property'])) $extra_attrs['property'] = $property;
        if (! isset($extra_attrs['content'])) $extra_attrs['content'] = $content;

        $need = '<meta' . Html::attributes($extra_attrs) . '>';

        self::addNeed($need);
    }


    /**
     * Dynamic loader for <needs/> which have been specified in an AJAX call.
     *
     * NOTE This does not support footer needs.
     *
     * Returns HTML of a snippet of JavaScript which does dynamic loading of the needs.
     * Calls the function "dynamicNeedsLoader" located in media/js/common.js, which does
     * the actual loading of the JS or CSS file. The dynamic loader will only load files
     * not currently loaded.
     *
     * This function must be called after all Needs have been specified.
     *
     * @example
     *     Needs::fileGroup('fb');
     *     echo Needs::ajaxNeedsLoader();   // outputs <script>...</script>
     *
     * @return string HTML Snippet of JavaScript
     */
    public static function dynamicNeedsLoader()
    {
        if (count(self::$needs) == 0) return '';

        $out = '<script>$(document).ready(function(){' . PHP_EOL;

        foreach (self::$needs as $tag) {
            $tag = trim(self::replacePathsString($tag));

            // Browsers don't require (or even work) with HTML encoding of <script> tags in HTML5.
            // This is in contrast to XHTML which (as it's XML) does require encoding or CDATA.
            // The only logic they use is to check for </script> but this breaks the loader.
            // The only solution is to convert this to two separate strings.
            $tag = Enc::js($tag);
            $tag = str_replace('</script>', '</sc" + "ript>', $tag);

            // The bulk of the work in a function located in media/js/common.js
            $out .= 'dynamicNeedsLoader("' . $tag . '");' . PHP_EOL;
        }

        $out .= '});</script>' . PHP_EOL;

        return $out;
    }


    /**
     * Add data to GTM dataLayers
     *
     * @param array $data
     * @return void
     */
    public static function addGTMdataLayer($data)
    {
        Session::Instance();
        if (empty($_SESSION['gtm_datalayers'])) $_SESSION['gtm_datalayers'] = [];
        $_SESSION['gtm_datalayers'][] = $data;
    }


    /**
     * Render GTM dataLayers
     *
     * @return string HTML
     */
    public static function renderGTMDataLayers()
    {
        Session::Instance();
        if (empty($_SESSION['gtm_datalayers'])) return;

        $out = '<script>';
        $out .= 'var dataLayer = window.dataLayer || [];';

        foreach ($_SESSION['gtm_datalayers'] as $data) {
            $out .= 'dataLayer.push(' . json_encode($data) . ');';
        }

        $out .= '</script>';

        unset($_SESSION['gtm_datalayers']);
        return $out;
    }


    /**
    * Does needs replacement on all of the html
    **/
    public static function replacePlaceholders(DisplayEvent $event)
    {
        // Don't do anything if the output isn't HTML
        $headers = headers_list();
        $is_html = false;
        foreach ($headers as $header) {
            if (preg_match('#^Content-type:\s*text/html#i', $header)) {
                $is_html = true;
                break;
            }
        }
        if (!$is_html) return;

        // GTM data layers
        self::addNeed(self::renderGTMDataLayers(), 'gtm_datalayer');

        // Needs
        $event->output = preg_replace('!<needs\s?/?>!', implode ("\n\t", self::$needs), $event->output);
        $event->output = preg_replace('!<needs_footer\s?/?>!', implode ("\n\t", self::$needs_footer), $event->output);

        // Path stuff
        $event->output = self::replacePathsString($event->output);

        // Page links to use slugs instead of IDs
        $event->output = ContentReplace::intlinks($event->output);
    }


    /**
    * Do the path replacements for a provided string
    *
    * TODO this doesn't belong here.
    **/
    public static function replacePathsString($str)
    {
        $str = str_replace('ROOT/', Kohana::config('core.site_domain'), $str);
        $str = str_replace('SITE/', Url::base(TRUE), $str);
        $str = str_replace('SKIN/', Kohana::config('core.site_domain') . 'skin/' . SubsiteSelector::$subsite_code . '/', $str);
        return $str;
    }

}
