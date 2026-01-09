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

namespace Sprout\Helpers;

use Composer\InstalledVersions;
use Exception;
use InvalidArgumentException;
use karmabunny\kb\Encrypt;
use karmabunny\kb\EncryptInterface;
use ReflectionClass;

use Kohana;

use karmabunny\pdb\Exceptions\QueryException;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;

/**
* Useful functions for sprout in general
**/
class Sprout
{

    /**
     * When reading out of a response object, process this many bytes at a time.
     *
     * @var int 1 MiB
     */
    public static $SEND_BUFFER_SIZE = 1024 * 1024;


    /**
     * Determines the file path for a class, usually for autoloading
     *
     * @param string $class The class, including namespace
     * @return string|false path or false if it couldn't be found
     */
    public static function determineFilePath($class)
    {
        try {
            $reflect = new ReflectionClass($class);
            return $reflect->getFileName();
        } catch (ReflectionException $ex) {
            return false;
        }
    }


    /**
     * Removes the namespaces from a class-like entity,
     * e.g. Sprout\Helpers\Text => Text
     * @param string $classlike
     * @return string
     */
    public static function removeNs($classlike)
    {
        $pos = strrpos($classlike, '\\');
        if ($pos !== false) {
            return substr($classlike, $pos + 1);
        }
        return $classlike;
    }


    /**
     * Creates an object of a class specified by a string name, with a list of possible namespaces to lookup if the
     * specified class name doesn't contain a namespace
     * @param string $class The class to instantiate
     * @param array $possible_nses Possible namespaces to try
     * @return object
     * @throws Exception if the class lookup failed
     */
    public static function nsNew($class, array $possible_nses)
    {
        if (strpos($class, '\\') !== false) {
            if (class_exists($class)) {
                return new $class;
            } else {
                throw new Exception("Unable to load class {$class}");
            }
        }
        foreach ($possible_nses as $ns) {
            $full_class = $ns . '\\' . $class;
            if (class_exists($full_class)) {
                return new $full_class;
            }
        }
        throw new Exception("Class lookup failed: {$class}");
    }


    /**
     * Gets the full class name (including namespace) for a specified class, with a list of namespaces to search
     * @param string $class The class to instantiate, e.g. 'Fb'
     * @param array $possible_nses Possible namespaces to try, e.g. ['Sprout\Helpers']
     * @return string
     * @throws Exception if the class lookup failed
     */
    public static function nsClass($class, array $possible_nses)
    {
        if (strpos($class, '\\') !== false) {
            if (class_exists($class)) return $class;
        }
        foreach ($possible_nses as $ns) {
            $full_class = $ns . '\\' . $class;
            if (class_exists($full_class)) return $full_class;
        }
        throw new Exception("Class lookup failed: {$class}");
    }


    /**
     * Gets the full name (including namespaced class) for a specified function
     * @param string $func The function to find, e.g. 'Fb::dropdown'
     * @param array $possible_nses Possible namespaces to try, e.g. ['Sprout\Helpers']
     * @return string
     * @throws Exception if the function lookup failed
     */
    public static function nsFunc($func, array $possible_nses)
    {
        $class = '';
        if (strpos($func, '::') !== false) {
            list($class, $func) = explode('::', $func, 2);
        }

        if (strpos($func, '\\') !== false) {
            if ($class) {
                if (method_exists($class, $func)) return "{$class}::{$func}";
            }
            if (function_exists($func)) return $func;
        }
        foreach ($possible_nses as $ns) {
            if ($class) {
                $full_class = $ns . '\\' . $class;
                if (method_exists($full_class, $func)) return "{$full_class}::{$func}";
            } else {
                $full_fn = $ns . '\\' . $func;
                if (function_exists($full_fn)) return $full_fn;
            }
        }
        throw new Exception("Function lookup failed: {$func}");
    }


