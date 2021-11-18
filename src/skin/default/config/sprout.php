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
 * The email address that emails are sent from.
 * Default is no-reply @ the server name with any www part removed.
 */
$config['site_email'] = 'no-reply@' . str_replace('www.', '', $_SERVER['SERVER_NAME']);


/**
 * The site ABN number. Exclude the leading ABN text
 * Eg. "12 345 678 910", not ABN 12 345 678 910.  
 */
$config['site_abn'] = '';

/**
 * Twitter account name, used in social meta data.
 * Don't include the leading @
 * Example: 'KarmabunnyWeb'
 */
$config['site_twitter'] = '';


/**
 * Image gallery widget
 */
$config['image_gallery'] = array(
    'thumb_size_2' => 'c600x600',
    'thumb_size_3' => 'c400x400',
    'thumb_size_4' => 'c300x300',
    'thumb_size_5' => 'c220x220',
    'full_size' => 'm800x600',
    'slider_size' => 'c800x450'
);


/**
 * Social media page links
 * Example format:
 *   'facebook' => 'https://www.facebook.com/KarmabunnyWebDesign'
 * Example usage:
 *   <?php echo SocialNetworking::pageLink('facebook'); ?>Facebook</a>
 */
$config['social_media'] = array(
    'facebook' => '',
    'twitter' => '',
    'youtube' => '',
    'instagram' => '',
    'pinterest' => '',
);


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
 * Whether the navigation should show a 'home' link at the start or not
 */
$config['nav_home'] = true;


/**
 * The maximum number of top-level navigation items to show
 */
$config['nav_limit'] = 7;


/**
 * Specify the number of nav groups for each top-level page id
 * The actual group *names* are managed in the admin
 * Syntax is page_id => num_groups / columns
 * Example:
 *
 *    $config['nav_groups'] = array(
 *        '2' => 3,
 *        '3' => 2,
 * );
 */
$config['nav_groups'] = null;


/**
 * Which of the "extra" fields to show when managing menu groups
 */
$config['nav_extras'] = array(
    'text' => false,
    'image' => true,
);


/**
* Override the dropdown logic for a given top-level page in the mega menu
* Format:
*    Key   Top-level page id
*    Val   Callable method (cannot be inline)
* Example:
*    2 => 'specials::menu_dropdown',
* The callback method should have the same signature as {@see navigation_menu::sub_menu}
**/
$config['nav_custom_dropdown'] = array(
);


/**
 * Allows the top-level nav to be re-ordered.
 * Remote logins can always do re-ordering.
 */
$config['nav_reorder'] = false;


/**
 * The size, in pixels, to show the richtext area in the admin.
 * Only applies to pages.
 */
$config['admin_richtext_width'] = 700;
$config['admin_richtext_height'] = 500;


/**
 * The maximum menu depth of the PMM2 menu
 * 1 - no slideout
 * 2 - single level of slideout
 * 3 - two levels of slideout
 * etc
 **/
$config['pmm2_depth'] = 2;


/**
 * Sets the heading to be above related links.
 * This heading can contain HTML - typically H2 tags, but other HTML is legal too.
 * Anywhere where the string SECTION is found, it will be replaced with the top-parent name.
 */
$config['related_heading'] = '<h3 class="widget-title">SECTION</h3>';


/**
* Widget heading/title
* This heading can contain HTML - typically H2 tags, but other HTML is legal too.
* Anywhere where the string TITLE is found, it will be replaced with the user given widget heading
*/
$config['widget_title'] = '<h2 class="widget-title">TITLE</h2>';


/**
 * Whether the top-parent should be shown in the related links
 * TRUE for page name, A string for something else, or FALSE to not show at all
 */
$config['related_top'] = true;


/**
 * Max depth of related links
 */
$config['related_max_depth'] = 2;


/**
 * Front-end site search - the number of results to show per page
 **/
$config['search_per_page'] = 10;


/**
 * The profile id of this site in Google Analytics
 * Example 'UA-6624788-6'
 **/
$config['google_analytics_id'] = '';


/**
 * Default locale for currency, address display, etc
 */
$config['locale'] = 'AUS';


/**
 * Any libaries which should be ignored by Needs::fileGroup
 * Only actually useful if you are using mediamush
 */
$config['dont_need'] = array();


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


/**
 * Widget wrapper templates
 * Eg: 'skin/partials/demo_wrap' => 'Demo wrap'
 * Your partial must contain HTML with the merge tag: {{widget}}
 *    to be replaced with the actual widget's HTML
 */
$config['widget_templates'] = array(
);
