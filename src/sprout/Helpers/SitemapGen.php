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

        if ($redirect = $this->findRedirect($loc)) {
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
        if (!$this->flatten_redirects) {
            return null;
        }

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
        $url = parse_url($loc);

        if (!$url or empty($url['path'])) {
            return null;
        }

        $url['scheme'] ??= Request::protocol();
        $url['host'] ??= $_SERVER['HTTP_HOST'];
        $url['path'] = ltrim($url['path'], '/ ');

        $params = [
            'url_std' => $url['path'],
            'url_like' => Pdb::likeEscape($url['path']),
            'subsite_id' => SubsiteSelector::$subsite_id,
            'domain_std' => $url['host'],
        ];

        $q = "SELECT destination
            FROM ~redirects
            WHERE
                active = 1
                AND (path_exact = '' OR path_exact LIKE :url_like)
                AND (path_contains = '' OR :url_std LIKE CONCAT('%', path_contains, '%'))
                AND (subsite_id = 0 OR subsite_id = :subsite_id)
                AND (domain_contains = '' OR :domain_std LIKE CONCAT('%', domain_contains, '%'))
            ORDER BY id
            LIMIT 1";

        $row = Pdb::q($q, $params, 'row?');

        if (!$row) {
            return null;
        }

        try {
            $redirect = Lnk::url($row['destination']);

            if (!str_starts_with($redirect, 'http')) {
                $redirect = $url['scheme'] . '://' . $url['host'] . ltrim($redirect, '/ ');
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
        $url = parse_url($loc);

        if (!$url) {
            return null;
        }

        $url['scheme'] ??= Request::protocol();
        $url['host'] ??= $_SERVER['HTTP_HOST'];
        $url['path'] = ltrim($url['path'], '/ ');

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
                $redirect = $url['scheme'] . '://' . $url['host'] . ltrim($redirect, '/ ');
            }

            return $redirect;

        } catch (InvalidArgumentException $exception) {
            Kohana::logException($exception);
            return null;
        }
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