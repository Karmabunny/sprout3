<?php
/**
 * For local development without an Apache or FPM server.
 *
 * Fire up your local PHP server with:
 * > php -S localhost:8080 -t web web/index.php
 *
 * OR, if using the 'sproutcms/site' template package:
 * > composer serve
 *
 * DO NOT USE THIS IN PRODUCTION.
 */

use Sprout\Helpers\CliServer;

if (CliServer::serve()) {
    return true;
}

return false;
