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
use Kohana;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Sprout;


abstract class SitemapGen
{

    /** @var bool */
    public $flatten_redirects = true;

    /** @var array<array{loc:string,mod?:string,freq?:string,prio?:float}> */
    protected $urls = [];


    /**
     * Add a single URL in the sitemap
     *
     * @param string $loc The location.
     *        Can be an absolute or relative url
     * @param string|int|null $mod The last modified date.
     *        Should be anything parseable by strtotime()
     * @param string|null $freq The frequency of updates.
     *        Options include 'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'
     * @param float $prio Priority relative to other pages on the site.
     *        Range 0.0 (unimportant) to 1.0 (very important)
     * @return void
     */
    protected function url($loc, $mod = null, $freq = null, $prio = 0.5)
    {
        if (! preg_match('!https?://!', $loc)) {
            $loc = Sprout::absRoot() . ltrim($loc, '/');
        }

        if (
            $this->flatten_redirects
            and ($redirect = $this->findRedirect($loc))
        ) {
            $loc = $redirect;
        }

        $this->urls[] = [
            'loc' => $loc,
            'mod' => $mod,
            'freq' => $freq,
            'prio' => $prio,
        ];
    }


    /**
     * Find a redirect for a given URL.
     *
     * @param string $loc
     * @return null|string
     */
    public function findRedirect(string $loc): ?string
    {
        if ($url = $this->findStaticRedirect($loc)) {
            return $url;
        }

        if ($url = $this->findPageRedirect($loc)) {
            return $url;
        }

        return null;
    }


    /**
     * Find a static redirect for a given URL.
     *
     * @param string $loc
     * @return null|string
     */
    protected function findStaticRedirect(string $loc): ?string
    {
        $url = self::normalizeUrl($loc);

        if (!$url) {
            return null;
        }

        $redirects = static::loadRedirects($url['host'], SubsiteSelector::$subsite_id);

        $match = null;

        foreach ($redirects as $redirect) {
            if (
                ($redirect['path_exact'] === '' or $redirect['path_exact'] === $url['path'])
                and ($redirect['path_contains'] === '' or str_contains($url['path'], $redirect['path_contains']))
            ) {
                $match = $redirect;
                break;
            }
        }

        if (!$match) {
            return null;
        }

        try {
            $redirect = Lnk::url($match['destination']);

            if (!str_starts_with($redirect, 'http')) {

                // This is typically from SitemapGenPages, where it also doesn't
                // include the subsite prfix.
                if (!str_starts_with($redirect, SubsiteSelector::$url_prefix)) {
                    $redirect = SubsiteSelector::$url_prefix . $redirect;
                }

                $redirect = $url['scheme'] . '://' . $url['host'] . '/' . ltrim($redirect, '/ ');
            }

            if ($match['preserve_query']) {
                $params = [];

                if (!empty($url['query'])) {
                    parse_str($url['query'], $params);
                }

                if (strpos($redirect, '?') !== false) {
                    list($url, $query) = explode('?', $redirect, 2);
                    parse_str($query, $parts);
                    $params = array_merge($params, $parts);
                }

                $redirect .= '?' . http_build_query($params);
            }

            return $redirect;

        } catch (InvalidArgumentException $exception) {
            Kohana::logException($exception);
            return null;
        }
    }


    /**
     * Find a page redirect for a given URL.
     *
     * @param string $loc
     * @return null|string
     */
    protected function findPageRedirect(string $loc): ?string
    {
        $url = self::normalizeUrl($loc);

        if (!$url) {
            return null;
        }

        $root = Navigation::getRootNode();

        if (!$root) {
            return null;
        }

        $matcher = new TreenodePathMatcher($url['path']);
        $node = $root->findNode($matcher);

        if (!$node) {
            return null;
        }

        if ($node['type'] !== 'redirect') {
            return null;
        }

        if (empty($node['redirect'])) {
            return null;
        }

        try {
            $redirect = Lnk::url($node['redirect']);

            if (!str_starts_with($redirect, 'http')) {

                // This is typically from SitemapGenPages, where it also doesn't
                // include the subsite prfix.
                if (!str_starts_with($redirect, SubsiteSelector::$url_prefix)) {
                    $redirect = SubsiteSelector::$url_prefix . $redirect;
                }

                $redirect = $url['scheme'] . '://' . $url['host'] . '/' . ltrim($redirect, '/ ');
            }

            return $redirect;

        } catch (InvalidArgumentException $exception) {
            Kohana::logException($exception);
            return null;
        }
    }


    /**
     * Load redirects for a given host and subsite.
     *
     * @param string $host
     * @param int $subsite_id
     * @return array<array> db rows
     */
    protected static function loadRedirects(string $host, int $subsite_id): array
    {
        static $redirects = [];

        if (!isset($redirects[$host][$subsite_id])) {
            $q = "SELECT path_exact, path_contains, destination, preserve_query
                FROM ~redirects
                WHERE
                    active = 1
                    AND (subsite_id = 0 OR subsite_id = :subsite_id)
                    AND (domain_contains = '' OR :domain_std LIKE CONCAT('%', domain_contains, '%'))
                ORDER BY id
            ";

            $params = [
                'subsite_id' => SubsiteSelector::$subsite_id,
                'domain_std' => $host,
            ];

            $redirects[$host][$subsite_id] = Pdb::q($q, $params, 'arr');
        }

        return $redirects[$host][$subsite_id];
    }


    /**
     * Parse a URL/path and normalise it.
     *
     * This ensures there is always a scheme, host and path.
     *
     * The path is stripped of the subsite prefix if present.
     *
     * @param string $loc
     * @return null|array{scheme:string,host:string,path:string,query?:string}
     */
    public static function normalizeUrl(string $loc): ?array
    {
        $url = parse_url($loc);

        if (!$url or empty($url['path'])) {
            return null;
        }

        $url['scheme'] ??= Request::protocol();
        $url['host'] ??= $_SERVER['HTTP_HOST'];
        $url['path'] = ltrim($url['path'], '/ ');

        if (str_starts_with($url['path'], SubsiteSelector::$url_prefix)) {
            $url['path'] = substr($url['path'], strlen(SubsiteSelector::$url_prefix));
        }

        return $url;
    }


    /**
     * Build the sitemap URLs.
     *
     * This calls generate() and returns the URL objects.
     *
     * @return array<array{loc:string,mod?:string,freq?:string,prio?:float}>
     */
    public function build(): array
    {
        $this->urls = [];
        $this->generate();
        return $this->urls;
    }


    /**
     * Render a single URL object as XML.
     *
     * @param array{loc:string,mod?:string,freq?:string,prio?:float} $url
     * @return string
     */
    public static function render(array $url): string
    {
        $xml = '';
        $xml .= '<url>';
        $xml .= '<loc>' . Enc::xml($url['loc']) . '</loc>';

            if (!empty($url['mod'])) {
                $mod = date('c', strtotime($url['mod']));
                $xml .= '<lastmod>' . Enc::xml($mod) . '</lastmod>';
            }

            if (!empty($url['freq'])) {
                $xml .= '<changefreq>' . Enc::xml($url['freq']) . '</changefreq>';
            }

            if (!empty($url['prio']) and $url['prio'] > 0.0) {
                $xml .= '<priority>' . number_format($url['prio'], 2) . '</priority>';
            }

        $xml .= '</url>';
        $xml .= PHP_EOL;

        return $xml;
    }


    /**
     * Generate sitemap entries by calling the {@see SitemapGen::url} method
     *
     * @return void
     */
    public abstract function generate();

}