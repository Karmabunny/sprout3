<?php
$license_text = trim(file_get_contents(__DIR__ . '/license_block.txt'));
$license_text = str_replace(PHP_EOL, PHP_EOL . ' * ', $license_text);
$license_text = str_replace(' * ' . PHP_EOL, ' *' . PHP_EOL, $license_text);

// If this file exists, it can be used to *remove* license text
if (file_exists(__DIR__ . '/license_block_old.txt')) {
    $old_license_text = trim(file_get_contents(__DIR__ . '/license_block_old.txt'));
    $old_license_text = str_replace(PHP_EOL, PHP_EOL . ' * ', $old_license_text);
    $old_license_text = str_replace(' * ' . PHP_EOL, ' *' . PHP_EOL, $old_license_text);
} else {
    $old_license_text = null;
}

if ($argc == 1) {
    // No args? Process all files in src/
    $srcdir = __DIR__ . '/../../src';
    $directory = new RecursiveDirectoryIterator($srcdir);
    $iterator = new RecursiveIteratorIterator($directory);
    $files = new RegexIterator($iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

    $ignore_prefix = array(
        $srcdir . '/skin',
        $srcdir . '/sprout/Helpers/FirePHP.php',
        $srcdir . '/sprout/Helpers/FPDF.php',
        $srcdir . '/sprout/Helpers/mdetect',
        $srcdir . '/sprout/Helpers/php_mailer',
        $srcdir . '/sprout/php_webdriver',
        $srcdir . '/sprout/version.php',
        $srcdir . '/sprout/Helpers/fpdf_fonts',
        $srcdir . '/sprout/Views',
        $srcdir . '/sprout/Helpers/fpdi',
        $srcdir . '/sprout/Helpers/Fpdi.php',
    );
    $ignore_contains = array(
        'views/',
        'config/',
    );

    foreach ($files as $filename) {
        foreach ($ignore_prefix as $prefix) {
            if (strpos($filename[0], $prefix) === 0) continue 2;
        }
        foreach ($ignore_contains as $contains) {
            if (strpos($filename[0], $contains) !== false) continue 2;
        }
        process_file($license_text, $old_license_text, $filename[0]);
    }

} else {
    unset($argv[0]);
    foreach ($argv as $filename) {
        process_file($license_text, $old_license_text, $filename);
    }
}


/**
* Modify a file to include a license block
* If a file already has the license block, it will be ignored.
*
* @param string $license_text The license text
* @param string $filename
**/
function process_file($license_text, $old_license_text, $filename) {
    $contents = file_get_contents($filename);

    // If there is an old license, remove it
    if ($old_license_text) {
        $regex = '/' . preg_quote($old_license_text, '/') . '/';
        $regex = str_replace('YEAR', '[-, 0-9]{4,}', $regex);
        $contents = preg_replace($regex, 'TODO:LICENSEBLOCK', $contents);
    }

    // The check for existing matches uses a regex so that it doesn't false-positive on the year
    // This supports single years, year ranges and comma-separated years
    $regex = '/' . preg_quote($license_text, '/') . '/';
    $regex = str_replace('YEAR', '[-, 0-9]{4,}', $regex);
    if (preg_match($regex, $contents)) {
        return;
    }

    $license_text = str_replace('YEAR', date('Y'), $license_text);

    if (strpos($contents, 'TODO:LICENSEBLOCK') !== false) {
        $contents = str_replace('TODO:LICENSEBLOCK', $license_text, $contents);

    } else if (strpos($contents, '<?php') == 0) {
        $inject = '/*' . PHP_EOL
            . ' * ' . $license_text . PHP_EOL
            . ' */' . PHP_EOL
            . PHP_EOL;
        $contents = preg_replace('/^<\?php\n/', '$0' . $inject, $contents);

    } else {
        $contents = '<?php' . PHP_EOL
            . '/*' . PHP_EOL
            . ' * ' . $license_text . PHP_EOL
            . ' */' . PHP_EOL
            . '?>' . PHP_EOL
            . PHP_EOL
            . $contents;
    }

    file_put_contents($filename, $contents);
}