    /**
     * Construct a new instance of a class with a given name
     *
     * @example
     *     $inst = Sprout::instance($widget_class, 'Sprout\\Widgets\\Widget');
     *
     * @template T
     * @param class-string $class_name The name of the class
     * @param class-string<T>|class-string[] $base_class_name The base class or interface which the class must extend/implement.
     *        Can be a string for a single check, or an array for multiple checks.
     *        NULL disables this check.
     * @param bool $assert_all If true, all base classes/interfaces must be met.
     * @throws InvalidArgumentException If the class does not exist
     * @return object The new instance
     */
    public static function instance(string $class_name, $base_class_name = null, $assert_all = true): object
    {
        if (!$class_name or !class_exists($class_name)) {
            throw new InvalidArgumentException("Class <{$class_name}> does not exist");
        }

        // Check the class isn't abstract
        $class = new ReflectionClass($class_name);
        if ($class->isAbstract()) {
            throw new InvalidArgumentException("Class <{$class_name}> is abstract");
        }

        // Check that the class extends/implements everything it's required to
        if (!empty($base_class_name)) {
            if (!is_array($base_class_name)) {
                $base_class_name = [$base_class_name];
            }

            $match = false;

            foreach ($base_class_name as $chk) {
                if ($class->getName() == trim($chk, '\\')) {
                    $match = true;
                    continue;
                }

                if ($class->isSubclassOf($chk)) {
                    $match = true;
                    continue;
                }

                if ($assert_all) {
                    throw new InvalidArgumentException("Class <{$class_name}> is not a sub-class of <{$chk}>");
                }
            }

            if (!$match) {
                $base_class_name = implode(', ', $base_class_name);
                throw new InvalidArgumentException("Class <{$class_name}> is not a sub-class of <{$base_class_name}>");
            }
        }

        // Interesting little way to report an instantiation error even if display_errors is off.
        // On fatal errors, the output buffer gets flushed to the beowser, reporting our message.
        // On success, we just clear the buffer.
        // On the test server, we just let it die naturally.
        if (IN_PRODUCTION) {
            ob_start();
            echo '<p><b>FATAL ERROR:</b><br>Unable to instance class "' . Enc::html($class_name) . '".</p>';
            $inst = @new $class_name;
            ob_end_clean();
        } else {
            $inst = new $class_name;
        }

        return $inst;
    }


    /**
     * Determine the version for a composer package.
     *
     * @param string $package
     * @return string e.g. `1.2.3 - #git-ref`
     */
    public static function getInstalledVersion(string $package): string
    {
        $version = InstalledVersions::getPrettyVersion($package) ?? 'dev';
        $reference = InstalledVersions::getReference($package) ?? 'unknown';

        return sprintf('%s - #%.7s', $version, $reference);
    }


    /**
     * Determine the version for the current site, i.e. the 'root' package.
     *
     * This doesn't include the semvar version but instead the package name.
     * Often sites don't release a "version". Although perhaps identifying a
     * deploy tag would be useful.
     *
     * @return string e.g. `package - #git-ref`
     */
    public static function getSiteVersion(): string
    {
        $root = InstalledVersions::getRootPackage();
        return sprintf('%s - #%.7s', $root['name'], $root['reference']);
    }


    /**
     * Returns the current version of sprout
     *
     * @param bool $git_version Optional flag to return git version, returns branding version by default
     * @return string e.g. `1.2.3 - #git-ref`
     */
    public static function getVersion($git_version = false)
    {
        if (!empty($git_version)) return Kohana::config('core.version');
        return Kohana::config('core.version_brand');
    }


    /**
     * Is this module installed?
     *
     * @deprecated use `Modules::isInstalled()`
     * @param string $module_name provided by `ModuleInterface::getName()`
     * @return bool if installed, otherwise false
     */
    public static function moduleInstalled(string $module_name): bool
    {
        return Modules::isInstalled($module_name);
    }


