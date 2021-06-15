<?php
// This bootstrap only concerns CLI environments.
// The sprout index.php does all this for web contexts.
if (PHP_SAPI !== 'cli') return;

// We can also rely on the autoloader being initialised much later.
// This tells us that we're being included when all of this already exists (sprout workers, crons).
if (!(
    defined('KOHANA') or
    defined('PHPUNIT') or
    defined('PHPUNIT_COMPOSER_INSTALL')
)):

error_reporting(-1);
date_default_timezone_set('Australia/Adelaide');

define('IN_PRODUCTION', false);
define('DOCROOT', realpath('./src') . '/');
define('KOHANA',  realpath('./src'));
define('APPPATH', realpath('./src/sprout') . '/');

function _privDetermineWebDirectory() {
    if (!empty($_SERVER['PHP_S_WEBDIR'])) return $_SERVER['PHP_S_WEBDIR'];
    if (! $_SERVER['DOCUMENT_ROOT']) return false;

    $pos = strpos($_SERVER['SCRIPT_FILENAME'], $_SERVER['DOCUMENT_ROOT']);
    if ($pos === 0) {
        $doc_path = substr($_SERVER['SCRIPT_FILENAME'], strlen($_SERVER['DOCUMENT_ROOT']));
        $doc_path = dirname($doc_path);

        if (substr($doc_path, 0, 1) != '/') $doc_path = '/' . $doc_path;
        if (substr($doc_path, -1, 1) != '/') $doc_path .= '/';

        if ($_SERVER['REQUEST_URI'] and preg_match('!^/v[1-9]/!', $doc_path) and !preg_match('!^/v[1-9]/!', $_SERVER['REQUEST_URI'])) {
            $doc_path = preg_replace('!^/v[1-9]/!', '/', $doc_path);
        }

        return $doc_path;
    }

    return false;
}

// Fake server vars when run from CLI
if (empty($_SERVER['REMOTE_ADDR'])) $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

// Load core files
require_once APPPATH . 'core/utf8.php';
require_once APPPATH . 'core/Event.php';
require_once APPPATH . 'core/Kohana.php';

endif;