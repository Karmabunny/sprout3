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

namespace Sprout\Controllers;

use Sprout\Helpers\Register;
use Sprout\Helpers\Sprout;


/**
 * Handler for SEO functions, including generation of robots.txt and XML sitemap
 */
class SeoController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * Output a dynamic robots.txt file which points to the sitemap; for use on production sites
     * @return void Outputs plain text after setting the appropriate content-type header
     */
    public function robots()
    {
        header('Content-type: text/plain; charset=UTF-8');

        // Load additional robots.txt rules, if found
        if (file_exists(DOCROOT . 'config/additional_robots.txt')) {
            echo trim(file_get_contents(DOCROOT . 'config/additional_robots.txt')), PHP_EOL, PHP_EOL;
        }

        // Include sidemap rule
        echo '# Dynamic sitemap', PHP_EOL;
        echo 'Sitemap: ', Sprout::absRoot(), 'seo/xmlSitemap', PHP_EOL;
    }


    /**
     * Robots.txt which denies all; for use on dev/QA sites
     * @return void Outputs plain text after setting the appropriate content-type header
     */
    public function robotsDeny() {
        header('Content-type: text/plain; charset=UTF-8');

        echo 'User-agent: *', PHP_EOL;
        echo 'Disallow: /', PHP_EOL;
    }


    /**
     * Output a dynamic XML sitemap
     * @return void Outputs XML directly
     */
    public function xmlSitemap()
    {
        $gens = Register::getSitemapGens();

        $this->header();
        foreach ($gens as $class_name) {
            $inst = Sprout::instance($class_name, ['Sprout\\Helpers\\SitemapGen']);
            $inst->generate();
        }
        $this->footer();
    }


    /**
     * Echo the XML header for the sitemap
     * @return void Outputs XML after setting the appropriate content-type header
     */
    private function header()
    {
        header('Content-type: text/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="utf-8"?>';
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ' .
            'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ' .
            'xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">';
        echo PHP_EOL;
    }


    /**
     * Echo the XML footer for the sitemap
     * @return void Outputs XML directly
     */
    private function footer()
    {
        echo '</urlset>';
        echo PHP_EOL;
    }

}
