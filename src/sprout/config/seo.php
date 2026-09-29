<?php

$config['sitemaps'] = [
    /**
     * Whether to flatten redirects in the sitemap.
     *
     * By default sitemap generators will find redirects from the page tree
     * and redirects module. However implementations are free to extend or
     * override this behaviour.
     */
    'flatten_redirects' => true,

    /**
     * Whether to deduplicate URLs in the sitemap.
     *
     * If true, the same URL will only appear once in the sitemap.
     */
    'deduplicate_urls' => true,
];
