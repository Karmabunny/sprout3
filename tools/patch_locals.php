<?php
error_reporting(E_ALL ^ E_NOTICE);
array_shift($argv);

$dir = realpath(__DIR__ . '/../');
chdir($dir);

$vendor = realpath($dir . '/vendor/');
if (!$vendor) die("Missing /vendor/\n");

$composer = json_decode(file_get_contents($dir . '/composer.json'), true);
$locals = $composer['extra']['locals'] ?: [];

foreach ($locals as $package => $path) {
    if (!file_exists($path)) {
        echo "Target does not exist: {$path}\n";
        continue;
    }

    $link = $vendor . '/' . trim($package, '/');
    $target = '../../' . trim($path, '/');

    if (is_link($link)) {
        exec("rm -f {$link}");
    }
    else if (file_exists($link)) {
        exec("rm -rf {$link}");
    }

    symlink($target, $link);
    echo "link: {$path} -> {$link}\n";
}

echo "\n";
echo "Done!\n";
