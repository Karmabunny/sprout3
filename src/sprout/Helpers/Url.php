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
use karmabunny\kb\Events;
use LogicException;
use Sprout\Events\RedirectEvent;
use Sprout\Events\SendHeadersEvent;
use Sprout\Events\ShutdownEvent;

/**
 * Helper functions for working with URLs.
 */
class Url
{

    /**
     * Fetches the current URI.
     *
     * @param   boolean  $qs  include the query string
     * @return  string
     */
    public static function current($qs = FALSE)
    {
        return ($qs === TRUE) ? Router::$complete_uri : Router::$current_uri;
    }

    /**
     * Base URL, with or without the index page.
     *
     * If protocol is specified, a full URL including the domain will be used
     * otherwise only the root path will be used
     *
     * If a subsite-section has a defined URL prefix, it will be added to the URL automatically
     *
     * @param   bool $index include the index page
     * @param   string|false $protocol non-default protocol
     * @return  string
     */
    public static function base($index = FALSE, $protocol = FALSE)
    {
        // Load the site domain
        $site_domain = (string) Kohana::config('core.site_domain', TRUE);

        if ($protocol == FALSE)
        {
            // Use the configured site domain
            $base_url = $site_domain;
        }
        else
        {
            // Guess the server name if the domain starts with slash
            $base_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $site_domain;
        }

        $base_url .= SubsiteSelector::$url_prefix;

        if ($index === TRUE AND $index = Kohana::config('core.index_page'))
        {
            // Append the index page
            $base_url = $base_url . $index;
        }

        // Force a slash on the end of the URL
        $base_url = rtrim($base_url, '/').'/';

        return $base_url;
    }

    /**
     * Fetches an absolute site URL based on a URI segment.
     *
     * @param   string $uri site URI to convert
     * @param   string|false $protocol non-default protocol
     * @return  string
     */
    public static function site($uri = '', $protocol = FALSE)
    {
        if ($path = trim(parse_url($uri, PHP_URL_PATH), '/'))
        {
            // Add path suffix
            $path .= Kohana::config('core.url_suffix');
        }

        if ($query = parse_url($uri, PHP_URL_QUERY))
        {
            // ?query=string
            $query = '?'.$query;
        }

        if ($fragment = parse_url($uri, PHP_URL_FRAGMENT))
        {
            // #fragment
            $fragment =  '#'.$fragment;
        }

        // Strip the base if it is already in the path
        $base = trim(Url::base(TRUE), '/');
        if ($base != '' and strpos($path, $base) === 0)
        {
            $path = substr($path, strlen($base) + 1);
        }

        $url = Url::base(TRUE, $protocol).$path.$query.$fragment;
        return $url;
    }

    /**
     * Return the URL to a file. Absolute filenames and relative filenames
     * are allowed.
     *
     * @param   string   $file   filename
     * @param   boolean  $index  include the index page
     * @return  string
     */
    public static function file($file, $index = FALSE)
    {
        if (strpos($file, '://') === FALSE)
        {
            // Add the base URL to the filename
            $file = Url::base($index).$file;
        }

        return $file;
    }

    /**
     * Merges an array of arguments with the current URI and query string to
     * overload, instead of replace, the current query string.
     *
     * @param   array $arguments associative array of arguments
     * @return  string
     */
    public static function merge(array $arguments)
    {
        if ($_GET === $arguments)
        {
            $query = Router::$query_string;
        }
        elseif ($query = http_build_query(array_merge($_GET, $arguments)))
        {
            $query = '?'.$query;
        }

        // Return the current URI with the arguments merged into the query string
        return Router::$current_uri.$query;
    }

    /**
     * Sends a page redirect header and runs the system.redirect Event.
     *
     * @param  string|string[] $uri site URI or URL to redirect to, or array of strings if method is 300
     * @param  string $method HTTP method of redirect
     * @return never
     * @throws LogicException
     */
    public static function redirect($uri = '', $method = '302')
    {
        if (Events::hasRun(Kohana::class, SendHeadersEvent::class)) {

            if (!IN_PRODUCTION) {
                throw new LogicException("Attempting to redirect after headers have been sent.");
            }

            exit;
        }

        $codes = array
        (
            'refresh' => 'Refresh',
            '300' => 'Multiple Choices',
            '301' => 'Moved Permanently',
            '302' => 'Found',
            '303' => 'See Other',
            '304' => 'Not Modified',
            '305' => 'Use Proxy',
            '307' => 'Temporary Redirect'
        );

        // Validate the method and default to 302
        $method = isset($codes[$method]) ? (string) $method : '302';

        if (strpos($uri, '://') === FALSE)
        {
            // HTTP headers expect absolute URLs
            $uri = Url::site($uri, Request::protocol());
        }

        if ($method === '300')
        {
            $uri = (array) $uri;

            $output = '<ul>';
            foreach ($uri as $link)
            {
                $output .= '<li>'.Html::anchor($link).'</li>';
            }
            $output .= '</ul>';

            // The first URI will be used for the Location header
            $uri = $uri[0];
        }
        else
        {
            $output = '<p>'.Html::anchor($uri).'</p>';
        }

        // Run the redirect event
        $event = new RedirectEvent(['uri' => $uri]);
        Events::trigger(Kohana::class, $event);
        $uri = $event->uri;

        if ($method === 'refresh')
        {
            header('Refresh: 0; url='.$uri);
        }
        else
        {
            header('HTTP/1.1 '.$method.' '.$codes[$method]);
            header('Location: '.$uri);
        }

        // We are about to exit, so run the send_headers event
        $event = new SendHeadersEvent();
        Events::trigger(Kohana::class, $event);

        // If using a session driver, the session needs to be explicitly saved
        $event = new ShutdownEvent();
        Events::trigger(Kohana::class, $event);

        exit('<h1>'.$method.' - '.$codes[$method].'</h1>'.$output);
    }

