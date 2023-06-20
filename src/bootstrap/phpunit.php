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

use Sprout\Helpers\SubsiteSelector;
use Sprout\Helpers\Pdb;

if (defined('IN_PRODUCTION') and constant('IN_PRODUCTION')) {
    die('Cannot run tests in production');
}

// Fake server vars when run from CLI
if (empty($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

// Load core files
require APPPATH . 'core/utf8.php';
require APPPATH . 'core/Event.php';
require APPPATH . 'core/Kohana.php';

// Prepare the environment
Kohana::setup();
SubsiteSelector::selectSubsite();

// Allow both old and new versions of phpunit to work
if (!class_exists('PHPUnit_Framework_TestCase')) {
    /** @deprecated For crying out loud stop using this! */
    class PHPUnit_Framework_TestCase extends \PHPUnit\Framework\TestCase {}
}

// Increase wait timeout, which is very low on Travis CI
Pdb::query("SET wait_timeout=3600", [], 'null');
