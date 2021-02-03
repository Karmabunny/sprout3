<?php
require __DIR__ . '/../vendor/autoload.php';

use Sprout\Helpers\DatabaseSync;

$sync = new DatabaseSync(true);
$sync->loadStandardXmlFiles();
$sync->sanityCheck();

if ($sync->hasLoadErrors()) {
    $out = strip_tags($sync->getLoadErrorsHtml());
    echo 'Sync failed sanity check: ' . $out, PHP_EOL;
    die();
}

try {
    $log = $sync->updateDatabase();
    echo strip_tags($log), PHP_EOL;
} catch (Exception $ex) {
    echo $ex->getMessage(), PHP_EOL;
    echo 'Please configure Database.', PHP_EOL;
    exit(1);
}