    /**
     * Removes one or more argumens from the current URL, returning a URL which can have arguments appended to it
     * Multiple arguments can be specified
     *
     * If the current URL is:
     *     /search?q=test&category=general
     * and the function call is:
     *     Url::withoutArgs('q')
     * the resulting URL will be:
     *     /search?category=general&
     *
     * Use rtrim($url, '&?') if you do not desire the trailing ? or &
     *
     * @return string
     */
    public static function withoutArgs(...$args)
    {
        $url_base = Url::base() . Url::current() . '?';
        $get = $_GET;

        foreach ($args as $a)
        {
            unset ($get[$a]);
        }

        if (count($get)) {
            $url_base .= http_build_query($get) . '&';
        }

        return $url_base;
    }


    /**
    * Checks the provided argument is a valid redirect URL to the local site
    *
    * This is designed to prevent redirects to other domains, bad pages, etc
    *
    * @param string $url
    * @param bool $allow_querysting If true, allow querystrings too
    * @return bool
    **/
    public static function checkRedirect($url, $allow_querysting = false)
    {
        if ($url === '') return true;

        $url = trim(Enc::cleanfunky($url));
        if ($url === '') return false;

        $regex = '/^';
        $regex .= '[-_a-zA-Z0-9.\/]+';
        if ($allow_querysting) $regex .= '(\?[-_a-zA-Z0-9=&% ]+)?';
        $regex .= '$/i';

        if (! preg_match($regex, $url)) {
            return false;
        }

        return true;
    }


    /**
     * Add a scheme (e.g. 'http://') to a URL which doesn't have one
     *
     * @param string $url May or may not contain a scheme
     * @return string URL Always contains a scheme
     */
    public static function addUrlScheme($url)
    {
        if (preg_match('!^https?://!i', $url)) {
            return $url;
        } else {
            return 'http://' . $url;
        }
    }


    /**
     * Add a domain to provided social link, if it doesn't have one
     *
     * @param string $social_link Social link, e.g. 'https://instagram.com/kbtestbot3000' or 'kbtestbot3000'
     * @param string $domain, e.g. 'instagram.com'
     * @return string Social link with domain, e.g. 'https://instagram.com/kbtestbot3000'
     */
    public static function addSocialDomain($social_link, $domain)
    {
        $url = parse_url($social_link);

        // A URL such as "instagram.com/kbtestbot3000" will be treated as if there isn't a host specified
        // As we know the exepcted hostname, it's possible to look for this and make an assumption
        if (empty($url['host']) and !empty($url['path'])) {
            $domain_loc = stripos($url['path'], $domain . '/');
            if ($domain_loc !== false) {
                $url['host'] = $domain;
                $url['path'] = substr($url['path'], $domain_loc + strlen($domain));
            }
        }

        if (empty($url['scheme'])) {
            $url['scheme'] = 'https';
        } else {
            $url['scheme'] = strtolower($url['scheme']);
        }

        if (empty($url['host'])) {
            $url['host'] = $domain;
        } else {
            $url['host'] = strtolower($url['host']);
        }

        if ($url['path'][0] != '/') {
            $url['path'] = '/' . $url['path'];
        }

        $out = $url['scheme'] . '://' . $url['host'] . $url['path'];

        if (!empty($url['query'])) {
            $out .= '?' . $url['query'];
        }

        if (!empty($url['fragment'])) {
            $out .= '#' . $url['fragment'];
        }

        return $out;
    }


    /**
     * Return HTML for canonical URLs
     *
     * @param string $canonical_url
     * @return string HMTL
     */
    public static function canonical($canonical_url)
    {
        $parts = parse_url($canonical_url);

        // Attempt to determine if 3rd-party or local URL and fill in the blanks
        if (empty($parts['scheme'])) $parts['scheme'] = Request::protocol();
        if (empty($parts['host'])) $parts['host'] = str_replace($parts['scheme'], '', Sprout::absRoot($parts['scheme']));
        if (empty($parts['path'])) $parts['path'] = '';
        if (strpos($parts['host'],'://') === false) $parts['host'] = '://' . $parts['host'];

        $canonical_url = $parts['scheme'] . $parts['host'] . $parts['path'];
        return sprintf('<link rel="canonical" href="%s">', Enc::html($canonical_url));
    }
}
