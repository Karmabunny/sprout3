<?php
/**
 * For local development without an Apache or FPM server.
 *
 * Fire up your local PHP server with:
 * > php -S localhost:8080 -t src src/index.php
 *
 * DO NOT USE THIS IN PRODUCTION.
 */

use Sprout\Helpers\CliServer;

if (PHP_SAPI !== 'cli-server') return;

if (CliServer::serve()) {
    require __DIR__ . '/web.php';
}

return false;