    /**
    * Gets a simplified backtrace with fewer elements and no recursion
    * @param array $trace If empty, the trace is automatically determined
    * @return array
    */
    public static function simpleBacktrace(array $trace = [])
    {
        if (count($trace) == 0) {
            // This is safe because we strip the first two frames.
            // phpcs:ignore
            $trace = debug_backtrace();

            // Remove this and its caller
            array_shift($trace);
            array_shift($trace);
        }

        $simple_trace = [];
        foreach ($trace as $call) {
            $simple_call = [];
            if (isset($call['file'])) {
                $file = $call['file'];
                if (IN_PRODUCTION or @$_SERVER['SERVER_ADDR'] != @$_SERVER['REMOTE_ADDR']) {
                    if (substr($file, 0, strlen(DOCROOT)) == DOCROOT) {
                        $file = substr($file, strlen(DOCROOT));
                    }
                }
                $simple_call['file'] = $file;
            }
            if (isset($call['line'])) {
                $simple_call['line'] = $call['line'];
            }
            if (!empty($call['function'])) {
                $call_func = $call['function'];
                if (isset($call['class'])) {
                    $class = $call['class'];
                    if (isset($call['type'])) {
                        $class .= $call['type'];
                    } else {
                        $class .= '-??-';
                    }
                    $call_func = $class . $call_func;
                }
                $simple_call['function'] = $call_func;
            }
            if (!empty($call['args'])) {
                $args = [];
                foreach ($call['args'] as $akey => $aval) {
                    if (is_object($aval)) {
                        $args[$akey] = get_class($aval);
                    } else if (is_array($aval)) {
                        $len = count($aval);
                        $args[$akey] = "array({$len}): " . self::condenseArray($aval);
                    } else {
                        $args[$akey] = self::readableVar($aval);
                    }
                }
                unset($call['args']);
                $simple_call['args'] = $args;
            }
            $simple_trace[] = $simple_call;
        }
        return $simple_trace;
    }


    /**
     * Converts a variable into something human readable
     * @param mixed $var
     * @return string
     */
    public static function readableVar($var)
    {
        if (is_array($var)) return self::condenseArray($var);
        if (is_bool($var)) return $var? 'true': 'false';
        if (is_null($var)) return 'null';
        if (is_int($var) or is_float($var)) return (string) $var;
        if (is_string($var)) {
            return "'" . str_replace("'", "\\'", $var) . "'";
        }
        if (is_resource($var)) return 'resource';
        if (is_object($var)) return get_class($var);
        return 'unknown';
    }


    /**
     * Condenses an array into a string
     */
    public static function condenseArray(array $arr)
    {
        $keys = array_keys($arr);
        $int_keys = true;
        foreach ($keys as $key) {
            if (!is_int($key)) {
                $int_keys = false;
                break;
            }
        }

        $str = '[';
        $arg_num = 0;
        foreach ($arr as $key => $val) {
            if (++$arg_num != 1) $str .= ', ';
            if (!$int_keys) $str .= self::readableVar($key) . ' => ';
            $str .= self::readableVar($val);
        }
        $str .= ']';
        return $str;
    }


    /**
    * Checks a URL that is to be used for redirection is valid.
    *
    * Will allow remote URLs beginning with 'http://' and local URLs beginning with '/'
    **/
    public static function checkRedirect($text)
    {
        $text = Enc::cleanfunky($text);
        if (preg_match('!^http(s?)://[a-z]!', $text)) return true;
        if (preg_match('!^/[a-z]!', $text)) return true;

        return false;
    }


    /**
    * Returns an absolute URL for the web root of this server
    *
    * Example: 'http://thejosh.info/sprout_test/'
    *
    * @param string $protocol Protocol to use. 'http' or 'https'.
    *    Defaults to server config option, which if not set, uses current request protocol.
    **/
    public static function absRoot($protocol = '')
    {
        if ($protocol == '') {
            $protocol = Kohana::config('config.site_protocol');
        }
        if ($protocol == '') {
            $protocol = Request::protocol();
        }
        if ($protocol == '') {
            $protocol = 'http';
        }

        return Url::base(true, $protocol);
    }


    /**
    * Takes a mysql DATETIME value (will probably also work with a TIME or DATE value)
    * and formats it according to the format codes specified by the PHP date() function.
    *
    * The format is optional, if omittted, uses 'd/m/Y g:i a' = '7/11/2010 5:27 pm'
    **/
    public static function formatMysqlDatetime($date, $format = 'd/m/Y g:i a')
    {
        return date($format, strtotime($date));
    }


