<?php
/**
 * Configuration for Sprout
 * @package Kohana
 */

/**
 * The site title
 */
$config['site_title'] = 'Sprout3 test';

/**
 * The format string which builds the browser title.
 * Coders may recognise the use of sprintf style foratting, and they would be correct.
 *
 * Replacements:
 *   %1$s - The page title
 *   %2$s - The site title
 *
 * The default results in something like "About us | Karmabunny"
 */
$config['browser_title'] = '%1$s | %2$s';



/**
 * Default locale for currency, address display, etc
 */
$config['locale'] = 'AUS';


/**
 * Available skin views for pages.
 */
$config['skin_views'] = array(
    'skin/layouts/inner' => 'Standard',
    'skin/layouts/wide' => 'Full-width',
);


/**
 * View type - php or twig.
 *
 * This affects:
 * - page controller
 * - home controller
 * - any modules that render skin/ views with View::create()
 */
$config['skin_views_type'] = 'twig';

