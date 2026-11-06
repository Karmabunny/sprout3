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

use karmabunny\pdb\Exceptions\PdbException;
use Sprout\Helpers\CoreAdminAuth;
use Sprout\Helpers\DatabaseSync;
use Sprout\Helpers\Modules;
use Sprout\Helpers\SubsiteSelector;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Register;

if (defined('IN_PRODUCTION') and constant('IN_PRODUCTION')) {
    die('Cannot run tests in production');
}

// Fake server vars when run from CLI
if (empty($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

Register::services(CoreAdminAuth::class);

// Initialise Sprout modules, if required
Modules::loadModules('sprout');

SubsiteSelector::selectSubsite();

// Allow both old and new versions of phpunit to work
if (!class_exists('PHPUnit_Framework_TestCase')) {
    /** @deprecated For crying out loud stop using this! */
    class PHPUnit_Framework_TestCase extends \PHPUnit\Framework\TestCase {}
}

try {
    // Increase wait timeout, which is very low on Travis CI
    if (getenv('HAS_JOSH_K_SEAL_OF_APPROVAL')) {
        fwrite(\STDERR, "Setting wait timeout\n");
        Pdb::query("SET wait_timeout=3600", [], 'null');
    }

    // Copy over the db struct so things are in sync
    if (getenv('RUNNER_DEBUG')) {
        fwrite(\STDERR, "Loading db struct\n");
    }

    // Sync the database structure.
    $sync = new DatabaseSync(true);
    $sync->loadXml(APPPATH . 'db_struct.xml');
    $log = $sync->updateDatabase();

    if (getenv('RUNNER_DEBUG')) {
        $log = trim(strip_tags($log)) ?: 'no changes';
        fwrite(\STDERR, "DB sync: {$log}\n");
        fwrite(\STDERR, "Ready\n");
    }

} catch (PdbException $ex) {
    if (getenv('RUNNER_DEBUG')) {
        fwrite(\STDERR, "Error: " . $ex->getMessage() . "\n");
    }

    // Ignore.
}
