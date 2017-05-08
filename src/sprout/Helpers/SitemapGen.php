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

use Sprout\Helpers\Enc;
use Sprout\Helpers\Sprout;


abstract class SitemapGen
{

    /**
     * Echo XML for a single URL in the sitemap
     *
     * @param string $loc The location.
     *        Can be an absolute or relative url
     * @param date $mod The last modified date.
     *        Should be anything parseable by strtotime()
     * @param string $freq The frequency of updates.
     *        Options include 'always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'
     * @param float $prio Priority relative to other pages on the site.
     *        Range 0.0 (unimportant) to 1.0 (very important)
     * @return void Outputs XML directly
     */
    protected function url($loc, $mod = null, $freq = null, $prio = 0.5)
    {
        if (! preg_match('!https?://!', $loc)) {
            $loc = Sprout::absRoot() . ltrim($loc, '/');
        }

        echo '<url>';
        echo '<loc>', Enc::xml($loc), '</loc>';
        if ($mod) {
            $mod = date('c', strtotime($mod));
            echo '<lastmod>', Enc::xml($mod), '</lastmod>';
        }
        if ($freq) {
            echo '<changefreq>', Enc::xml($freq), '</changefreq>';
        }
        if ($prio and $prio > 0.0) {
            echo '<priority>', number_format($prio, 2), '</priority>';
        }
        echo '</url>';
        echo PHP_EOL;
    }


    /**
     * Generate sitemap entries by calling the {@see SitemapGen::url} method
     *
     * @return void Outputs XML directly
     */
    public abstract function generate();

}