    /**
    * Returns a string of random numbers and letters
    **/
    public static function randStr($length = 16, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890')
    {
        $num_chars = strlen($chars) - 1;

        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $chars[mt_rand(0, $num_chars)];
        }

        return $string;
    }


    /**
    * Returns a time in 'x minutes ago' format.
    *
    * Very small times (0, 1 seconds) are considered 'Just now'.
    * Times are represneted in seconds, minutes, hours or days.
    *
    * @param int $timediff Amount of time that has passed, in seconds.
    **/
    public static function timeAgo($timediff)
    {
        $timediff = (int) $timediff;

        if ($timediff < 2) return 'Just now';

        if ($timediff >= 86400) {
            $unit = ' day';
            $time = floor($timediff / 86400);

        } else if ($timediff >= 3600) {
            $unit = ' hour';
            $time = floor($timediff / 3600);

        } else if ($timediff >= 60) {
            $unit = ' minute';
            $time = floor($timediff / 60);

        } else {
            $unit = ' second';
            $time = $timediff;

        }

        return $time . $unit . ($time == 1 ? ' ago' : 's ago');
    }


    /**
    * Load the text for an extra page.
    * Returns NULL on error.
    *
    * @param int $type The page type, should be one of the type constants
    **/
    public static function extraPage($type)
    {
        $subsite_id = SubsiteSelector::$content_id;

        $q = "SELECT text
            FROM ~extra_pages
            WHERE subsite_id = ? AND type = ?
            ORDER BY id
            LIMIT 1";
        try {
            $row = Pdb::q($q, [$subsite_id, $type], 'row');
        } catch (QueryException $ex) {
            return null;
        }

        return $row['text'];
    }


    /**
    * Attempts to put the handbrake on a script which is doing malicious inserts to the database
    *
    * @param string $table The table name, not prefixed
    * @param string $column The column to check
    * @param string $value The value to check
    * @param int $limit The number of inserts allowed in the provided time
    * @param int $time The amount of time the limit applies for, in seconds. Default = 1 hour
    * @param array $conds Additional conditions for the WHERE clause, formatted as per {@see Pdb::buildClause}
    * @return bool True if the insert rate is OK
    **/
    public static function checkInsertRate($table, $column, $value, $limit, $time = 3600, array $conds = [])
    {
        Pdb::validateIdentifier($table);
        Pdb::validateIdentifier($column);

        $params = [$value, (int)$time];
        $clause = Pdb::buildClause($conds, $params);
        if ($clause) $clause = 'AND ' . $clause;

        $q = "SELECT COUNT(id) AS C
            FROM ~{$table}
            WHERE {$column} LIKE ?
                AND date_added > DATE_SUB(NOW(), INTERVAL ? SECOND)
                {$clause}";
        /** @var int */
        $count = Pdb::q($q, $params, 'val');

        if ($count >= $limit) return false;

        return true;
    }


    /**
    * Back-end for link-checking tool
    **/
    public static function linkChecker()
    {
        throw new Exception('Not in use any more; use the worker "WorkerLinkChecker".');
    }


