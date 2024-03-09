<?php

use Composer\InstalledVersions;

if (class_exists(InstalledVersions::class)) {
        $version = InstalledVersions::getPrettyVersion('sproutcms/cms');
        $hash = InstalledVersions::getReference('sproutcms/cms');
} else {
        $version = 'dev';
        $hash = 'unknown';
}

$config['version_brand'] = "4.0";
$config['version'] = sprintf('%s - #%.7s', $version, $hash);
