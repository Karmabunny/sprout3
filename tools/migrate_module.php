<?php

$name = $argv[1] ?? null;

if (empty($name)) {
    echo "Usage: php tools/migrate_modules.php <module-name>\n";
    exit(1);
}

$path = 'src/modules/' . $name;

if (!is_dir($path)) {
    echo "Error: Module path not found.\n";
    echo " > {$path}\n";
    exit(1);
}

$path .= '/' . $name . 'Module.php';

if (is_file($path)) {
    echo "Error: Module already migrated.\n";
    echo " > {$path}\n";
    exit(1);
}

file_put_contents($path, <<<PHP
<?php

namespace SproutModules\Karmabunny\\{$name};

use Sprout\Helpers\Module;
use Sprout\Helpers\ModuleSiteTrait;

/**
 * {$name} module.
 */
class {$name}Module extends Module
{
    use ModuleSiteTrait;
}
PHP);

echo "OK: Module migrated.\n";
echo " > {$path}\n";
