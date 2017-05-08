<?php

/**
* Creates merged versions of files, as per the definitions in installdata/merge.xml
* Unlike the install tool, this tool does not compress the merged files.
**/


$merge = file_get_contents('merge.xml');
if (! $merge) die('No merge data');

chdir(__DIR__ . '/../..');

$xml = simplexml_load_string($merge);

foreach ($xml->merge as $m) {
    $out = '';
    foreach ($m->file as $f) {
        $out .= file_get_contents('src/' . ((string)$f['src'])) . "\n\n";
    }

    $f = 'src/' . str_replace('REV', 'trunk', ((string)$m['dest']));

    file_put_contents($f, $out);

    echo "Saved {$f}.\n";
}
