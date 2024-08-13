<?php
/*
 * Copyright (C) 2024 Karmabunny Pty Ltd.
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

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Tools for crawling/scraping web pages
 *
 * This is really just to get you started.
 *
 * To work with the crawler, you can reference the following tools:
 *
 * - https://symfony.com/doc/current/components/dom_crawler.html
 * - https://symfony.com/doc/current/components/browser_kit.html
 * - https://symfony.com/doc/current/components/css_selector.html
 * - https://symfony.com/doc/current/http_client.html
 *
 * Extracting data from specific elements:
 *
 * $crawler->filter('ul > li')->each(function ($node) {
 *     print $node->text()."\n";
 * });
 *
 * Click a link
 *
 * $link = $crawler->selectLink('Make a booking')->link();
 * $crawler = $client->click($link);
 *
 * Submit a form:
 *
 * $crawler = WebCrawler::crawlUrl('https://github.com/');
 * $crawler = $client->click($crawler->selectLink('Sign in')->link());
 * $form = $crawler->selectButton('Sign in')->form();
 * $crawler = $client->submit($form, ['email' => 'test@karmabunny.com.au', 'password' => 'xxxxxx']);
 * $crawler->filter('.flash-error')->each(function ($node) {
 *     print $node->text()."\n";
 * });
 *
 * @package Sprout\Helpers
 */
class WebCrawler
{

    /**
     * Crawl a URL, return the crawler object. Use $crawler->html() to get the raw HTML.
     *
     * @param string $url The URL to crawl
     * @param string $method The HTTP method to use (GET, POST, etc)
     * @param HttpClientInterface|null $client An existing client to use, or null to create a new one
     */
    public static function crawlUrl(string $url, $method = 'GET', $client = null): Crawler
    {
        $browser = new HttpBrowser($client);
        $crawler = $browser->request($method, $url);

        return $crawler;
    }


    /**
     * Remove any instances of specified tags from the crawler result.
     *
     * @param Crawler $crawler The crawler object
     * @param array $tags An array of tags to remove (e.g. head, script, etc)
     * @return void as Crawler is passed by reference
     */
    public static function stripTags(Crawler $crawler, array $tags): void
    {
        foreach ($tags as $tag) {
            $crawler->filter($tag)->each(function (Crawler $node) {
                $node->getNode(0)->parentNode->removeChild($node->getNode(0));
            });
        }
    }

}