    /**
    * Takes two strings of text (which will be stripped of HTML tags)
    * and returns HTML which is a table showing the differences
    * in a nice colourful way
    **/
    public static function colorisedDiff($orig, $new)
    {
        $tmp_name1 = tempnam('/tmp', 'dif');
        file_put_contents($tmp_name1, trim(strip_tags($orig)) . "\n");

        $tmp_name2 = tempnam('/tmp', 'dif');
        file_put_contents($tmp_name2, trim(strip_tags($new)) . "\n");

        $diff = shell_exec("diff -yat --left-column --width=3004 {$tmp_name1} {$tmp_name2}");

        unlink($tmp_name1);
        unlink($tmp_name2);

        // Colorise diff
        $diff = explode("\n", $diff);
        $out = '<table cellpadding="5" cellspacing="3">';
        $out .= '<tr><td>&nbsp;</td>';
        $out .= '<th style="width: 420px;" bgcolor="#CECECE">Old revision (paragraph-by-paragraph)</th>';
        $out .= '<th style="width: 420px;" bgcolor="#CECECE">New revision (paragraph-by-paragraph)</th>';
        $out .= '</tr>';

        foreach ($diff as &$line) {
            if (! preg_match('/^(.{1,1500}) (.) ? ?(.{1,1500})?$/', $line, $matches)) continue;
            @list($nop, $left, $char, $right) = $matches;

            if ($left == '' and $right == '') continue;

            $line = $left . '<b>' . $char . '</b>' . $right;

            if (strlen($left) >= 1500) $left .= '...';
            if (strlen($right) >= 1500) $right .= '...';

            switch ($char) {
                case '(':
                    //$out .= '<tr><td><b>Not changed</b></td><td>' . $left . '</td><td>' . $left . '</td></tr>';
                    break;

                case '|':
                    $out .= '<tr><td><b>Changed</b></td><td bgcolor="#D8F1FF">' . $left . '</td><td bgcolor="#D8F1FF">' . $right . '</td></tr>';
                    break;

                case '<':
                    $out .= '<tr><td><b>Removed</b></td><td bgcolor="#FCA7AE">' . $left . '</td><td bgcolor="#FFDDDF">&nbsp;</td></tr>';
                    break;

                case '>':
                    $out .= '<tr><td><b>Added</b></td><td bgcolor="#E6FADD">&nbsp;</td><td bgcolor="#C9FFB3">' . $right . '</td></tr>';
                    break;
            }
        }
        $out .= '</table>';

        return $out;
    }


    /**
    * Set the etag header, and some expiry headers.
    * Checks if the etag matches - if it does, terminates the script with '304 Not Modified'.
    *
    * ETag should be specified as a string.
    * Expires should be specified as a number of seconds, after that time the URL will expire.
    *
    * ETags should be something which is unique for that version of the URL. They should use
    * something which is collission-resistant, such as MD5. They should vary based on the
    * Accept-Encoding header, or any other 'accept' headers, if you are supporting them.
    **/
    public static function etag($etag, $expires)
    {
        header('ETag: "' . $etag . '"');
        header('Pragma: public');
        header('Cache-Control: store, cache, must-revalidate, max-age=' . $expires);
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');

        if ($_SERVER['HTTP_IF_NONE_MATCH']) {
            $match = str_replace('"', '', $_SERVER['HTTP_IF_NONE_MATCH']);
            if ($match == $etag) {
                header('HTTP/1.0 304 Not Modified');
                exit;
            }
        }
    }


    /**
    * Translate an array which may contain a page_id, filename or url into the final URL to use
    **/
    public static function translateLink($row)
    {
        if ($row['page_id']) {
            $root = Navigation::getRootNode();
            $page = $root->findNodeValue('id', $row['page_id']);
            if ($page) {
                return $page->getFriendlyUrl();
            }
        }

        if ($row['filename']) {
            return File::absUrl($row['filename']);
        }

        if ($row['url']) {
            return $row['url'];
        }

        return null;
    }


    /**
    * Return the last-modified date of all pages on the (sub-)site
    * Returns NULL on error.
    *
    * The date is formatted using the php date function.
    * The default date format is "d/m/Y".
    *
    * @param string $date_format The date format to return the date in
    * @return string Last modified date
    * @return null On error
    **/
    public static function lastModified($date_format = 'd/m/Y')
    {
        try {
            $q = "SELECT date_modified
                FROM ~pages
                WHERE subsite_id = ?
                ORDER BY date_modified DESC
                LIMIT 1";
            $date = Pdb::query($q, [SubsiteSelector::$content_id], 'val');
            return date($date_format, strtotime($date));

        } catch (QueryException $ex) {
            return null;
        }
    }


