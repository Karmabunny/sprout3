<?php
setlocale(LC_CTYPE, 'en_US.UTF8');

// Some of the "odd name" cleanup will break in some cases
$name_replace = array(
    'CC' => 'Cocos Islands',                // Cocos (Keeling) Islands
    'BQ' => 'Caribbean Netherlands',        // Bonaire, Sint Eustatius and Saba
    'CD' => 'DR Congo',                     // Congo, the Democratic Republic of the
    'VA' => 'Vatican City State',           // Holy See (Vatican City State)
    'KP' => 'North Korea',                  // Korea, Democratic People's Republic of
    'KR' => 'South Korea',                  // Korea, Republic of
    'LA' => 'Laos',                         // Lao People's Democratic Republic
    'GS' => 'South Georgia',                // South Georgia and the South Sandwich Islands
    'VG' => 'Virgin Islands, British',      // Virgin Islands, British
    'VI' => 'Virgin Islands, U.S.',         // Virgin Islands, U.S.
);

// Read file
$raw = @file_get_contents('iso3166.tsv');
if (! $raw) die("Could not load file\n");

// Parse file
$countries = array();
$raw = explode("\n", $raw);
foreach ($raw as $idx => $ln) {
    $ln = trim($ln);
    if ($ln == '') continue;

    list($name, $alpha2, $alpha3) = explode("\t", $ln, 3);
    if (!$name or !$alpha2 or !$alpha3) continue;

    // Clean up odd names
    if (isset($name_replace[$alpha2])) {
        $name = $name_replace[$alpha2];
    } else {
        $name = preg_replace('!,.+!', '', $name);
        $name = trim($name);
    }

    $countries[] = array($name, $alpha2, $alpha3);
}

if (count($countries) == 0) die("Could not parse file - no countries found\n");

// Sort by name
usort($countries, function($a, $b) {
    $i = iconv("UTF-8", "ASCII//TRANSLIT", $a[0]);
    $j = iconv("UTF-8", "ASCII//TRANSLIT", $b[0]);
    return strcasecmp($i, $j);
});

// Create constants file
$out = "<?php\n\n";
$out .= "/**\n";
$out .= "* Generated class containing ISO-3166 country mappings for alpha-2 and alpha-3\n";
$out .= "**/\n";
$out .= "class CountryConstants {\n";
$out .= "\t\n";
$out .= "\tpublic static \$alpha2 = array(\n";
foreach ($countries as $row) {
    $row[0] = addslashes($row[0]);
    $out .= "\t\t'{$row[1]}' => '{$row[0]}',\n";
}
$out .= "\t);\n";
$out .= "\t\n";
$out .= "\tpublic static \$alpha3 = array(\n";
foreach ($countries as $row) {
    $row[0] = addslashes($row[0]);
    $out .= "\t\t'{$row[2]}' => '{$row[0]}',\n";
}
$out .= "\t);\n";
$out .= "\t\n";
$out .= "\tpublic static \$alpha2to3 = array(\n";
foreach ($countries as $row) {
    $out .= "\t\t'{$row[1]}' => '{$row[2]}',\n";
}
$out .= "\t);\n";
$out .= "\t\n";
$out .= "}\n";

// Save
file_put_contents('CountryConstants.php', $out);


