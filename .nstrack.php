<?php
/**
 * This is a config file for NStrack
 */


/** Directory which contains the source code */
Config::setSourceDir(Config::dir() . 'src/');

/** Class references that are known to be broken */
Config::setIgnore([
    // 3rd-party tools
    'FirePHP_Insight',
    'PDFLib',
    'scssc',
    'PHPBrowserMobProxy_Client',
    'Net_Server_Driver_Fork',
    'HTTP_Server',
    'DashboardPage',
    'SaunterPHP_Framework_Exception',
    'TCPDF_STATIC',
    'ntlm_sasl_client_class',

    // Missing modules
    'GalleryConstants',
    'utf8',          // Needs to die
]);

$kohana = ['Event', 'Kohana', 'Kohana_Exception', 'Kohana_404_Exception'];
Config::setSort(function($a, $b) use ($kohana) {
    if (is_array($a)) $a = array_shortest($a);
    if (is_array($b)) $b = array_shortest($b);
    $a_has_ns = (strpos($a, '\\') !== false);
    $b_has_ns = (strpos($b, '\\') !== false);
    if (!$a_has_ns and $b_has_ns) return -1;
    if ($a_has_ns and !$b_has_ns) return 1;

    $a_is_kohana = in_array($a, $kohana);
    $b_is_kohana = in_array($b, $kohana);
    if (!$a_is_kohana and $b_is_kohana) return -1;
    if ($a_is_kohana and !$b_is_kohana) return 1;

    if ($a < $b) return -1;
    if ($a > $b) return 1;
    return 0;
});

Config::setGroup(function(array $classes, $has_ns) use ($kohana) {
    if ($has_ns) {
        $section = 'sprout';
    } else if (in_array(reset($classes), $kohana)) {
        $section = 'kohana';
    } else {
        $section = 'php';
    }
    return $section;
});
