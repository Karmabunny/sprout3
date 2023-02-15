<?php
/**
 * Configuration for Sprout
 * @package Kohana
 */

use Sprout\Helpers\SearchHandler;
use Sprout\Helpers\WidgetArea;

// Super users are operators with access to all the developer tools
// Each entry is a username => details mapping, created by the tool at http://servername/dbtools/genPassHash
// An example is provided
$config['super_users'] = [
    // 'test' => ['uid' => 1, 'hash' => '$2a$12$J/qUFM9j6KRb7lXvXtFhH.J8RhJYdP/3oeNetR3taDmNhTYMCJfkG', 'salt' => '109Du9gMwI'],
];

// jQuery version(s) to use
$config['jquery_front'] = '2.1.4';
$config['jqueryui_front'] = '1.11.4';
$config['jquery_admin'] = '2.1.4';
$config['jqueryui_admin'] = '1.11.4';

/**
 * Rich text field type
 * 'TinyMCE4', 'Textarea'
 */
$config['rich_text_type'] = 'TinyMCE4';


/**
 * Who to send emails to if any cronjobs fail
 **/
$config['cron_email'] = 'root@localhost';

/**
 * Email address for the webmaster/developer
 *
 * This is shown in places like the coming_soon and maintenance pages
 */
$config['info_email'] = 'info@example.com';

/**
 * SMTP settings for email sending.
 * If you need more options, edit sprout/libraries/Email.php
 * The email library being used (PHPMailer) also supports other transports
 * but support for these will need to be hacked in manually.
 *
 * smtp_server     Server name or IP address
 * smtp_port       Server port
 * smtp_secure     Boolean to enable TLS
 * smtp_autotls    Boolean to automatically enable TLS if server supports it,
 *                 even if smtp_secure is off. This needs to be switched off
 *                 if the connection fails testing, e.g. due to a self-signed
 *                 cert or hostname mismatch.
 * smtp_auth       Boolean to enable authentication
 * smtp_username   Authentication username
 * smtp_password   Authentication password
 */
$config['smtp_server'] = 'localhost';
$config['smtp_port'] = 25;
$config['smtp_secure'] = false;
$config['smtp_autotls'] = true;
$config['smtp_auth'] = false;
$config['smtp_username'] = '';
$config['smtp_password'] = '';


/**
 * Restrict email sending to a given set of domain names.
 * If set to NULL then there aren't any restrictions
 */
if (IN_PRODUCTION) {
    $config['email_allowed_domains'] = null;
} else {
    // Only permit email to be sent to addresses at devel.example.com
    $config['email_allowed_domains'] = ['devel.example.com'];
}


/**
* Proxy server to use
**/
$config['httpreq_proxy_host'] = '';
$config['httpreq_proxy_port'] = '';

/**
* Proxy auth, formatted as user:password
**/
$config['httpreq_proxy_auth'] = '';

/**
* Proxy type, either 'html' or 'socks5'
**/
$config['httpreq_proxy_type'] = 'html';


// The default widgets are listed in sprout/sprout_load.php
// You can still add widgets in here if you really want though

/**
 * Define the widget area: embedded
 */
$config['widget_areas'][1] = $area = new WidgetArea('embedded', 1);

$area->setNiceName('Embedded in content');
$area->setOrientation(WidgetArea::ORIENTATION_WIDE);
$area->setEmbed(true);


/**
 * Define the widget area: sidebar
 */
$config['widget_areas'][2] = $area = new WidgetArea('sidebar', 2);

$area->setNiceName('Sidebar');
$area->setOrientation(WidgetArea::ORIENTATION_TALL);
$area->addDefault('RelatedLinks', array());


/**
 * Define the widget area: email
 */
$config['widget_areas'][4] = $area = new WidgetArea('email', 4);

$area->setNiceName('Email');
$area->setOrientation(WidgetArea::ORIENTATION_EMAIL);
$area->setEmbed(true);


/**
 * List of controllers (shorthand notation) to hide from admin tiles
 *
 * For example add 'recurring_payment' to this list if you're not using
 * the recurring payment feature of the payments module
 *
 * Unlike operator permissions, this *does not* protect against direct-url
 * access to these controllers; it only affects the display of the links.
 */
$config['admin_tile_hidden'] = [];