    /**
     * Adds classes, analytics and target-blank to file links.
     *
     * Also adds a random string, which prevents caching, solving some problems we were having with some clients.
     *
     * @param string $html
     * @return string
     */
    public static function specialFileLinks(string $html): string
    {
        // Grabs <a> links, with href containing:
        //  - optional something
        //  - "files/"
        //  - something
        //  - "."
        //  - some letters (a-z)
        // and the A must only have non HTML content (doesn't contain < or >)
        //
        return preg_replace_callback(
            '!<a[^>]+href="([^"]*)files/([^"]+\.([a-z]+))"[^>]*>([^<>]+)</a>!',

            function($matches) {
                $matches[1] = html_entity_decode($matches[1]);
                $matches[2] = html_entity_decode($matches[2]);
                $matches[3] = html_entity_decode($matches[3]);
                $matches[4] = html_entity_decode($matches[4]);

                // Only mangle local URLs; leave remote URLs alone
                $http_pattern = '#^(?:https?:)?//([^/]*)#';
                $link_matches = [];
                $link_matches_pattern = preg_match($http_pattern, $matches[1], $link_matches);
                $own_domain_matches = [];
                $url_base = Subsites::getAbsRoot(SubsiteSelector::$subsite_id);
                $own_domain_matches_pattern = preg_match($http_pattern, $url_base, $own_domain_matches);

                // Local URLs
                $url = File::relUrl($matches[2]) . '?v=' . mt_rand(100, 999);

                // Remote URLs
                if ($link_matches_pattern and $own_domain_matches_pattern) {
                    $link_domain = preg_replace('/^www\./', '', $link_matches[1]);
                    $own_domain = preg_replace('/^www\./', '', $own_domain_matches[1]);
                    if ($link_domain != $own_domain) {
                        return $matches[0];
                    }
                }

                $class = 'document document-' . $matches[3];
                $onclick = "ga('send', 'event', 'Document', 'Download', '" . Enc::js($matches[2]) . "');";

                if (preg_match('!class="([^"]+)"!', $matches[0], $m)) {
                    $class .= ' ' . html_entity_decode($m[1]);
                }

                $out = '<a href="' . Enc::html($url) . '"';
                $out .= ' class="' . Enc::html(trim($class)) . '"';
                $out .= ' target="_blank"';
                $out .= ' data-ext="' . Enc::html($matches[3]) . '"';
                $out .= ' data-size="' . Enc::html(File::humanSize(File::size($matches[2]))) . '"';
                $out .= ' onclick="' . Enc::html($onclick) . '">';
                $out .= Enc::html($matches[4]);
                $out .= '</a>';

                return $out;
            },

            $html
        );
    }


    /**
    * Return true if the browser supports drag-and-drop uploads.
    **/
    public static function browserDragdropUploads()
    {
        $supported = array(
            'Firefox' => '4.0.0',
            'Internet Explorer' => '10.0',
            'Chrome' => '13.0.0',
            'Safari' => '6.0.0',
        );

        if (! isset($supported[Kohana::userAgent('browser')])) {
            return false;
        }

        $min_version = $supported[Kohana::userAgent('browser')];

        return version_compare(Kohana::userAgent('version'), $min_version, '>=');
    }


    /**
    * @deprecated Use {@see Security::passwordComplexity} instead
    **/
    public static function passwordComplexity($str)
    {
        $errs = Security::passwordComplexity($str, 8, 0, false);
        if (count($errs) == 0) return true;
        return $errs;
    }


    /**
    * Return a list of admins to send emails to.
    *
    * The return value is an array of arrays.
    * The inner arrays contains the keys "name" and "email".
    **/
    public static function adminEmails()
    {
        $out = array();

        $ops = AdminPerms::getOperatorsWithAccess('access_reportemail');
        foreach ($ops as $row) {
            $out[] = array(
                'name' => $row['name'],
                'email' => $row['email'],
            );
        }

        return $out;
    }


    /**
    * Check an IP against a list of IP addresses, with logic for CIDR ranges
    *
    * @return bool True if the IP is in the list, false if it's not
    **/
    public static function ipaddressInArray($needle, $haystack)
    {
        foreach ($haystack as $check) {
            $parts = explode('/', $check, 2);

            if (count($parts) == 1) {
                // Plain IP
                if ($needle == $parts[0]) return true;

            } else {
                // CIDR
                list($subnet, $mask) = $parts;
                $mask = ~((1 << (32 - $mask)) - 1);

                // Correctly handle unaligned subnets
                $subnet = ip2long($subnet) & $mask;
                if ((ip2long($needle) & $mask) === $subnet) {
                    return true;
                }
            }
        }

        return false;
    }


