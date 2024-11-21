<?php

use Nette\Neon\Neon;

require dirname(__DIR__) . '/vendor/autoload.php';

$path = $argv[1] ?? null;

if (empty($path)) {
    echo "Usage: php tools/migrate_forms.php <path/to/json>\n";
    exit(1);
}

if (!is_file($path)) {
    echo "Error: path not found.\n";
    echo " > {$path}\n";
    exit(1);
}

$json = file_get_contents($path);
$json = json_decode($json, true);

if (!is_array($json)) {
    echo "Error: invalid json file.\n";
    echo " > {$path}\n";
    exit(1);
}

$neon = Neon::encode($json, true, "    ");
$neon = preg_replace("/^(\s*)    -\n\s+(html|field|group)/m", '$1  - $2', $neon);

Neon::decode($neon);

$target = preg_replace('/\.json$/', '.neon', $path);
file_put_contents($target, $neon);

echo "OK: Form migrated.\n";
echo " > {$target}\n";
