<?php
/**
 * For local development without an Apache or FPM server.
 *
 * Fire up your local PHP server with:
 * > php -S localhost:8080 -t src src/index.php
 *
 * DO NOT USE THIS IN PRODUCTION.
 */

if (PHP_SAPI !== 'cli-server') return;

$url = parse_url($_SERVER['REQUEST_URI']);
$_SERVER['QUERY_STRING'] = $url['query'] ?? '';
$path = trim($url['path'], '/');

// Serve it statically.
if (is_file(DOCROOT . $path)) {
    return false;
}

// Rewrites for media + skin files.
// These are identical to those in .htaccess and nginx.conf.
$count = 0;

if (!$count) {
    $target = preg_replace('!^media-[0-9]+/core/([a-z]+)/(.+)!', 'media/$1/$2', $path, -1, $count);
}

if (!$count) {
    $target = preg_replace('!^media-[0-9]+/sprout/([a-z]+)/(.+)!', 'sprout/media/$1/$2', $path, -1, $count);
}

if (!$count) {
    $target = preg_replace('!^media-[0-9]+/([_a-zA-Z0-9]+)/([a-z]+)/(.+)!', 'modules/$1/media/$2/$3', $path, -1, $count);
}

if (!$count) {
    $target = preg_replace('!^skin-[0-9]+/([_a-z0-9\-]+)/([a-z]+)/(.+)!', 'skin/$1/$2/$3', $path, -1, $count);
}

$target = DOCROOT . $target;

// Matched a rewrite.
if ($count and file_exists($target)) {
    $type = mime_content_type($target);

    $matches = [];

    // Built-in mime doesn't do js/css consistently.
    if (preg_match('!\.(css)|(js)$!', $target, $matches)) {
        if ($matches[1]) $type = 'text/css';
        if ($matches[2]) $type = 'application/javascript';
    }

    // Fallback.
    if (!$type) {
        $type = 'text/plain';
    }

    header('Content-Type: ' . $type);
    readfile($target);

    return true;
}

// Otherwise let sprout do it's thing.
return null;