/**
 * The URL which the user should be redirected to when logging in
 */
$config['admin_intro'] = 'admin/dashboard';


/**
 * The domain to require for the admin control panel.
 * If the admin is accessed from a different domain, the user is redirected
 */
//$config['admin_domain'] = 'www.example.net';


/**
 * Restrict admin access to given IPs or CIDR ranges
 */
//$config['admin_ips'] = array('192.168.10.0/24');


/**
* How strict the censorship is. 1 = Fairly lax, 3 = Fairly strict.
* This roughly translate into R18, M15, PG.
* Level 0 disables censorship.
**/
$config['censor_level'] = 3;


/**
* Update notification:
* Updates to pages will send an email to all operators, detailing the change made
**/
$config['update_notify'] = false;


/**
* Should the admin do record locking
* Turn this off if it's constantly giving trouble
**/
$config['admin_locks'] = true;


/**
* Allows per-page skin tweaking with custom CSS
**/
$config['tweak_skin'] = false;


/**
* Should we track stats for page traffic?
* This is normally of for performance, but is
* useful for a "popular pages" feature
**/
$config['page_stats'] = false;


/**
 * Default class to handle CAPTCHA fields:
 * 'DefaultCaptcha','Recaptcha','Recaptcha3'
 */
$config['captcha'] = 'Recaptcha';


/**
 * ReCAPTCHA keys, see https://www.google.com/recaptcha
 */
if (!IN_PRODUCTION) {
    // Keys for development usage
    $config['recaptcha_public_key'] = 'please_generate_me_devel';
    $config['recaptcha_private_key'] = 'please_generate_me_devel';
} else {
    // Add live keys here
    $config['recaptcha_public_key'] = 'please_generate_me';
    $config['recaptcha_private_key'] = 'please_generate_me';
}


/**
 * Google Maps API keys
 * An empty string will cause no key to be included in the JS call.
 * The string "please_generate_me" will throw an exception
 */
if (!IN_PRODUCTION) {
    $config['google_maps_key'] = '';
    $config['google_maps_secret'] = '';
} else {
    $config['google_maps_key'] = 'please_generate_me';
    $config['google_maps_secret'] = 'please_generate_me';
}


/**
 * Google Places API key
 * Used for autocomplete address fields
 */
if (!IN_PRODUCTION) {
    $config['google_places_key'] = '';
} else {
    $config['google_places_key'] = 'please_generate_me';
}


/**
 * Google YouTube API key
 * An empty string will cause no key to be included in the JS call.
 * The string "please_generate_me" will throw an exception
 */
if (!IN_PRODUCTION) {
    $config['google_youtube_api'] = '';
} else {
    $config['google_youtube_api'] = 'please_generate_me';
}


/**
 * Default address to send stale page notifications to
 */
if (!IN_PRODUCTION) {
    $config['stale_page_email'] = 'stale@example.com';
} else {
    $config['stale_page_email'] = 'stale@example.com';
}

/**
 * Default max age for new 'standard' page revisions, before they're counted as stale
 */
$config['stale_page_age'] = 0;

/**
 * Number of days between resend of stale page info
 */
$config['stale_page_resend_after'] = 7;


/**
* Site unavailability.
* Makes it so that skin views will not be loaded but the specified message will be loaded instead.
* The admin will still work while the site is unavailable.
**/
$config['unavailable'] = '';
//$config['unavailable'] = 'coming_soon';
//$config['unavailable'] = 'maintenance';


/**
 * Default quality for JPEGs that travel through the resize handler
 */
$config['jpeg_quality'] = 80;

/**
 * Use interlacing on JPEGs (aka progressive JPEGs)
 * This usually makes them smaller and on most browsers leads to the nice blur-in effect.
 */
$config['jpeg_progressive'] = true;

/**
 * Per-hour rate limiting for admin authentication
 */
$config['auth_rate_limit']['ip'] = 10;
$config['auth_rate_limit']['username'] = 10;

/**
 * Whether the server is behind a load balancer
 *
 * This is necessary to determine whether to trust X-Forwarded headers.
 */
$config['load_balanced'] = false;

/**
 * Complexity requirements for password validation using the {@see Validity::password} method
 */
$config['password_length'] = 8;
$config['password_classes'] = 2;
$config['password_bad_list'] = true;