    /**
     * Returns the memory limit in bytes.
     *
     * If there is no limit, returns PHP_INT_MAX.
     *
     * @return int Bytes
     */
    public static function getMemoryLimit()
    {
        $memory_limit = ini_get('memory_limit');

        if ($memory_limit == -1) return PHP_INT_MAX;

        if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
            $matches[2] = strtoupper($matches[2]);
            if ($matches[2] == 'G') return $matches[1] * 1024 * 1024 * 1024;
            if ($matches[2] == 'M') return $matches[1] * 1024 * 1024;
            if ($matches[2] == 'K') return $matches[1] * 1024;
        } else {
            return $memory_limit;
        }
    }

    /**
     * Gets the first key value pair of an iterable
     *
     * @deprecated use `Arrays::firstPair`
     * @param iterable $iter An array or Traversable
     * @return array|null An array of [key, value] or null if the iterable is empty
     * @example
     *          list ($key, $value) = Sprout::iterableFirst(['an' => 'array']);
     */
    public static function iterableFirst($iter)
    {
        foreach ($iter as $k => $v) {
            return [$k, $v];
        }

        return null;
    }

    /**
     * Gets the first key of an iterable
     *
     * @deprecated use `Arrays::firstKey`
     * @param iterable $iter An array or Traversable
     * @return mixed|null The value or null if the iterable is empty
     */
    public static function iterableFirstKey($iter)
    {
        return @static::iterableFirst($iter)[0];
    }

    /**
     * Gets the first value of an iterable
     *
     * Note, unlike the first key helper a `null` result here could be a valid value.
     * You can check true emptiness using `iterableFirst()` or `iterableFirstKey()`.
     *
     * @deprecated use `Arrays::first`
     * @param iterable $iter An array or Traversable
     * @return mixed|null The value or null if the iterable is empty
     */
    public static function iterableFirstValue($iter)
    {
        return @static::iterableFirst($iter)[1];
    }


    /**
     * Render out a response object.
     *
     * Note this doesn't fire any system events.
     *
     * @param ResponseInterface $response
     * @return void
     */
    public static function send(ResponseInterface $response)
    {
        $version = $response->getProtocolVersion();
        $reason = $response->getReasonPhrase();
        $status = $response->getStatusCode();

        header("HTTP/{$version} {$status} {$reason}", true, $status);

        foreach ($response->getHeaders() as $name => $values) {
            // Kohana sets Content-Type: text/html at line 178 (before routing), needs to be overridable
            $is_content_type = (strtolower($name) === 'content-type');
            if ($is_content_type) {
                $name = 'Content-Type';
            }
            foreach ($values as $value) {
                header("{$name}: {$value}", $is_content_type);
            }
        }

        $stream = $response->getBody();

        if ($stream->isReadable()) {
            while (!$stream->eof()) {
                echo $stream->read(static::$SEND_BUFFER_SIZE);
            }
        }
    }


    /**
     * Get an encryption tool instance. Falls back to looking for configs in core encryption config file
     *
     * This can be overriden by models or other places that need a different approach to encryption
     *
     * @param array|string $config
     *
     * @return EncryptInterface
     * @throws Exception
     */
    public static function getEncrypt($config): EncryptInterface
    {
        if (is_string($config)) {
            $name = $config;

            // Test the config group name
            if (($config = Kohana::config("encryption.{$config}")) === null) {
                throw new Exception("Undefined encrypt group '{$name}'");
            }
        }

        if (is_array($config)) {
            // Append the default configuration options
            $config += Kohana::config('encryption.default');
        } else {
            // Load the default group
            $config = Kohana::config('encryption.default');
        }

        // Create a new encryption object
        $encrypt = Encrypt::instance($config);

        return $encrypt;
    }

}
