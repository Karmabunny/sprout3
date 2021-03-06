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

namespace Sprout\Controllers;

use DOMDocument;
use Exception;
use InvalidArgumentException;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SimpleXMLElement;
use ZipArchive;

use Kohana;
use Kohana_404_Exception;

use Sprout\Exceptions\QueryException;
use Sprout\Exceptions\RowMissingException;
use Sprout\Exceptions\ValidationException;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Archive;
use Sprout\Helpers\Auth;
use Sprout\Helpers\Constants;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\DatabaseSync;
use Sprout\Helpers\Email;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Export;
use Sprout\Helpers\ExportTableSQL;
use Sprout\Helpers\Fb;
use Sprout\Helpers\File;
use Sprout\Helpers\FileIndexing;
use Sprout\Helpers\FileUpload;
use Sprout\Helpers\Form;
use Sprout\Helpers\Html;
use Sprout\Helpers\ImportCMS;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Itemlist;
use Sprout\Helpers\Json;
use Sprout\Helpers\LaunchChecks;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\QueryTo;
use Sprout\Helpers\Register;
use Sprout\Helpers\Request;
use Sprout\Helpers\Router;
use Sprout\Helpers\Session;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\SubsiteSelector;
use Sprout\Helpers\Subsites;
use Sprout\Helpers\Text;
use Sprout\Helpers\TreenodeValueMatcher;
use Sprout\Helpers\Url;
use Sprout\Helpers\Validator;
use Sprout\Helpers\Validity;
use Sprout\Helpers\View;


/**
* Provides tools for dealing with the database
* Tools include a sync tool and a structure viewer
**/
class DbToolsController extends Controller
{
    private $template_enabled = true;



    /**
     * List of tools to show in the sidebar and index view
     */
    public static $tools = [
        'Development' => [
            [ 'url' => 'dbtools/sql', 'name' => 'SQL', 'desc' => 'Allows the user to execute SQL queries' ],
            [ 'url' => 'dbtools/sync', 'name' => 'Database sync', 'desc' => 'Syncs the db structure to match db_struct.xml' ],
            [ 'url' => 'dbtools/struct', 'name' => 'View db structure', 'desc' => 'Shows table and column definitions' ],
            [ 'url' => 'dbtools/testSkinTemplates', 'name' => 'Test skin templates', 'desc' => 'Simple tool for testing skin templates' ],
            [ 'url' => 'dbtools/sessionEditor', 'name' => 'Session editor', 'desc' => 'Edit the $_SESSION' ],
            [ 'url' => 'admin/extra/worker_job/manual_run', 'name' => 'Manual run worker job', 'desc' => 'Manually run a worker job in a browser' ],
            [ 'url' => 'admin/extra/cron_job/manual_run', 'name' => 'Manual run cron job', 'desc' => 'Manually run a cron job in a browser' ],
            [ 'url' => 'admin/style_guide', 'name' => 'Admin style guide', 'desc' => 'View styles of various admin features - form fields, etc' ],
        ],
        'Code generation' => [
            [ 'url' => 'dbtools/moduleBuilder', 'name' => 'Module builder', 'desc' => 'Generates blank modules' ],
            [ 'url' => 'dbtools/moduleBuilderDb', 'name' => 'Database struct generator', 'desc' => 'Generates db_struct.xml content for a module' ],
            [ 'url' => 'dbtools/moduleBuilderExisting', 'name' => 'Module builder from existing', 'desc' => 'Generates modules from an existing db_struct.xml file' ],
            [ 'url' => 'dbtools/multimake', 'name' => 'Generate multiedit', 'desc' => 'Generate multiedit code' ],
        ],
        'Environment' => [
            [ 'url' => 'dbtools/info', 'name' => 'Env and PHP info', 'desc' => 'Sprout information + phpinfo()' ],
            [ 'url' => 'dbtools/varDump', 'name' => 'Var dump', 'desc' => 'View session, cookie & server data'],
            [ 'url' => 'dbtools/email', 'name' => 'Test email', 'desc' => 'Renders form to send emails' ],
            [ 'url' => 'dbtools/launchChecks', 'name' => 'Launch checks', 'desc' => 'Run a series of self-tests to ensure everything is configured correctly' ],
            [ 'url' => 'admin/user-agent', 'name' => 'User agent tool', 'desc' => 'Show browser information<br><span>(this link doesn\'t require auth)</span>' ],
            [ 'url' => 'dbtools/generatePasswordHash', 'name' => 'Generate password hash', 'desc' => 'Generate a password hash to store in a config file' ],
            [ 'url' => 'dbtools/bcryptSpeed', 'name' => 'Test hashing speed', 'desc' => 'Test hasing speed of bcrypt' ],
            [ 'url' => 'dbtools/fileTypesIndexingSupport', 'name' => 'File indexing support', 'desc' => 'List of file types which can be indexed for full-text search' ],
        ],
        'Logs' => [
            [ 'url' => 'dbtools/exceptionLog', 'name' => 'Exception log', 'desc' => 'Browse and search exceptions' ],
            [ 'url' => 'admin/intro/cron_job', 'name' => 'Cron job log', 'desc' => 'Cron (scheduled task) log' ],
            [ 'url' => 'admin/intro/worker_job', 'name' => 'Worker job log', 'desc' => 'Log of worker (background) processes' ],
        ],
        'Migration' => [
            [ 'url' => 'dbtools/exportFiles', 'name' => 'Export files', 'desc' => 'Exports all files' ],
            [ 'url' => 'dbtools/exportTables', 'name' => 'Export database', 'desc' => 'Export tables to an SQL file' ],
            [ 'url' => 'dbtools/importFiles', 'name' => 'Import files', 'desc' => 'Imports a files into the cms' ],
            [ 'url' => 'dbtools/importTables', 'name' => 'Import database', 'desc' => 'Import database tables from a .sql file' ],
            [ 'url' => 'dbtools/importXML', 'name' => 'Import XML', 'desc' => 'Import Sprout2 CMS export' ],
        ],
    ];


    /**
    * Constructor
    **/
    public function __construct()
    {
        parent::__construct();

        // Command-line access does not require auth OR output buffering
        if (PHP_SAPI === 'cli') return;

        // Require remote (super) auth
        AdminAuth::checkLogin();
        if (AdminAuth::isSuper() !== true) {
            Notification::error('Access denied');
            Url::redirect('admin');
        }

        // Don't start output buffering for some methods
        if (strpos(Router::$method, 'json_') === 0) return;
        if (Router::$method == 'gettempfile') return;
        if (Router::$method == 'sqlcsv') return;

        // Execute some code for each module
        // This usually just loads some menu items
        $module_paths = Register::getModuleDirs();
        foreach ($module_paths as $path) {
            $path .= '/admin_load.php';
            if (file_exists($path)) include_once $path;
        }

        // Output buffering allows the methods to "echo" directly, and then the output
        // is captured and wrapped up in a template by the ->template() method
        ob_start();
    }


    /**
    * Shows the tools heading
    **/
    private function template($main_title)
    {
        if (!$this->template_enabled) return;

        $main_content = ob_get_clean();

        Needs::fileGroup('jquery.tablesorter');

        $nav = new View('sprout/dbtools/navigation');

        $view = new View('sprout/admin/main_layout');
        $view->main_title = $main_title;
        $view->browser_title = $main_title;
        $view->controller_name = 'dbtools';
        $view->controller_navigation_name = 'Dev tools';
        $view->live_url = '';
        $view->nav = $nav->render();
        $view->nav_tools = '';
        $view->main_content = $main_content;
        echo $view->render();
    }


    /**
    * Shows a list of database tools
    **/
    public function index()
    {
        echo '<div class="info">These tools allow you to manage various aspects of this SproutCMS install.</div>';

        echo '<style>';
        echo '.dbtools-box { color: #333; text-decoration: none; min-height: 150px; }';
        echo '.dbtools-box:hover { color: #333; text-decoration: none; box-shadow: 0 0 2px 2px #c3c7d4; }';
        echo '.dbtools-box h4 { margin: 0 0 0.5em 0; font-size: 20px; }';
        echo '.dbtools-box span { font-size: small; color: #666; }';
        echo '</style>';

        foreach (self::$tools as $section => $subtools) {
            echo '<h3>', Enc::html($section), '</h3>';
            echo '<div class="dbtools-wrap columns">';
            $index = 0;
            foreach ($subtools as $item) {
                $class = 'dbtools-box white-box column column-3';
                if (++$index % 4 === 0) {
                    $class .= ' column-last';
                }

                echo '<a href="', $item['url'], '" class="' . $class . '">';
                echo '<h4>', Enc::html($item['name']), '</h4>';
                echo '<p>', Text::limitedSubsetHtml($item['desc']), '</p>';
                echo '</a>';
            }
            echo '</div>';
        }

        $this->template('Database and system tools');
    }


    /**
    * Output some sprout and platform info
    **/
    public function info()
    {
        $vars = array(
            'PHP version' => phpversion(),
            'PHP sapi' => php_sapi_name(),
            'Server software' => @$_SERVER['SERVER_SOFTWARE'],
            'Server OS' => PHP_OS,
            'IN_PRODUCTION' => IN_PRODUCTION ? 'true' : 'false',
            'DOCROOT' => DOCROOT,
            'KOHANA' => KOHANA,
            'APPPATH' => APPPATH,
            'HTTP_X_FORWARDED_FOR' => @$_SERVER['HTTP_X_FORWARDED_FOR'],
            'REMOTE_ADDR' => @$_SERVER['REMOTE_ADDR'],
            'Request::userIp' => Request::userIp(),
            'Request::method' => Request::method(),
            'Request::isAjax' => (Request::isAjax() ? 'true' : 'false'),
            'Request::protocol' => Request::protocol(),
            'PHP date' => date('Y-m-d H:i:s'),
            'PHP TZ' => date_default_timezone_get(),
        );

        try {
            $row = Pdb::q("SELECT NOW() AS now, @@session.time_zone AS tz", [], 'row');
            $vars['MySQL date'] = $row['now'];
            $vars['MySQL TZ'] = $row['tz'];
        } catch (Exception $ex) {
            $vars['MySQL date'] = 'Lookup failure';
            $vars['MySQL TZ'] = 'Lookup failure';
        }

        $vars['Sprout::absRoot'] = Sprout::absRoot();
        $vars['Subsite ID'] = SubsiteSelector::$subsite_id;

        echo '<style>td { padding: 4px 8px; font-size: 12px; font-family: sans-serif; }</style>';
        echo '<div class="center"><table width="600" cellpadding="3" border="0">';
        echo '<tr class="h"><td colspan="2"><b>Basics</b></td></tr>';
        foreach ($vars as $key => $val) {
            echo '<tr><td>', $key, '</td><td>', Enc::html($val), '</td></tr>';
        }
        echo '</table></div>';

        try {
            $q = "SELECT id, name, code, active FROM ~subsites ORDER BY id";
            $res = Pdb::query($q, [], 'arr');

            echo '<p>&nbsp;</p>';
            echo '<div class="center"><table width="600" cellpadding="3" border="0">';
            echo '<tr class="h"><td colspan="4"><b>Subsites</b></td></tr>';
            foreach ($res as $row) {
                if ($row['active'] === '0') $row['name'] .= ' (inactive)';
                echo '<tr>';
                echo '<td>', $row['id'], '</td>';
                echo '<td>', Enc::html($row['name']), '</td>';
                echo '<td>', Enc::html($row['code']), '</td>';
                echo '<td>', Enc::html(Subsites::getAbsRoot($row['id'])), '</td>';
                echo '</tr>';
            }
            echo '</table></div>';
        } catch (Exception $ex) {
        }

        echo '<p>&nbsp;</p>';

        phpinfo();
    }


    /**
     * Benchmark the server to find appropriate cost parameter
     */
    public function bcryptSpeed()
    {
        $results = [];
        $cost = 8;
        $thresh_secs = 0.5;
        do {
            $start = microtime(true);
            password_hash("test", PASSWORD_BCRYPT, ['cost' => $cost]);
            $time_secs = microtime(true) - $start;
            $results[$cost] = $time_secs;
            ++$cost;
        } while ($time_secs < $thresh_secs);

        echo '<p>This tool reports the time required to hash a password using bcrypt and varying levels of difficulty cost.</p>';
        echo '<pre>';
        foreach ($results as $cost => $time_secs) {
            echo 'Cost ', $cost, ' took ', number_format($time_secs * 1000, 2), ' ms', PHP_EOL;
        }
        echo '</pre>';

        $this->template('Test hashing speed');
    }


    /**
    * Dumps an SQL result set into a table
    **/
    private function outputSqlResultset($res, $headings = null)
    {
        if ($res->columnCount() == 0) return;
        if ($res->rowCount() == 0) return;

        $res->setFetchMode(PDO::FETCH_NUM);

        echo "<div class=\"sqlresult\">\n";
        echo "<table class=\"main-list main-list-no-js\">\n";
        echo "<thead>\n";
        echo "<tr>\n";
        for ($i = 0; $i < $res->columnCount(); ++$i) {
            $col = $res->getColumnMeta($i);
            echo '  <th>', Enc::html($col['name']), "</th>\n";
        }
        echo "</tr>\n";
        echo "</thead>\n";
        echo "<tbody>\n";
        foreach ($res as $row) {
            echo "<tr>\n";
            foreach ($row as $val) {
                if ($val === null) {
                    echo "  <td><i>null</i></td>\n";
                } else {
                    echo '  <td>', Enc::html($val), "</td>\n";
                }
            }
            echo "</tr>\n";
        }
        echo "</tbody>\n";
        echo "</table>\n\n";
        echo "</div>";

        if ($res->rowCount()) {
            echo '<form action="SITE/dbtools/sqlcsv" method="post" target="_blank">';
            echo Csrf::token();
            echo '<input type="hidden" name="sql" value="', Enc::html($res->queryString), '">';
            echo '<div class="action-bar"><button type="submit" class="button icon-after icon-save">Download CSV</button></div>';
            echo '</form>';
        }

        return $res->rowCount();
    }

    #
    # Tools are below.
    #


    /**
    * Syncs the db structure to match db_struct.xml
    **/
    public function sync()
    {
        $act = false;
        if (isset($_POST['act']) and $_POST['act'] === 'yes') $act = true;
        if (PHP_SAPI === 'cli') $act = true;

        // If there is no tables, act straight away without asking the user
        $num = 0;
        try {
            $num += (int) Pdb::query("SELECT COUNT(*) FROM ~operators", [], 'val');
            $num += (int) Pdb::query("SELECT COUNT(*) FROM ~pages", [], 'val');
            $num += (int) Pdb::query("SELECT COUNT(*) FROM ~subsites", [], 'val');
        } catch (Exception $ex) {}
        if ($num == 0) {
            $act = true;
        }

        $sync = new DatabaseSync($act);

        $chk = $sync->checkConnPermissions();
        if ($chk !== true) {
            echo '<p>Insufficent database permissions for this tool.</p>';
            echo '<p>Additional permissions required: ', implode(', ', $chk), '.</p>';
            return;
        }

        $sync->loadStandardXmlFiles();
        $sync->sanityCheck();

        if ($sync->hasLoadErrors()) {
            echo $sync->getLoadErrorsHtml();
            $this->template('Database sync');
            return;
        }

        $out = $sync->updateDatabase();
        if (PHP_SAPI === 'cli') {
            if ($out) {
                $out = trim($out);
                $out = preg_replace('!<h3>(.+?)</h3>!', '== $1 ==', $out);
                $out = str_replace('<pre class="query">', ' --> ', $out);
                echo strip_tags($out);
                echo PHP_EOL;
            } else {
                echo 'Everything is up to date.', PHP_EOL;
            }
            return;
        }

        if ($out == '') {
            echo '<p>Everything is up to date.</p>';
        } else {
            echo '<style>';
            echo '.update-log .query { color: blue; border: none; padding: 0 0 0 100px; }';
            echo '.update-log b { display: inline-block; width: 100px; }';
            echo '.update-log p.heading { margin: 20px 0 5px; }';
            echo '.update-log pre { margin-bottom: 0; }';
            echo '</style>';
            if (!$act) {
                echo '<form action="dbtools/sync" method="post">';
                echo '<input type="hidden" name="act" value="yes">';
                echo '<div class="action-bar"><button type="submit" class="button button-orange icon-after icon-loop">Run this sync</button></div>';
                echo '</form>';
            }
            echo '<div class="update-log">';
            echo $out;
            echo '</div>';
            if (!$act) {
                echo '<form action="dbtools/sync" method="post">';
                echo '<input type="hidden" name="act" value="yes">';
                echo '<div class="action-bar"><button type="submit" class="button button-orange icon-after icon-loop">Run this sync</button></div>';
                echo '</form>';
            }
        }

        // Clear the Kohana caches too
        if (file_exists(APPPATH . 'cache/kohana_configuration')) {
            unlink(APPPATH . 'cache/kohana_configuration');
        }
        if (file_exists(APPPATH . 'cache/kohana_find_file_paths')) {
            unlink(APPPATH . 'cache/kohana_find_file_paths');
        }
        if (file_exists(APPPATH . 'cache/kohana_language')) {
            unlink(APPPATH . 'cache/kohana_language');
        }

        $this->template('Database sync');
    }


    /**
    * Shows table and column definitions
    **/
    public function struct($arg = '')
    {
        // Show columns if a table was specified
        if (!empty($arg)) {
            echo "<h3>Columns from {$arg}</h3>";
            $q = "SHOW COLUMNS FROM `{$arg}`";
            $res = Pdb::query($q, [], 'pdo');
            $this->outputSqlResultset($res);
            $res->closeCursor();

            echo "<h3>Example data from {$arg}</h3>";
            $q = "SELECT * FROM `{$arg}` LIMIT 3";
            $res = Pdb::query($q, [], 'pdo');
            $count = $this->outputSqlResultset($res);
            $res->closeCursor();
            if ($count == 0) echo '<p><i>No data is in this table at this time</i></p>';
        }


        // Show a list of tables
        $params = [];
        $q = "SHOW TABLE STATUS";
        if (!empty($_GET['search'])) {
            $q .= " WHERE NAME LIKE CONCAT('%', ?, '%')";
            $params[] = $_GET['search'];
        }
        $res = Pdb::query($q, $params, 'pdo');

        $ignore_cols = ['Row_format', 'Max_data_length', 'Auto_increment', 'Comment', 'Version', 'Create_time',
            'Update_time', 'Check_time', 'Collation', 'Checksum', 'Create_options'];
        $headings = array();
        for ($i = 0; $i < $res->columnCount(); ++$i) {
            $meta = $res->getColumnMeta($i);
            $val = $meta['name'];
            if (in_array($val, $ignore_cols)) continue;
            $headings[$val] = $val;
        }

        echo '<form method="get">';
        echo '<input type="text" name="search" value="' . Enc::html(@$_GET['search']) . '"> <input type="submit" value="Refine">';
        echo '</form>';

        if (empty($_GET['search'])) {
            echo '<h3>All tables</h3>';
        } else {
            echo '<h3>Matching tables</h3>';
        }
        echo '<table class="main-list main-list-no-js">';
        echo "<thead>\n";
        echo '<tr>';
        foreach ($headings as $name) {
            echo '<th>', $name, '</th>';
        }
        echo '</tr>';
        echo "</thead>\n";
        echo "<tbody>\n";
        foreach ($res as $row) {
            foreach ($ignore_cols as $ignore) {
                unset($row[$ignore]);
            }
            echo '<tr>';
            foreach ($row as $name => $val) {
                if (! $val) $val = '&nbsp;';
                if ($name == 'Name') {
                    $suf = '';
                    if (!empty($_GET['search'])) {
                        $suf = '&search=' . $_GET['search'];
                    }
                    $val = Html::anchor('dbtools/struct/'.$val.$suf, $val);
                }
                echo '<td>', $val, '</td>';
            }
            echo '</tr>';
        }
        echo "</tbody>\n";
        echo '</table>';

        $res->closeCursor();

        $this->template('Database structure');
    }


    /**
    * Splits a set of SQL queries into individual queries
    **/
    private function splitSql($all)
    {
        $all = preg_replace('/^\s*--.*$/m', '', $all);

        $queries = array();

        $offset = 0;
        $length = strlen($all);
        $query = '';
        $quote = '';

        while ($offset < $length) {
            // Search for end-of-statement
            if ($quote == '' and $all[$offset] == ';') {
                $queries[] = $query;
                $query = '';
                $offset++;
                continue;
            }

            if ($quote != '' and $all[$offset] == '\\') {
                $query .= '\\';
                $offset++;
                $query .= $all[$offset];
                $offset++;
                continue;
            }

            if ($all[$offset] == "'") {
                if ($quote == "'") {
                    $quote = '';
                } else if ($quote == '') {
                    $quote = "'";
                }
            }

            if ($all[$offset] == '"') {
                if ($quote == '"') {
                    $quote = '';
                } else if ($quote == '') {
                    $quote = '"';
                }
            }

            $query .= $all[$offset];
            $offset++;
        }

        if ($query) $queries[] = $query;

        return $queries;
    }


    /**
    * Allows the user to execute SQL queries
    **/
    public function sql()
    {
        Needs::fileGroup('sprout/dbtools_sql');
        $out = '';

        $vars = [0 => []];
        $binds = [];
        if (@is_array($_POST['vars'])) {
            $idx = 0;
            foreach ($_POST['vars'] as $var) {
                if (empty($var['key']) and (!isset($var['val']) or $var['val'] === '')) continue;

                $key = (string) @$var['key'];
                $val = (string) @$var['val'];
                if ($key) {
                    $binds[$key] = $val;
                } else {
                    $binds[] = $val;
                }
                $vars[$idx]['key'] = $key;
                $vars[$idx]['val'] = $val;
                ++$idx;
            }
        }


        // Split up the queries
        if (isset($_POST['sql'])) {
            Csrf::checkOrDie();

            $queries = $this->splitSql($_POST['sql']);

            // Execute the queries
            foreach ($queries as $q) {
                $q = trim($q);
                $res = false;

                if ($q == '') continue;

                $out .= "<div class=\"sql-block\">\n";

                $out .= '<pre class="sql">' . Enc::html($q) . '</pre>';

                if (!empty($_POST['profile'])) {
                    Pdb::query("SET profiling=1", [], 'count');
                }

                $bind_subset = Pdb::getBindSubset($q, $binds);
                try {
                    $res = Pdb::query($q, $bind_subset, 'pdo');
                } catch (QueryException $ex) {
                    $out .= '<ul class="messages all-type-error"><li class="error">';
                    $out .= nl2br(Enc::html($ex->getMessage()));

                    // If a DROP TABLE query fails due to a foreign key constraint, list the constraining columns
                    if ($ex->state == 23000) {
                        $matches = [];
                        if (preg_match('/^DROP\s+TABLE\s+~([a-z0-9_]+)$/i', $q, $matches)) {
                            $deps = Pdb::getDependentKeys($matches[1]);
                            if (count($deps) > 0) {
                                $out .= '<p style="margin-bottom: 0;">The following columns link to the specified table:</p>';
                                $out .= '<p style="padding-left: 30px; margin: 0;">';
                                $out = '';
                                foreach ($deps as $dep) {
                                    $out .= Enc::html("{$dep['table']}.{$dep['column']}") . '<br>';
                                }
                                $out .= substr($out, 0, -4);
                                $out .= '</p>';
                            }
                        }
                    }
                    $out .= '</li></ul>';
                }

                if (!empty($_POST['profile'])) {
                    Pdb::query("SET profiling=0", [], 'count');
                }

                if (! $res) continue;

                if($res->rowCount() === 1) {
                    $out .= '<p class="row-count">' . $res->rowCount() . ' row</p>';
                } else {
                    $out .= '<p class="row-count">' . $res->rowCount() . ' rows</p>';
                }

                ob_start();
                $this->outputSqlResultset($res);
                $out .= ob_get_clean();

                $res->closeCursor();

                if (!empty($_POST['explain'])) {
                    $q = "EXPLAIN {$q}";
                    $res = Pdb::query($q, [], 'pdo');

                    ob_start();
                    $this->outputSqlResultset($res);
                    $out .= ob_get_clean();

                    $res->closeCursor();
                }
                $out .= "</div>\n";
            }

            if (!empty($queries) and count($queries) > 0) {
                Notification::confirm(sprintf('Executed %u %s. Scroll down for results', count($queries), Inflector::singular('queries', count($queries))));
            }

            // Show profiling info
            if (!empty($_POST['profile'])) {

                $q = "SHOW PROFILES";
                $res = Pdb::query($q, [], 'arr');

                foreach ($res as $row) {
                    $q = "SHOW PROFILE FOR QUERY {$row['Query_ID']}";
                    $res2 = Pdb::query($q, [], 'pdo');

                    $out .= "<h3>Query #{$row['Query_ID']}; total duration: {$row['Duration']}</h3>";
                    $out .= '<pre class="sql">' . Enc::html($row['Query']) . '</pre>';

                    ob_start();
                    $this->outputSqlResultset($res2);
                    $out .= ob_get_clean();

                    $res2->closeCursor();
                }
            }
        }

        $out .= '</div>';

        $res = Pdb::query('SHOW TABLES', [], 'col');
        $tables = [];
        foreach ($res as $row) {
            $tables[] = preg_replace('/^' . preg_quote(Pdb::prefix()) . '/', '~', $row);
        }

        $view = new View('sprout/dbtools/sql');
        $view->vars = $vars;
        $view->tables = $tables;
        $view->results = $out;

        echo $view->render();
        $this->template('SQL query');
    }


    public function sqlcsv()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        if (empty($_POST['sql'])) {
            Url::redirect('dbtools/sql');
        }

        $rows = Pdb::q($_POST['sql'], [], 'pdo');
        $csv = QueryTo::csv($rows);
        $rows->closeCursor();

        if (!$csv) {
            echo "CSV generation failed";
            return;
        }

        header('Content-type: text/csv');
        header('Content-disposition: attachment; filename="sql.csv"');
        echo $csv;
    }


    public function ajaxTableDefn($table)
    {
        $table = trim($table, '~');
        try {
            Pdb::validateIdentifier($table);
        } catch (InvalidArgumentException $ex) {
            return;
        }

        // Get FKs and group them by columns
        $fks = [];
        $fk_defs = Pdb::getForeignKeys($table);
        foreach ($fk_defs as $fk) {
            $fks[$fk['source_column']][] = "{$fk['target_table']}.{$fk['target_column']}";
        }

        $allowed_keys = ['Field', 'Type', 'Null', 'Key', 'Default', 'Collation'];
        $raw_res = Pdb::q("SHOW FULL COLUMNS FROM ~{$table}", [], 'pdo');
        $res = [];
        foreach ($raw_res as $raw_row) {
            $row = [];
            foreach ($allowed_keys as $key) {
                $row[$key] = $raw_row[$key];
            }
            if (isset($fks[$row['Field']])) {
                $row['FK'] = implode(', ', $fks[$row['Field']]);
            } else {
                $row['FK'] = '';
            }
            $res[] = $row;
        }
        $raw_res->closeCursor();

        echo Json::out($res);
    }


    /**
    * Import database tables from a .sql file
    **/
    public function importTables()
    {
        if (class_exists('ZipArchive')) {
            echo '<p><em>Accepts raw SQL files, and zip archives.</em></p>';
        } else {
            echo '<p><em>Accepts raw SQL files.</em></p>';
        }

        echo '<form action="SITE/dbtools/importSave" method="post" enctype="multipart/form-data">';
        echo Csrf::token();
        Form::nextFieldDetails('File', true);
        echo Form::upload('filename');
        echo '<div class="action-bar"><button type="submit" class="button icon-after icon-file_upload">Upload file</button></div>';
        echo '</form>';

        $this->template('Import tables');
    }


    public function importSave()
    {
        Csrf::checkOrDie();

        $ext = strtolower(File::getExt($_FILES['filename']['name']));

        $valid_exts = ['sql'];
        if (class_exists('ZipArchive')) {
            $valid_exts[] = 'zip';
        }

        if (!in_array($ext, $valid_exts)) {
            echo "Invalid file type, suported types: " . implode(', ', $valid_exts);
            return;
        }

        // Determine temp filename
        $timestamp = time();
        $tempname = "dbtools_import_{$timestamp}.{$ext}";

        $res = @copy($_FILES['filename']['tmp_name'], APPPATH . 'temp/' . $tempname);
        if (! $res) {
            echo 'Unable to copy file to temporary directory';
            return;
        }

        Url::redirect('dbtools/importOptions?tempname=' . Enc::url($tempname));
    }



    public function importOptions()
    {
        echo '<p>Uploaded file: <code>', Enc::html($_GET['tempname']), '</code></p>';

        $tempname = APPPATH . 'temp/' . $_GET['tempname'];
        if (File::getExt($tempname) == 'zip') {
            $za = new ZipArchive();
            $za->open($tempname);
            $sql = 0;
            for ($i = 0; $i < $za->numFiles; $i++) {
                $info = $za->statIndex($i);
                if (preg_match('/\.sql/', $info['name'])) {
                    $sql++;
                }
            }
            $za->close();

            echo "<p>It's a zip file containing {$sql} SQL files.</p>";

        } else if (File::getExt($tempname) == 'sql') {
            echo "<p>It's a single SQL file.</p>";
        }


        echo '<form action="SITE/dbtools/importAction" method="post" target="process" onsubmit="$(\'iframe\').show(); $(this).find(\'.action-bar\').remove();">';
        echo Csrf::token();
        echo '    <input type="hidden" name="tempname" value="', Enc::html($_GET['tempname']), '">';
        echo '    <div class="action-bar"><button type="submit" class="button icon-before icon-check">Process file</button></div>';
        echo '</form>';

        echo '<iframe name="process" style="border: 1px #999 dashed; margin: 30px 0; width: 700px; height: 300px; display: none;"></iframe>';

        $this->template('Import tables');
    }

    /**
    * Action for importing tables
    **/
    public function importAction()
    {
        Csrf::checkOrDie();
        Kohana::closeBuffers();
        set_time_limit(0);
        echo '<style>body { font-size: 11px; font-family: sans-serif; } p { margin: 0; }</style>';


        $tempname = APPPATH . 'temp/' . $_POST['tempname'];
        echo "<p>Processing: '{$tempname}'.</p>";


        // Rip SQL files out of ZIP file
        $sql_files = array();
        if (File::getExt($tempname) == 'zip') {
            $za = new ZipArchive();
            $za->open($tempname);

            for ($i = 0; $i < $za->numFiles; $i++) {
                $info = $za->statIndex($i);

                if (preg_match('/\.sql/', $info['name'])) {
                    echo "<p>Found sql file in zip: '{$info['name']}'.</p>";
                    $sql_files[] = $za->getFromIndex($i);
                }
            }

            $za->close();
            unset($za);

        } else if (File::getExt($tempname) == 'sql') {
            echo "<p>Found sql file: '{$tempname}'.</p>";
            $sql_files[] = file_get_contents($tempname);
        }

        unlink($tempname);


        echo '<p>Processing ' . count($sql_files) . ' sql file(s).</p>';
        @ob_flush(); @flush(); usleep(100 * 1000);

        // Process files
        $idx = 0;
        foreach ($sql_files as $file) {
            $idx++;
            echo '<p>Processing file # ', $idx, '.</p>';
            @ob_flush(); @flush(); usleep(100 * 1000);

            $queries = $this->splitSql($file);

            echo '<p>File # ', $idx, ' contains ', count($queries), ' queries.</p>';
            @ob_flush(); @flush(); usleep(100 * 1000);

            Pdb::q("SET FOREIGN_KEY_CHECKS=0", [], 'null');

            $qidx = 0;
            foreach ($queries as $q) {
                $q = trim($q);
                if ($q == '') continue;

                try {
                    Pdb::q($q, [], 'count');
                } catch (Exception $ex) {
                    echo '<p>Failed query: <code>' . Enc::html($q) . '</code>, exception: ',
                        Enc::html($ex->getMessage()), '</p>';
                }

                unset($q);

                $qidx++;
                if ($qidx % 1000 == 0) {
                    echo '<p>Done ', $qidx, ' queries.</p>';
                    @ob_flush(); @flush(); usleep(100 * 1000);
                }
            }

            unset($queries);

            Pdb::q("SET FOREIGN_KEY_CHECKS=1", [], 'null');

            echo '<p>File # ', $idx, ' finished.</p>';
            @ob_flush(); @flush(); usleep(100 * 1000);
        }

        echo '<p>Import complete, running sync...</p>';

        $sync = new DatabaseSync(true);
        $sync->loadStandardXmlFiles();
        $sync->sanityCheck();

        if ($sync->hasLoadErrors()) {
            echo $sync->getLoadErrorsHtml();
            exit;
        }

        echo $sync->updateDatabase();

        echo '<p>Sync complete</p>';

        exit;
    }


    /**
    * Run a series of self-tests to ensure everything is configured correctly
    **/
    public function launchChecks()
    {
        $results = LaunchChecks::runTests();

        echo '<style>';
        echo '.status--okay { color: #005306; }';
        echo '.status--warning { color: #C26600; }';
        echo '.status--error { color: #B20000; }';
        echo '</style>';

        $itemlist = new Itemlist();
        $itemlist->items = $results;
        $itemlist->main_columns = [
            'Check' => 'check',
            'Skin' => 'skin',
            'Result' => 'result',
            'Message' => 'message',
        ];
        $itemlist->setRowClassesFunc(function($row){
            return 'status--' . $row['result'];
        });
        echo $itemlist->render();

        $this->template('Launch checks');
    }


    /**
    * Returns a list of file types which can be indexed for fulltext search
    **/
    public function fileTypesIndexingSupport()
    {
        $exts = array(
            'txt' => '',
            'csv' => '',
            'pdf' => 'pdftotext',
            'doc' => 'antiword',
            'docx' => 'perl',
            'odt' => 'odt2txt',
            'xls' => 'xls2csv'
        );

        $out = '<table class="main-list">';
        $out .= '<thead><tr><th>Format</th><th>Supported?</th></thead>';
        $out .= '<tbody>';
        foreach ($exts as $e => $pkg) {
            $out .= '<tr>';
            $out .= '<td>' . $e . '</td>';

            if (FileIndexing::isExtSupported($e)) {
                $out .= '<td>Yes</td>';
            } else {
                $out .= '<td><span style="color: #900;">No.</span> Please install "' . $pkg . '".</td>';
            }

            $out .= '</tr>';
        }
        $out .= '</tbody>';
        $out .= '</table>';

        echo $out;
        $this->template('File types with indexing support');
    }


    /**
    * Export tables to an SQL file
    **/
    public function exportTables()
    {
        ?>
        <script type="text/javascript">
        $(document).ready(function() {
            $('a.next-toggle').click(function() {
                $(this).next().toggle();
                return false;
            });

            $('a.next-toggle').next().hide();
        });
        </script>
        <?php


        // Show a list of tables
        $q = "SHOW TABLE STATUS";
        $db_tables = Pdb::q($q, [], 'arr');

        ?>
        <p><a class="preview" id="select-all-none" href="#">Select all/none</a></p>
        <script type="text/javascript">
        $('#select-all-none').click(function(){
            var all_checked = true;
            $("input[name*='tables[']").each(function() {
                if (!$(this).attr('checked')) all_checked = false;
            });
            if (all_checked) {
                $("input[name*='tables[']").each(function() {
                    $(this).removeAttr('checked');
                });
            } else {
                $("input[name*='tables[']").each(function() {
                    $(this).attr('checked', 'checked');
                });
            }
            return false;
        });
        </script>
        <?php

        echo '<form action="SITE/dbtools/exportAction" method="post">';
        echo Csrf::token();
        echo '<table class="main-list main-list-no-js">';
        echo '<col width="1"><col width="230"><col width="40"><col width="40"><col width="200"><col width="200"><col width="1"><col width="1"><col width="75">';
        echo '<thead><tr><th style="width: 1px;">&nbsp;</th>';
        echo '<th>Name</th><th>Rows</th><th>Size</th><th colspan="2">Options</th><th>Drop</th><th>Structure</th><th>Data</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        $idx = 0;
        $prefix = Pdb::prefix();
        foreach ($db_tables as $table) {
            $column_options = '<p>No options yet.</p>';

            $where_options = '<p><b>Where clause:</b>';
            $where_options .= "<br><input type=\"text\" name=\"where[{$idx}]\"></p>";

            $type_options = "<select name=\"data[{$idx}]\">";
            $type_options .= '<option value="' . ExportTableSQL::DATA_INSERT . '">Insert</option>';
            $type_options .= '<option value="' . ExportTableSQL::DATA_BOTH . '">Insert...update</option>';
            $type_options .= '<option value="' . ExportTableSQL::DATA_UPDATE .'">Update</option>';
            $type_options .= '<option value="' . ExportTableSQL::DATA_CSV . '">CSV file</option>';
            $type_options .= '<option value="' . ExportTableSQL::DATA_NONE . '">None</option>';
            $type_options .= '</select>';

            $checked = '';
            if (strpos($table['Name'], $prefix) === 0) $checked = ' checked';

            echo '<tr>';
            echo "<td><input type=\"checkbox\" name=\"tables[{$idx}]\" value=\"{$table['Name']}\"{$checked}></td>";
            echo "<td>{$table['Name']}</td>";
            echo "<td>{$table['Rows']}</td>";
            echo "<td>{$table['Data_length']}</td>";
            echo '<td><a href="#" class="next-toggle">Columns</a><div>' . $column_options . '</div></td>';
            echo '<td><a href="#" class="next-toggle">Where clause</a><div>' . $where_options . '</div></td>';
            echo "<td><input type=\"checkbox\" name=\"drop[{$idx}]\" value=\"1\" checked></td>";
            echo "<td><input type=\"checkbox\" name=\"structure[{$idx}]\" value=\"1\" checked></td>";
            echo '<td>' . $type_options . '</td>';
            echo '</tr>';

            $idx++;
        }
        echo '</tbody>';
        echo '</table>';

        echo '<p><input type="checkbox" name="split_table" value="1"> Split the export per table.</p>';
        echo '<p><input type="checkbox" name="split_size" value="1" checked> Split the export into chunks no bigger than <input type="text" name="split_amount" value="8m">. <small>(prefixes: k, m, g)</small></p>';

        echo '<p><input type="checkbox" name="compress" value="1" checked> Compress the file into a zip archive.</p>';

        echo '<p><b>DBMS:</b> &nbsp; <select name="dbms" style="width: 100px;"><option>MySQL<option>SQLite</select></p>';

        echo '<div class="action-bar"><button type="submit" class="button icon-after icon-save">Export</button></div>';
        echo '</form>';


        $this->template('Export tables');
    }

    /**
    * Does the actual export
    *
    * @post tables Array of tables
    * @post dbms DBMS - MySQL or SQLite
    * @post split_tables Split the export per table
    * @post split_size Split the export by size
    * @post split_amount Split the export by size - size in kb, mb or gb
    * @post compress Should the data be compressed
    * @return void Outputs HTML directly
    **/
    public function exportAction()
    {
        Csrf::checkOrDie();

        if (empty($_POST['tables'])) {
            throw new InvalidArgumentException('No tables specified');
        }

        $temp_filename = 'temp/export-' . time() . '-';

        $class = 'Sprout\Helpers\ExportDBMS_' . $_POST['dbms'];
        $dbms = Sprout::instance($class);

        $export = new Export();
        $export->setFilenamePrefix(APPPATH . $temp_filename);
        $export->setDbms($dbms);

        if (!empty($_POST['split_table'])) {
            $export->split_table = true;
        }

        $matches = array();
        if (
            isset($_POST['split_size'])
            and isset($_POST['split_amount'])
            and preg_match('/([0-9]+)\s?([kmg])/', $_POST['split_amount'], $matches)
        ) {
            if ($matches[2] == 'k') $amt = $matches[1] * 1000;
            if ($matches[2] == 'm') $amt = $matches[1] * 1000 * 1000;
            if ($matches[2] == 'g') $amt = $matches[1] * 1000 * 1000 * 1000;
            $export->split_size = $amt;
        }

        foreach ($_POST['tables'] as $idx => $table_name) {
            $table = new ExportTableSQL();
            $table->name = $table_name;
            $table->drop = (bool)@$_POST['drop'][$idx];
            $table->structure = (bool)@$_POST['structure'][$idx];
            $table->data = $_POST['data'][$idx];
            $table->where = $_POST['where'][$idx];

            $export->addTable($table);
        }

        $export->exportSql();

        if (!empty($_POST['compress'])) {
            $name = Kohana::config('sprout.site_title');
            $name = str_replace(' ', '_', strtolower($name));
            $name = preg_replace('/[^a-z_]/', '', $name);

            $name = 'sql_' . date('Y-m-d') . '_' . $name . '.zip';
            $export->buildArchive($name);
        }

        $files = $export->getGeneratedFiles();
        foreach ($files as $f) {
            $f_url = basename($temp_filename . $f);
            echo "<p><a href=\"SITE/dbtools/gettempfile/export-database/{$f_url}/{$f}\">{$f}</a></p>";
        }

        $this->template('Export tables');
    }


    /**
    * Imports a files into the cms
    **/
    public function importFiles()
    {
        if (! class_exists('ZipArchive')) {
            echo '<p><em>ARGH! No ZIP support!</em></p>';
            return;
        }

        echo '<form action="SITE/dbtools/importFileAction" method="post" enctype="multipart/form-data">';
        echo Csrf::token();
        Form::nextFieldDetails('File', true);
        echo Form::upload('filename');
        echo '<div class="action-bar"><button type="submit" class="button icon-after icon-file_upload">Import files</button></div>';
        echo '</form>';

        $this->template('Import files');
    }

    /**
    * Action for importing files
    **/
    public function importFileAction()
    {
        Csrf::checkOrDie();

        if (! class_exists('ZipArchive')) {
            echo '<p><em>ARGH! No ZIP support!</em></p>';
            return;
        }

        if (empty($_FILES['filename']) or $_FILES['filename']['error'] !== UPLOAD_ERR_OK) {
            Notification::error('There was an error uploading your file, please try again.');
            Url::redirect('dbtools/importFiles');
        }

        if (File::getExt($_FILES['filename']['name']) !== 'zip') {
            Notification::error('Invalid file type; only .zip files are supported.');
            Url::redirect('dbtools/importFiles');
        }

        copy($_FILES['filename']['tmp_name'], APPPATH . 'temp/import.zip');

        $za = new ZipArchive();
        $za->open(APPPATH . 'temp/import.zip');

        // Check for disallowed file types
        $invalid = [];
        for ($i = 0; $i < $za->numFiles; $i++) {
            $filename = $za->getNameIndex($i);
            if (!FileUpload::checkFilename($filename)) {
                $invalid[] = $filename;
            }
        }

        // If there are any disallowed files in the ZIP, then stop
        if (count($invalid)) {
            $za->close();
            unlink(APPPATH . 'temp/import.zip');
            header('Content-type: text/plain');
            echo "DISALLOWED FILES FOUND:\n - ", implode("\n - ", $invalid);
            die(1);
        }

        for ($i = 0; $i < $za->numFiles; $i++) {
            @File::putString($za->getNameIndex($i), $za->getFromIndex($i));
        }

        $za->close();

        unlink(APPPATH . 'temp/import.zip');


        echo '<p>Done.</p>';

        $this->template('Import files');
    }


    /**
    * Allows files to be downloaded from the temporary directory
    *
    * Only certain files from the temp directory can be downloaded.
    * Each 'source' has a regex which is used to restrict downloads to only that particular type of file
    *
    * @param string $source The source of the file, one of 'export-database', 'export-files', 'module-builder'
    * @param string $tempfile File name in the temporary directory
    * @param string $orig Alternate name to use when providing file to browser
    **/
    public function gettempfile($source, $tempfile, $orig = null)
    {
        AdminAuth::checkLogin();

        $validation_regexes = [
            'export-database' => '/^export-[0-9]+-(?:sql_[-0-9]+_.+\.zip|.+\.sql)$/',
            'export-files' => '/^files_[-0-9]+_.+\.zip$/',
            'module-builder' => '/^mt_[0-9]+\.tar\.bz2$/',
        ];

        if (!isset($validation_regexes[$source])) {
            throw new InvalidArgumentException('Invalid source specified');
        }
        if (!preg_match($validation_regexes[$source], $tempfile)) {
            throw new InvalidArgumentException('Invalid tempfile specified');
        }

        $disk_filename = APPPATH . 'temp/' . $tempfile;
        if (! file_exists($disk_filename)) {
            throw new Kohana_404_Exception($tempfile);
        }

        // If no original name, use the disk filename
        if (! $orig) {
            $orig = $tempfile;
        }
        $orig = addslashes($orig);

        // Determine mimetype
        $parts = explode('.', $orig);
        $ext = array_pop($parts);
        $mimetypes = array(
            'txt' => 'text/plain',
            'sql' => 'text/plain',
            'zip' => 'application/zip',
            'bz2' => 'application/bzip2',
            'gz' => 'application/gzip',
        );
        if (! $mime = $mimetypes[$ext]) {
            $mime = 'application/octet-stream';
        }

        // Make sure there's no buffering, or large files will exhaust PHP's memory allocation
        while (count(ob_list_handlers()) > 0) {
            ob_end_clean();
        }


        header ("Content-type: {$mime}");
        header ('Content-length: ' . filesize($disk_filename));
        header ("Content-disposition: attachment; filename=\"{$orig}\"");
        readfile($disk_filename);
    }


    /**
    * UI to export all files
    **/
    public function exportFiles()
    {
        $files = File::glob('*');

        echo "<p>Found " . count($files) . " files.\n";

        echo '<p>NOTE: Exports of many files may take a long time and/or fail.</p>';

        echo '<form action="dbtools/exportFilesAction" method="post">';
        echo Csrf::token();
        echo '<div class="action-bar"><button type="submit" class="button">Export files</button></div>';
        echo '</form>';

        $this->template('Export files');
    }


    /**
    * Action to export all files
    **/
    public function exportFilesAction()
    {
        Csrf::checkOrDie();

        $files = File::glob('*');

        echo "<p>Found " . count($files) . " files.\n";

        // Prep archive
        $arch = new Archive('zip');
        foreach ($files as $filename) {
            $temp = File::createLocalCopy($filename);
            if (!$temp) continue;
            $arch->add($temp, $filename);
            $temp_names[] = $temp;
        }

        // Build file name
        $name = Kohana::config('sprout.site_title');
        $name = str_replace(' ', '_', strtolower($name));
        $name = preg_replace('/[^a-z_]/', '', $name);
        $name = 'files_' . date('Y-m-d') . '_' . $name . '.zip';

        // Save archive
        echo "<p>Saving archive.\n";
        $arch->save(APPPATH . 'temp/' . $name);

        // Nuke temps
        foreach ($temp_names as $temp) {
            File::cleanupLocalCopy($temp);
        }

        // Offer download link
        echo "<div class=\"action-bar\"><a href=\"SITE/dbtools/gettempfile/export-files/{$name}\" class=\"button icon-after icon-save\">Download: {$name}</a></div>";

        $this->template('Export files');
    }


    /**
    * Edit the $_SESSION
    **/
    public function sessionEditor()
    {
        Session::instance();

        echo '<style>';
        echo 'div.val { margin: 0 20px 15px; padding: 5px; clear: right; background: rgba(0,0,0,0.1); border-radius: 3px; }';
        echo 'div.val div.val:last-child { margin-bottom: 6px; }';
        echo 'div.val p { margin: 0; padding: 0; cursor: default; line-height: 32px; }';
        echo 'div.val h3 { margin: 0 0 5px 0; padding: 0; }';
        echo 'div.val form { margin: 0; padding: 0; }';
        echo 'div.val:hover { background: rgba(0,0,0,0.3); }';
        echo 'div.val:hover p { color: #fff; }';
        echo 'div.val:hover h3 { color: #fff; }';
        echo 'div.val button.right { margin: 0; }';
        echo '</style>';

        echo '<h3>$_SESSION</h3>';
        $this->sessionLoop($_SESSION, 0, []);

        $this->template('Session editor');
    }

    /**
    * List items in an array, along with the tools buttons
    **/
    private function sessionLoop(&$a, $depth, $keys)
    {
        if ($depth > 50) {
            echo '<p>TOO DEEP!</p>';
            return;
        }

        foreach ($a as $key => $val) {
            $this_keys = $keys;
            $this_keys[] = $key;

            // Can't delete protected/private members
            $can_delete = true;
            foreach ($this_keys as $k) {
                if (strpos($k, '->') !== false) {
                    $can_delete = false;
                    break;
                };
            }

            echo '<div class="val -clearfix">';
            if ($can_delete) {
                echo '<form action="SITE/dbtools/sessionEditorAction" method="post">';
                foreach ($this_keys as $k) {
                    echo '<input type="hidden" name="key[]" value="' . Enc::url($k) . '">';
                }
                echo '<button type="submit" value="delete" name="do" class="button right button-orange button-small icon-after icon-close">Delete</button>';
                echo '</form>';
            }

            if (is_array($val)) {
                echo '<h3>' . Enc::html($key) . '</h3>';
                if (empty($val)) {
                    echo '<div class="val"><p><i>(empty array)</i></p></div>';
                } else {
                    $this->sessionLoop($val, $depth + 1, $this_keys);
                }

            } else if (is_object($val)) {
                echo '<h3>' . Enc::html($key) . ' <i>' . get_class($val);
                $parent = $val;
                while ($parent = get_parent_class($parent)) {
                    echo " ex $parent";
                }
                $implements = class_implements($val);
                $i = 0;
                foreach ($implements as $interface) {
                    if (++$i == 1) {
                        echo ' impl ';
                    } else {
                        echo ', ';
                    }
                    echo $interface;
                }
                echo '</i></h3>';
                $obj_pub_members = array_keys(get_object_vars($val));
                $obj_all_members = (array) $val;

                $members = array();
                foreach ($obj_all_members as $mem_key => $mem_val) {
                    // N.B. This doesn't work (it returns an array):
                    // $new_key = preg_replace('/[^\0]*\0+/', '', $mem_key);
                    // Undocumented PHP behaviour; assume \0 is interpreted
                    // in $pattern as it is in $replacement
                    $null_pos = strrpos($mem_key, "\0");
                    if ($null_pos !== false) {
                        $new_key = substr($mem_key, $null_pos + 1);
                    } else {
                        $new_key = $mem_key;
                    }
                    $new_key = str_replace('*', '', $new_key);

                    if (in_array($new_key, $obj_pub_members)) {
                        $members["->{$new_key}"] = $mem_val;
                        continue;
                    }

                    $getter = false;
                    $getters = array('get_' . $new_key, 'get' . $new_key);
                    foreach ($getters as $fn) {
                        if (method_exists($val, $fn)) {
                            $getter = $fn;
                            break;
                        }
                    }
                    if (!$getter) continue;

                    $members["->{$getter}()"] = $mem_val;
                }
                $this->sessionLoop($members, $depth + 1, $this_keys);

            } else if (gettype($val) == 'object') {
                // e.g. __PHP_Incomplete_Class objects
                echo '<p>' . $key . ' = <i>OBJECT OF UNKNOWN CLASS</i></p>';
            } else {
                echo '<p>' . $key . ' = ' . $val . '</p>';
            }

            echo '</div>';

            unset ($this_keys, $key, $val);
        }
    }

    /**
    * Session Editor Action
    **/
    public function sessionEditorAction()
    {
        Session::instance();

        $_POST['do'] = preg_replace('/[^a-z]/', '', strtolower($_POST['do']));

        // Find the array & key which will do the action
        $final_key = array_pop($_POST['key']);
        $final_array = &$_SESSION;
        foreach ($_POST['key'] as $key) {
            $final_array = &$final_array[$key];
        }

        // Do the action
        switch ($_POST['do']) {
            case 'delete':
                unset ($final_array[$final_key]);
                break;
        }

        Url::redirect('dbtools/sessionEditor');
    }


    /**
    * For a given field name, a type to use which is better than VARCHAR(200)
    **/
    private static $module_builder_type_guess = array(
        'active' => 'TINYINT UNSIGNED',
        'amount' => 'DECIMAL(6,2)',
        'data' => 'BLOB',
        'description' => 'TEXT',
        'email' => 'VARCHAR(150)',
        'fax' => 'VARCHAR(20)',
        'filename' => 'VARCHAR(255)',
        'first_name' => 'VARCHAR(100)',
        'image' => 'VARCHAR(255)',
        'last_name' => 'VARCHAR(100)',
        'mobile' => 'VARCHAR(20)',
        'notes' => 'TEXT',
        'phone' => 'VARCHAR(20)',
        'photo' => 'VARCHAR(255)',
        'postcode' => 'VARCHAR(10)',
        'price' => 'DECIMAL(6,2)',
        'state' => 'VARCHAR(50)',
        'suburb' => 'VARCHAR(50)',
        'text' => 'TEXT',
        'visible' => 'TINYINT UNSIGNED',
    );


    /**
    * Generates blank modules
    **/
    public function moduleBuilder()
    {
        $temp = APPPATH . 'temp';

        // Generate list of modules
        $mod_dir = DOCROOT . '/modules/';
        $modules = scandir($mod_dir);
        foreach ($modules as $key => $mod) {
            if ($mod[0] == '.' or !is_dir($mod_dir . $mod)) unset($modules[$key]);
        }

        // Prep array for form data
        $modules_list = [];
        foreach($modules as $mod) {
            $modules_list[$mod] = $mod;
        }

        $view = new View('sprout/dbtools/module_builder');
        $view->temp_writeable = (is_dir($temp) and is_writeable($temp));
        $view->bad_fields = array('id', 'name', 'active', 'date_added', 'date_modified', 'record_order');
        $view->modules = $modules_list;
        echo $view->render();

        $this->template('Module builder');
    }


    public function moduleBuilderAction()
    {
        if (empty($_POST['module_author'])) {
            throw new InvalidArgumentException('Module author not specified');
        }
        if (empty($_POST['module_name'])) {
            throw new InvalidArgumentException('Module name not specified');
        } else {
            $module_name = trim($_POST['module_name']);
            if (!preg_match('/^([A-Z][a-z0-9]+)+$/', $module_name)) {
                throw new InvalidArgumentException('Invalid module name');
            }
        }

        // Name => type
        $inbuilt_fields = [
            'id' => 'INT UNSIGNED',
            'name' => 'VARCHAR(60)',
            'active' => 'TINYINT UNSIGNED',
            'date_added' => 'DATETIME',
            'date_modified' => 'DATETIME',
        ];
        if (in_array($_POST['module_type'], ['list', 'tree'])) {
            $inbuilt_fields['record_order'] = 'INT UNSIGNED';
        }

        $fields = array_keys($inbuilt_fields);
        if ($_POST['module_type'] == 'tree') $fields[] = 'parent_id';
        foreach (explode("\n", $_POST['fields']) as $field) {
            $field = trim($field);
            if ($field == '') continue;
            if (in_array($field, $fields)) continue;
            $fields[] = $field;
        }

        $fields_xml = array();
        $fields_manual = array();

        $t = "    ";
        foreach ($fields as $f) {
            if (!in_array($f, ['name']) and isset($inbuilt_fields[$f])) continue;

            $l = ucfirst(str_replace('_', ' ', $f));

            // Try to guess a name using a basic algorithm
            if (isset($inbuilt_fields[$f])) {
                $type = $inbuilt_fields[$f];
            } else if (preg_match('!_id$!', $f)) {
                $type = 'INT UNSIGNED';
                $l = substr($l, 0, -3);
            } else {
                $type = @self::$module_builder_type_guess[$f];
            }

            // If it all fails, fall back to something generic
            if (! $type) {
                $type = 'VARCHAR(100)';
            }

            $attrs = 'allownull="0" ';

            if ($f == 'parent_id') {
                $input_method = 'Fb::dropdown';
                $items = "{\"query\": \"SELECT id, name FROM ~{$_POST['pname']} WHERE parent_id = 0 ORDER BY record_order\"}";
            } else {
                $input_method = 'Fb::text';
                $items = "{}";
            }

            $json = "{$t}{$t}{\n" .
                "{$t}{$t}{$t}\"field\": {\n" .
                "{$t}{$t}{$t}{$t}\"name\": \"{$f}\",\n" .
                "{$t}{$t}{$t}{$t}\"label\": \"{$l}\",\n" .
                "{$t}{$t}{$t}{$t}\"display\": \"{$input_method}\",\n" .
                "{$t}{$t}{$t}{$t}\"items\": {$items},\n" .
                "{$t}{$t}{$t}{$t}\"required\": false,\n" .
                "{$t}{$t}{$t}{$t}\"validate\": [\n";

            // Use length as basic validation where possible, allowing an extra char for a decimal point if relevant
            $matches = [];
            if (preg_match('/\([0-9]+(\s*,)?/', $type, $matches)) {
                $field_len = (int) substr($matches[0], 1);
                if (!empty($matches[1])) ++$field_len;
                $json .= "{$t}{$t}{$t}{$t}{$t}{\"func\": \"Validity::length\", \"args\": [0, {$field_len}]}\n";
            }
            $json .= "{$t}{$t}{$t}{$t}]\n" .
                "{$t}{$t}{$t}}\n" .
                "{$t}{$t}}";
            $fields_json[] = $json;

            if (isset($inbuilt_fields[$f])) continue;

            $fields_xml[] = "{$t}{$t}<column name=\"{$f}\" type=\"{$type}\" {$attrs}/>";
            $fields_manual[] = "<p><b>{$l}</b>\n<br><!-- description goes here --></p>\n";
        }

        $possible_main_fields = [
            'name',
            'email',
            'first_name',
            'last_name',
            'price',
            'suburb',
            'state',
            'visible',
        ];

        $fields_main = [];
        foreach ($possible_main_fields as $ind) {
            if (in_array($ind, $fields)) {
                $label = ucfirst(str_replace('_', ' ', $ind));
                if (in_array($ind, ['visible'])) {
                    $field = "[new ColModifierBinary(), '{$ind}']";
                } else {
                    $field = "'{$ind}'";
                }
                $fields_main[] = "'{$label}' => {$field},";
            }
        }

        $_POST['_fields_xml'] = rtrim(implode("\n", $fields_xml));
        $_POST['_fields_json'] = implode(",\n", $fields_json);
        $_POST['_fields_manual'] = rtrim(implode("\n", $fields_manual));
        $_POST['_fields_main'] = implode("\n{$t}{$t}{$t}", $fields_main);


        $temp = APPPATH . 'temp';
        $template_dir = APPPATH . 'module_template/' . $_POST['module_type'];

        shell_exec("rm -f {$temp}/mt_*.tar.bz2");
        if (!file_exists("{$temp}/{$module_name}") and !@mkdir("{$temp}/{$module_name}")) {
            echo "<ul class=\"messages\"><li class=\"error\">Failed to create temp directory {$module_name}</li></ul>";
            $this->template('Module builder');
            return;
        }

        $dir_iterator = new RecursiveDirectoryIterator($template_dir);
        $iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

        echo '<pre>';
        echo "Ready.\n";

        foreach ($iterator as $file) {
            $basename = basename($file);
            if ($basename[0] == '.') continue;
            if (strpos($file, '.svn') !== false) continue;
            if (strpos($file, '~') !== false) continue;

            $relative_name = substr($file, strlen($template_dir));

            echo "Processing: '/{$_POST['module_type']}{$relative_name}'";

            if ($file->isDir()) {
                // directories
                $new_name = self::mtTransform($relative_name);
                @mkdir ("{$temp}/{$module_name}" . $new_name);

            } else if ($file->isFile()) {
                // files
                $text = file_get_contents($template_dir . $relative_name);
                $text = self::mtTransform($text);

                $new_name = "{$temp}/{$module_name}" . self::mtTransform($relative_name);
                file_put_contents($new_name, $text);
            }

            echo " => '{$new_name}'.\n";
        }

        echo "Done building, now compressing.\n";

        $rand = time();
        echo shell_exec("cd {$temp}; tar -cjvf mt_{$rand}.tar.bz2 {$module_name}");
        shell_exec("cd {$temp}; rm -rf {$module_name}");

        echo "Done.\n";
        echo '</pre>';

        echo "<p><a href=\"SITE/dbtools/gettempfile/module-builder/mt_{$rand}.tar.bz2/sprout_module_{$_POST['sname']}_{$_POST['module_type']}.tar.bz2\">Download module</a></p>";

        $this->template('Module builder');
    }


    /**
     * Generates db_struct.xml content for a module
     */
    public function moduleBuilderDb()
    {
        if (!empty($_GET['table']) and in_array(@$_GET['type'], ['has_categories', 'list', 'tree'])) {
            $template_path = APPPATH . 'module_template/' . $_GET['type'] . '/db_struct.xml';
            $content = file_get_contents($template_path);
            $content = str_replace('PNAME', $_GET['table'], $content);
            $content = str_replace('SNAME', Inflector::singular($_GET['table']), $content);
            $content = str_replace('FIELDS_XML', '', $content);
            $content = preg_replace('/^[\t ]+$/m', '', $content);
            $_GET['xml'] = $content;
        }

        $view = new View('sprout/dbtools/module_builder_db');
        $view->data = $_GET;
        echo $view->render();

        $this->template('Module builder - DB');
    }


    /**
    * Generates modules from an existing db_struct.xml file
    **/
    public function moduleBuilderExisting()
    {
        $temp = APPPATH . 'temp';
        $temp_writeable = (is_dir($temp) and is_writeable($temp));

        $existing_files = [];
        $modules = glob(DOCROOT . 'modules/*');
        foreach ($modules as $mod) {
            if (is_dir($mod) and file_exists($mod . '/db_struct.xml')) {
                $mod = basename($mod);
                $existing_files[$mod] = $mod;
            }
        }

        $view = new View('sprout/dbtools/module_builder_existing_upload');
        $view->temp_writeable = $temp_writeable;
        $view->existing_files = $existing_files;
        echo $view->render();

        $this->template('Module builder existing');
    }


    /**
    * Generates modules from an existing db_struct.xml file
    **/
    public function moduleBuilderExistingUploadAction()
    {
        $filename = 'mbe' . time() . '.xml';

        if (isset($_FILES['file']['tmp_name'])) {
            // Upload a new file
            copy($_FILES['file']['tmp_name'], APPPATH . 'temp/' . $filename);

        } else if (isset($_POST['existing'])) {
            // Process an existing file
            if (!preg_match('!^[a-zA-Z0-9]+$!', $_POST['existing'])) {
                die('Invalid module');
            }
            copy(DOCROOT . 'modules/' . $_POST['existing'] . '/db_struct.xml', APPPATH . 'temp/' . $filename);
            $_SESSION['module_builder_existing']['field_values']['module_name'] = $_POST['existing'];
            $_SESSION['module_builder_existing']['field_values']['module_author'] = 'Karmabunny';

        } else if (isset($_POST['content'])) {
            // Copy and paste XML content
            if (strpos($_POST['content'], '<database>') === false) {
                $_POST['content'] = '<database>' . $_POST['content'] . '</database>';
            }
            file_put_contents(APPPATH . 'temp/' . $filename, $_POST['content']);

        } else {
            die('No file');
        }

        Url::redirect('dbtools/moduleBuilderExistingForm/' . $filename);
    }

    /**
    * Generates modules from an existing db_struct.xml file
    **/
    public function moduleBuilderExistingForm($input_xml)
    {
        if (!preg_match('/^mbe[0-9]+\.xml$/', $input_xml)) die('Invalid filename');

        $sync = new DatabaseSync(false);
        $sync->loadXml(APPPATH . 'temp/' . $input_xml);

        $tables = $sync->tables;
        ksort($tables);

        if (!empty($_SESSION['module_builder_existing']['field_values'])) {
            $data = $_SESSION['module_builder_existing']['field_values'];
        } else {
            $data = [];
        }

        if (!isset($data['tables_cname'])) {
            $data = ['tables_cname' => [], 'tables_sname' => [], 'tables_snice' => [], 'tables_pnice' => []];
            foreach ($tables as $name => $defn) {
                $data['tables_cname'][$name] = Text::lc2camelCaps(Inflector::singular($name));
                $data['tables_sname'][$name] = Inflector::singular($name);
                $data['tables_snice'][$name] = ucfirst(str_replace('_', ' ', Inflector::singular($name)));
                $data['tables_pnice'][$name] = ucfirst(str_replace('_', ' ', $name));
            }
            if (empty($data['module_author'])) $data['module_author'] = 'Karmabunny';
        }

        $view = new View('sprout/dbtools/module_builder_existing_form');
        $view->tables = $tables;
        $view->templates = [
            'has_categories' => 'Categories',
            'tree' => 'Tree',
            'list' => 'List',
        ];
        $view->data = $data;
        $view->input_xml = $input_xml;
        if (!empty($_SESSION['module_builder_existing']['field_errors'])) {
            $view->errors = $_SESSION['module_builder_existing']['field_errors'];
        } else {
            $view->errors = [];
        }
        echo $view->render();

        $this->template('Module builder existing');
    }


    /**
    * Generates modules from an existing db_struct.xml file
    **/
    public function moduleBuilderExistingAction($input_xml)
    {
        static $tab = "    ";

        $errs = [];
        if (!preg_match('/^mbe[0-9]+\.xml$/', $input_xml)) $errs[] = 'Invalid filename';

        if (empty($_POST['module_author'])) {
            $errs['module_author'] = 'Required';
        }
        if (empty($_POST['module_name'])) {
            $errs['module_name'] = 'Required';
        } else {
            $module_name = trim($_POST['module_name']);
            if (!preg_match('/^([A-Z][a-z0-9]+)+$/', $module_name)) {
                $errs['module_name'] = 'Invalid value';
            }
        }
        if ($errs) {
            $_SESSION['module_builder_existing']['field_values'] = Validator::trim($_POST);
            $_SESSION['module_builder_existing']['field_errors'] = $errs;
            Url::redirect('/dbtools/moduleBuilderExistingForm/' . $input_xml);
        }

        $temp = APPPATH . 'temp';
        if (!file_exists("{$temp}/{$module_name}") and !@mkdir("{$temp}/{$module_name}")) {
            echo "<ul class=\"messages\"><li class=\"error\">Failed to create temp directory {$module_name}</li></ul>";
            $this->template('Module builder');
            return;
        }

        $sync = new DatabaseSync(false);
        $sync->loadXml(APPPATH . 'temp/' . $input_xml);
        $tables = $sync->tables;

        foreach ($tables as $t => $defn) {
            if (empty($_POST['tables'][$t])) continue;

            $template_dir = APPPATH . 'module_template/' . $_POST['tables'][$t];
            $dir_iterator = new \RecursiveDirectoryIterator($template_dir);
            $iterator = new \RecursiveIteratorIterator($dir_iterator, \RecursiveIteratorIterator::SELF_FIRST);

            $fields_json = array();
            $fields_manual = array();

            echo '<h3>', Enc::html($t), '</h3>';
            echo "<pre>\n";
            foreach ($defn['columns'] as $f => $col) {
                if (in_array($f, ['id', 'date_added', 'date_modified'])) continue;

                $items = "{}";

                // Determine HTML input field based on field name and type
                // Active field (every table should have one of these)
                if ($f == 'active') {
                    $input_method = 'Fb::multiradio';
                    $items = "{\"0\": \"No\", \"1\": \"Yes\"}";

                // Numeric fields
                } else if (preg_match('/^(num|max|min)_/', $f)) {
                    $input_method = 'Fb::number';

                // ENUM and SET fields
                } else if (preg_match('/^ENUM\s*\(/', $col['type'])) {
                    $input_method = 'Fb::dropdown';
                    $items = '{"func": "Pdb::extractEnumArr", "args": ["' . Enc::js($t) . '", "' . Enc::js($f) . '"]}';
                } else if (preg_match('/^SET\s*\(/', $col['type'])) {
                    $input_method = 'Fb::checkboxSet';
                    $items = '{"func": "Pdb::extractEnumArr", "args": ["' . Enc::js($t) . '", "' . Enc::js($f) . '"]}';

                // Foreign keys (ending in _id)
                } else if (preg_match('/_id$/', $f)) {
                    $target_table = Inflector::plural(substr($f, 0, -3));
                    $input_method = 'Fb::dropdown';
                    $items = '{"func": "Pdb::lookup", "args": ["' . Enc::js($target_table) . '"]}';

                // Other columns: determine field type
                } else {
                    $col_def_parts = preg_split('/\s+/', $col['type']);
                    $type = strtoupper(Sprout::iterableFirstValue($col_def_parts));

                    switch ($type) {
                    case 'DATETIME':
                        $input_method = 'Fb::datetimepicker';
                        break;

                    case 'DATE':
                        $input_method = 'Fb::datepicker';
                        break;

                    case 'TEXT':
                        $input_method = 'Fb::multiline';
                        break;

                    case 'TINYINT':
                        $input_method = 'Fb::multiradio';
                        $items = '{"0": "No", "1": "Yes"}';
                        break;

                    default:
                        $input_method = 'Fb::text';
                    }
                }

                $l = ucfirst(trim(str_replace('_', ' ', $f)));
                $l = preg_replace('/\s+id$/', '', $l);

                // Handle common acronyms
                $l_parts = preg_split('/\s+/', $l);
                foreach ($l_parts as &$l_part) {
                    if (in_array(strtolower($l_part), ['url', 'gst'])) {
                        $l_part = strtoupper($l_part);
                    }
                }
                $l = implode(' ', $l_parts);

                $json = "{$tab}{$tab}{\n" .
                    "{$tab}{$tab}{$tab}\"field\": {\n" .
                    "{$tab}{$tab}{$tab}{$tab}\"name\": \"{$f}\",\n" .
                    "{$tab}{$tab}{$tab}{$tab}\"label\": \"{$l}\",\n" .
                    "{$tab}{$tab}{$tab}{$tab}\"display\": \"{$input_method}\",\n" .
                    "{$tab}{$tab}{$tab}{$tab}\"items\": {$items},\n" .
                    "{$tab}{$tab}{$tab}{$tab}\"required\": false,\n" .
                    "{$tab}{$tab}{$tab}{$tab}\"validate\": [\n";

                // Use length as basic validation where possible, allowing an extra char for a decimal point if relevant
                $matches = [];
                if (preg_match('/\([0-9]+(\s*,)?/', $col['type'], $matches)) {
                    $field_len = (int) substr($matches[0], 1);
                    if (!empty($matches[1])) ++$field_len;
                    $json .= "{$tab}{$tab}{$tab}{$tab}{$tab}{\"func\": \"Validity::length\", \"args\": [0, {$field_len}]}\n";
                }

                $json .= "{$tab}{$tab}{$tab}{$tab}]\n" .
                    "{$tab}{$tab}{$tab}}\n" .
                    "{$tab}{$tab}}";
                $fields_json[] = $json;

                $fields_manual[] = "<p><b>{$l}</b>\n<br><!-- description goes here --></p>\n";
            }

            $possible_main_fields = [
                'name',
                'active',
                'email',
                'first_name',
                'last_name',
                'price',
                'suburb',
                'state',
                'visible',
            ];

            $fields_main = [];
            foreach ($possible_main_fields as $ind) {
                if (in_array($ind, array_keys($defn['columns']))) {
                    $label = ucfirst(str_replace('_', ' ', $ind));
                    if (in_array($ind, ['active', 'visible'])) {
                        $field = "[new ColModifierBinary(), '{$ind}']";
                    } else {
                        $field = "'{$ind}'";
                    }
                    $fields_main[] = "'{$label}' => {$field},";
                }
            }

            $_POST['_fields_xml'] = '';
            $_POST['_fields_json'] = implode(",\n", $fields_json);
            $_POST['_fields_manual'] = rtrim(implode("\n", $fields_manual));
            $_POST['_fields_main'] = implode("\n{$tab}{$tab}{$tab}", $fields_main);

            $_POST['cname'] = $_POST['tables_cname'][$t];
            $_POST['sname'] = $_POST['tables_sname'][$t];
            $_POST['pname'] = $t;
            $_POST['snice'] = $_POST['tables_snice'][$t];
            $_POST['pnice'] = $_POST['tables_pnice'][$t];

            foreach ($iterator as $file) {
                $basename = basename($file);
                if ($basename[0] == '.') continue;
                if (strpos($file, '.svn') !== false) continue;
                if (strpos($file, '~') !== false) continue;

                $relative_name = substr($file, strlen($template_dir));
                if ($relative_name == '/db_struct.xml') continue;
                if ($relative_name == '/admin_load.php') continue;
                if ($relative_name == '/sprout_load.php') continue;

                echo "Processing: '{$relative_name}'";

                if ($file->isDir()) {
                    $new_name = self::mtTransform($relative_name);
                    @mkdir ("{$temp}/{$module_name}" . $new_name);

                } else if ($file->isFile()) {
                    $text = file_get_contents($template_dir . $relative_name);
                    $text = self::mtTransform($text);

                    $new_name = "{$temp}/{$module_name}" . self::mtTransform($relative_name);
                    file_put_contents($new_name, $text);
                }

                echo " => '{$new_name}'.\n";
            }

            echo '</pre>';
        }

        copy(APPPATH . 'temp/' . $input_xml, "{$temp}/{$module_name}/db_struct.xml");

        echo "<p>Done building, now compressing...\n";

        echo '<pre>';
        $rand = time();
        echo shell_exec("cd {$temp}; tar -cjvf mt_{$rand}.tar.bz2 {$module_name}");
        shell_exec("cd {$temp}; rm -rf {$module_name}");
        echo '</pre>';

        echo "<p>Done.\n";
        echo "<div class=\"action-bar\"><a href=\"SITE/dbtools/gettempfile/module-builder/mt_{$rand}.tar.bz2/sprout_module.tar.bz2\" class=\"button icon-after icon-save\">Download module</a></div>";

        $this->template('Module builder');
    }



    /**
    * Used by the module_template_action method
    **/
    public static function mtTransform($text)
    {
        $text = str_replace('AUTHOR', $_POST['module_author'], $text);
        $text = str_replace('MODULE', $_POST['module_name'], $text);
        $text = str_replace('CNAME', $_POST['cname'], $text);
        $text = str_replace('SNAME', $_POST['sname'], $text);
        $text = str_replace('PNAME', $_POST['pname'], $text);
        $text = str_replace('SNICE', $_POST['snice'], $text);
        $text = str_replace('PNICE', $_POST['pnice'], $text);
        $text = str_replace('SLWR', strtolower($_POST['snice']), $text);
        $text = str_replace('PLWR', strtolower($_POST['pnice']), $text);

        $text = str_replace('FIELDS_XML', $_POST['_fields_xml'], $text);
        $text = str_replace('FIELDS_JSON', $_POST['_fields_json'], $text);
        $text = str_replace('FIELDS_MANUAL', $_POST['_fields_manual'], $text);
        $text = str_replace('FIELDS_MAIN', $_POST['_fields_main'], $text);

        return $text;
    }


    /**
     * Browse and search exceptions
     */
    public function exceptionLog()
    {
        $conditions = array();
        if (!empty($_GET['class'])) {
            $conditions[] = ['class_name', '=', $_GET['class']];
        }
        if (!empty($_GET['message'])) {
            $conditions[] = ['message', 'CONTAINS', $_GET['message']];
        }
        if (empty($_GET['show_row_missing'])) {
            $conditions[] = ['class_name', '!=', 'Sprout\Exceptions\RowMissingException'];
        }
        if (empty($_GET['show_404'])) {
            $conditions[] = ['class_name', '!=', 'Kohana_404_Exception'];
        }
        if (count($conditions) == 0) $conditions[] = '1';

        $page_size = 100;
        $page = max((int)@$_GET['page'], 1);
        $offset = ($page - 1) * $page_size;

        $binds = array();
        $where = Pdb::buildClause($conditions, $binds);
        $q = "SELECT id, date_generated, class_name, message
            FROM ~exception_log
            WHERE {$where}
            ORDER BY id DESC
            LIMIT {$offset}, {$page_size}";
        $res = Pdb::query($q, $binds, 'pdo');

        $row_count = $res->rowCount();
        if ($row_count == 0) {
            $itemlist = '<p><em>No items found</em></p>';
        } else {
            $itemlist = new Itemlist();
            $itemlist->items = $res;
            $itemlist->addAction('edit', 'dbtools/exceptionDetail?id=%%');
            $itemlist->main_columns = array(
                'Date' => 'date_generated',
                'Class' => 'class_name',
                'Message' => 'message',
            );
        }

        // View
        $view = new View('sprout/dbtools/exception_log');
        $view->itemlist = $itemlist;
        $view->page = $page;
        $view->row_count = $row_count;
        $view->page_size = $page_size;
        echo $view->render();

        $res->closeCursor();
        $this->template('Exception log');
    }


    /**
     * Browse recent exceptions - details
     */
    public function exceptionDetail()
    {
        $_GET['id'] = preg_replace('/^SE/i', '', trim($_GET['id']));

        try {
            $log = Pdb::get('exception_log', $_GET['id']);
            $title = $log['id'];
        } catch (RowMissingException $ex) {
            $log = [];
            $title = 'Not found';
        }

        // View
        $view = new View('sprout/dbtools/exception_details');
        $view->log = $log;

        echo $view->render();
        $this->template('Exception #' . $title);
    }


    /**
     * Generate a password hash to store in a config file
     * The username and hash are used by {@see AdminAuth::processLocal}
     */
    public function generatePasswordHash()
    {
        $username = trim(@$_POST['username']);

        echo '<form method="post">';
        echo '<div class="field-group-wrap -clearfix">';
        echo '<div class="field-group-item col col--one-half">';
        Form::nextFieldDetails('Username', true, 'Letters and numbers only');
        echo Form::text('username', ['-wrapper-class' => 'white']);
        echo '</div><!-- .col.col--one-half -->';

        echo '<div class="field-group-item col col--one-half">';
        Form::nextFieldDetails('Password', true, 'Will be displayed on screen');
        echo Form::text('pass', ['-wrapper-class' => 'white']);
        echo '</div><!-- .col.col--one-half -->';
        echo '</div><!-- .field-group-wrap -->';

        echo '<div class="action-bar"><button type="submit" class="button icon-after icon-keyboard_arrow_right">Generate hash</button></div>';
        echo '</form>';

        if (!empty($username) and !empty($_POST['pass'])) {
            $data = Auth::hashPassword($_POST['pass'], Constants::PASSWORD_BCRYPT12);

            $users = AdminAuth::injectLocalSuperConf($username, $data[0], $data[2]);

            echo "<h4>Paste this into a config/super_ops.php file</h4>\n";

            echo "<pre>&lt;?php\n\$config['operators'] = [\n";
            foreach ($users as $username => $user) {
                echo "    '", Enc::html(Enc::js($username));
                echo "' =&gt; ['uid' => {$user['uid']}, 'hash' =&gt; '", Enc::html(Enc::js($user['hash']));
                echo "', 'salt' =&gt; '", Enc::html(Enc::js($user['salt'])), "'],\n";
            }
            echo "];</pre>";
        }

        $this->template('Generate password hash');
    }


    /**
     * Render view to see session and cookie data
     *
     * @return void Echos HTML
     */
    public function varDump()
    {
        echo '<h2>$_SESSION</h2>';
        echo sprintf('<pre>%s</pre>', print_r($_SESSION, true));
        echo '<h2>$_COOKIE</h2>';
        echo sprintf('<pre>%s</pre>', print_r($_COOKIE, true));
        echo '<h2>$_SERVER</h2>';
        echo sprintf('<pre>%s</pre>', print_r($_SERVER, true));

        $this->template('Var dump');
    }

    /**
     * Simple tool for testing skin templates
     *
     * @return void Outputs HTML directly
     */
    public function testSkinTemplates()
    {
        $skins = Subsites::getCodes();
        $skins[] = 'unavailable';

        echo '<div class="info highlight-warning">';
        echo 'Note: Not all templates will work if they rely on variables being set which are not set by this tool.';
        echo '</div>';

        foreach ($skins as $s) {
            echo '<h3>', Enc::html($s), '</h3>';

            $templates = glob(DOCROOT . 'skin/' . $s . '/*.php');
            foreach ($templates as $f) {
                $f = basename($f, '.php');
                if ($f[0] == '.' or $f[0] == '_') continue;
                if ($f == 'exception' or $f == 'popup' or $f == 'google_analytics') continue;

                $url = '/dbtools/testSkinTemplatesAction/' . Enc::url($s) . '/' . Enc::url($f);

                echo '<p><a href="' . Enc::html($url) . '">' . Enc::html($f) . '</a></p>';
            }
        }

        $this->template('Template test tool');
    }


    /**
     * Actual viewing UI for templates
     *
     * @param string $skin Skin name, e.g. 'default'
     * @param string $tmpl Template filename, e.g. 'inner'
     * @return void Outputs HTML directly
     */
    public function testSkinTemplatesAction($skin, $tmpl)
    {
        $skin = preg_replace('![^-_a-zA-Z0-9]!', '', $skin);
        $tmpl = preg_replace('![^-_a-zA-Z0-9]!', '', $tmpl);

        if (empty($skin) or empty($tmpl)) {
            throw new Kohana_404_Exception();
        }

        try {
            $q = "SELECT id FROM ~subsites WHERE code = ?";
            $subsite_id = Pdb::query($q, [$skin], 'val');
        } catch (RowMissingException $ex) {
            $subsite_id = SubsiteSelector::$subsite_id;
        }

        // Fake the subsite environment so nav and breadcrumb will work
        SubsiteSelector::$subsite_id = $subsite_id;
        SubsiteSelector::$subsite_code = $skin;
        SubsiteSelector::$content_id = $subsite_id;

        // Force a reload of the tree (in case tree is already loaded for some reason)
        $root = Navigation::loadPageTree($subsite_id, false, true);

        // Find a node for sidenav, breadcrumb, etc.
        // Preference goes to one with children, but fallback is one without
        if ($root and count($root->children) > 0) {
            $fake_node = null;
            foreach ($root->children as $nd) {
                if ($nd['show_in_nav'] and count($nd->children)) {
                    $fake_node = $nd;
                    break;
                }
            }

            if ($fake_node == null) {
                foreach ($root->children as $nd) {
                    if ($nd['show_in_nav']) {
                        $fake_node = $nd;
                        break;
                    }
                }
            }

            if ($fake_node != null) {
                $fake_node['name'] = 'Template test';
                Navigation::setPageNodeMatcher(new TreenodeValueMatcher('id', $fake_node['id']));
            }
        }

        $content = new View('sprout/dbtools/skin_test_content');
        $email = new View('sprout/email/testing_long');

        // Page templates
        $view = new View('skin/' . $tmpl);
        $view->page_title = 'Template test';
        $view->browser_title = 'Template test';
        $view->main_content = $content->render();

        // Email template
        $view->html_title = $view->page_title;
        $view->content = $email->render();

        echo $view->render();
    }


    /**
    * Generate multiedit code
    **/
    public function multimake()
    {
        echo '<style type="text/css">';
        echo '.mini {font-size: 9px; color: #555;}';
        echo '</style>';

        if (empty($_POST)) {
            $modules_dir = DOCROOT . 'modules';
            $xml_files = glob("$modules_dir/*/db_struct.xml");
            $opts = array();
            foreach ($xml_files as $file) {
                $file = substr($file, strlen($modules_dir) + 1);
                $module = dirname($file);
                $opts[$module] = $module;
            }

            echo '<form method="POST">';
            Form::nextFieldDetails('Select module', true);
            echo Form::dropdown('module', ['-wrapper-class' => 'white'], $opts);
            echo '<div class="action-bar"><button type="submit" class="button icon-after icon-keyboard_arrow_right">Next</button></div>';

            $this->template('Generate multiedit code');
            return;
        }

        if (!empty($_POST['module'])) {
            $doc = self::xmlLoad($_POST['module']);
            $tables = self::xmlGetTables($doc);

            $module_tables = array();
            $invalid_tables = array();
            foreach ($tables as $table) {
                $cols = self::xmlGetColumns($doc, $table);
                if (!isset($cols['id'])) continue;

                $full_name = $_POST['module'] . '/' . $table;

                // Look for a {something}_id column.
                // A table without such a column is probably invalid, so put it
                // at the bottom of the list, with an asterisk to warn the user
                $valid = false;
                foreach ($cols as $name => $defn) {
                    if ($name == 'id') continue;
                    if ($name == 'subsite_id') continue;
                    if (preg_match('/_id$/', $name)) {
                        $valid = true;
                        break;
                    }
                }

                if ($valid) {
                    $module_tables[$full_name] = $table;
                } else {
                    $invalid_tables[$full_name] = "* $table";
                }
            }
            $opts = array_merge($module_tables, $invalid_tables);

            echo "<h3>Module: <b>{$_POST['module']}</b></h3>";
            if (count($opts) > 0) {
                echo '<form method="POST">';
                Form::nextFieldDetails('Select table which will store the data', true, 'i.e. the sub-table');
                echo Form::dropdown('table', ['-wrapper-class' => 'white'], $opts);
                echo '<div class="action-bar"><button type="submit" class="button icon-after icon-keyboard_arrow_right">Next</button></div>';
                echo '</form>';
            } else {
                echo '<p>No useable tables in this module</p>';
            }

            $this->template('Generate multiedit code');
            return;
        }

        if (!empty($_POST['table'])) {
            list($module, $table) = explode('/', $_POST['table']);

            $doc = self::xmlLoad($module);
            $columns = self::xmlGetColumns($doc, $table);
            $opts = array();
            $selected = null;
            foreach ($columns as $name => $defn) {
                if ($name == 'id') continue;
                $value = "{$module}/{$table}/{$name}";
                $opts[$value] = $name;

                if ($selected) continue;
                if ($name == 'subsite_id') continue;

                if (substr($name, -3) == '_id') $selected = $value;
            }

            $matches = array();
            preg_match('/_([a-z0-9]+)$/', $table, $matches);
            Fb::setData(array('group' => @$matches[1], 'linker' => $selected));

            echo "<h3>Module: <b>{$module}</b><br>Table: <b>{$table}</b></h3>";

            echo '<form method="POST">';
            echo '<div class="field-group-wrap -clearfix"><div class="field-group-item col col--one-half">';
            Form::nextFieldDetails('Group name', true, 'e.g. people');
            echo Form::text('group', ['-wrapper-class' => 'white']);
            echo '</div>';

            echo '<div class="field-group-item col col--one-half">';
            Form::nextFieldDetails('Column which links to base table', true, 'e.g. user_id');
            echo Form::dropdown('linker', ['-wrapper-class' => 'white'], $opts);
            echo '</div></div>';

            echo '<div class="action-bar"><button type="submit" class="button icon-after icon-keyboard_arrow_right">Generate code</button></div>';

            echo '</form>';

            $this->template('Generate multiedit code');
            return;
        }

        if (empty($_POST['linker']) or empty($_POST['group'])) {
            echo '<p>Huh?</p>';

            $this->template('Generate multiedit code');
            return;
        }

        list($module, $table, $linker) = explode('/', $_POST['linker']);
        $doc = self::xmlLoad($module);
        $columns = self::xmlGetColumns($doc, $table);
        $ordered = isset($columns['record_order']);

        echo "<h3>Module: <b>{$module}</b><br>Table: <b>{$table}</b><br>Group name: <b>{$_POST['group']}</b><br>Linking column: <b>{$linker}</b></h3>";

        $file = DOCROOT . 'sprout/views/dbtools/multimake_template.php';
        $template = file_get_contents($file);
        $template = preg_replace('/(multiedit[-_])people/', '$1' . $_POST['group'], $template);
        $template = str_replace('user_people', $table, $template);
        $template = str_replace('People', ucfirst($_POST['group']), $template);
        $template = str_replace('people', $_POST['group'], $template);
        $single = ucfirst(Inflector::singular($_POST['group']));
        $template = str_replace('Person', $single, $template);
        $template = str_replace('user_id', $linker, $template);

        if ($ordered) {
            $reorder = '<?php MultiEdit::reorder(); ?>';
            $init = '$order = 0;';
        } else {
            $reorder = '';
            $init = '';
            $template = str_replace('record_order', 'id', $template);
        }
        $template = preg_replace('#//\s*REORDER\s*//#', $reorder, $template);
        $template = preg_replace('#//\s*INIT_ORDER\s*//#', $init, $template);

        // Generate form fields for view
        $data = '';
        foreach ($columns as $name => $type) {
            if ($name == 'id') continue;
            if ($name == $linker) continue;
            if ($name == 'record_order') continue;
            $label = ucfirst($name);
            $label = preg_replace('/_id$/', '', $label);
            $label = str_replace('_', ' ', $label);
            $data .= "\t<p><b>{$label}:</b>\n";
            $data .= "\t" . '<br><input type="text" name="m_' .
                Enc::html($name) . "\"></p>\n\n";
        }
        $template = preg_replace('#//\s*INPUTS\s*//#', trim($data), $template);

        // Generate update data
        $data = '';
        foreach ($columns as $name => $type) {
            if ($name == 'id') continue;
            $data .= "\t\$update_fields['" . $name . "'] = ";
            if ($name == 'record_order') {
                $data .= "\$order++;\n";
            } else if ($name == $linker) {
                $data .= "\$item_id;\n";
            } else {
                $data .= "\$data['" . $name . "'];";
                if ($name == 'date_added' or $name == 'date_modified') {
                    $data .= ' // WARNING!!!';
                }
                $data .= "\n";
            }
        }
        $template = preg_replace('#//\s*UPDATES\s*//#', trim($data), $template);

        $template = str_replace("\n\n\n", "\n\n", $template);

        highlight_string($template);

        $this->template('Generate multiedit code');
    }

    /**
    * @return array
    */
    private static function xmlGetTables(DOMDocument $doc)
    {
        $table_els = $doc->getElementsByTagName('table');
        $tables = array();
        foreach ($table_els as $el) {
            $tables[] = $el->getAttribute('name');
        }
        return $tables;
    }

    /**
    * @return array
    */
    private static function xmlGetColumns(DOMDocument $doc, $table_name)
    {
        $table_els = $doc->getElementsByTagName('table');
        $table = null;
        foreach ($table_els as $el) {
            if ($el->getAttribute('name') == $table_name) {
                $table = $el;
                break;
            }
        }
        if (!$table) return array();

        $columns = array();
        $column_els = $table->getElementsByTagName('column');
        foreach ($column_els as $el) {
            $name = $el->getAttribute('name');
            $type = $el->getAttribute('type');
            $columns[$name] = $type;
        }

        return $columns;
    }

    private static function xmlLoad($module)
    {
        $file = DOCROOT . "modules/{$module}/db_struct.xml";
        $doc = new DOMDocument();
        $doc->loadXML(file_get_contents($file));
        return $doc;
    }


    /**
    * Renders form to send emails
    **/
    public function email()
    {
        $op = AdminAuth::getDetails();

        $data = [];
        $data['emails'] = $op['email'];
        $data['from'] = Kohana::config('sprout.site_email');
        $data['msg'] = 'long';

        Form::setData($data);

        $out = '<form action="SITE/dbtools/emailSend" method="post">';
        $out .= Csrf::token();

        Form::nextFieldDetails('Who to send to', false, 'one address per line');
        $out .= Form::multiline('emails', []);

        Form::nextFieldDetails('Different FROM address', false);
        $out .= Form::email('from');

        Form::nextFieldDetails('Message to send', false);
        $out .= Form::multiradio('msg', [], ['long' => 'Long test email - tables, headings, unicode, etc.', 'short' => 'Short simple test email']);

        Form::nextFieldDetails('Information', false);
        $out .= Form::checkboxBoolList('', [], ['debug' => 'Show debugging information']);

        $out .= '<button class="button" type="submit">Send emails</button>';
        $out .= '</form>';

        echo $out;
        $this->template('Email');
    }


    /**
    * Process form submission
    **/
    public function emailSend()
    {
        Csrf::checkOrDie();

        if (empty($_POST['emails'])) {
            Url::redirect('dbtools/email');
        }

        if ($_POST['msg'] == 'long') {
            $subject = "Test email containing a little bit of üńìĉȯḍē.";
            $view = new View('sprout/email/testing_long');
            $body = $view->render();

        } else if ($_POST['msg'] == 'short') {
            $subject = 'Test';
            $body = '<p>This is a test email.</p>';
        }

        if (!empty($_POST['from'])) {
            Validity::email($_POST['from']);
        }

        $addresses = explode("\n", $_POST['emails']);
        $succ = $fail = 0;
        foreach ($addresses as $e) {
            $e = trim($e);
            if (! $e) continue;

            echo '<h2>', Enc::html($e), '</h2>';

            try {
                Validity::email($e);
            } catch (ValidationException $ex) {
                echo '<p>', Enc::html($ex->getMessage()), '</p>';
                continue;
            }

            $mail = new Email();
            $mail->AddAddress($e);
            $mail->Subject = $subject;
            $mail->SkinnedHTML($body);

            if (!empty($_POST['debug'])) {
                $mail->SMTPDebug = 3;
            }

            if (!empty($_POST['from'])) {
                $mail->From = $_POST['from'];
            }

            ob_start();
            $result = $mail->Send();
            $log = ob_get_clean();

            if ($log) {
                echo '<pre>', Enc::html($log), '</pre>';
            }

            if ($result) {
                echo '<p>Sent email to <b>', Enc::html($e), '</b>.</p>';
                $succ++;
            } else {
                echo '<p>Sending to <b>', Enc::html($e), '</b> failed!</p>';
                $fail++;
            }
        }

        echo '<h2>Summary</h2>';
        echo '<p><b>Success:</b> ', $succ, '<br><b>Failed:</b> ', $fail, '</p>';
        echo '<p><a href="SITE/dbtools/email" class="button">Send more!</a></p>';
        $this->template('Email');
    }


    /**
     * Renders form to imoprt Sprout2 Export XML
     *
     * @return void Echos HTML directly
     */
    public function importXML()
    {
        $view = new View('sprout/dbtools/import_xml');
        $view->subsites = Pdb::lookup('subsites');
        echo $view->render();

        $this->template('Import Sprout 2 pages');
    }


    /**
     * Process Sprout2 Export XML into this CMS
     *
     * @return void Redirects
     */
    public function importXmlAction()
    {
        Csrf::checkOrDie();

        $_POST['subsite_id'] = (int) @$_POST['subsite_id'];
        $_POST['page_id'] = (int) @$_POST['page_id'];

        // Validate sub-site
        if (empty($_POST['subsite_id'])) {
            Notification::error('Please select a sub-site');
            Url::redirect('dbtools/importXML');
        }

        // Validate file type
        $ext = strtolower(File::getExt($_FILES['filename']['name']));
        if ($ext != 'xml') {
            Notification::error('Invalid file type');
            Url::redirect('dbtools/importXML');
        }

        // Determine temp filename
        $timestamp = time();
        $tempname = APPPATH . "temp/dbtools_import_{$timestamp}.{$ext}";

        // Attempt upload
        $res = @copy($_FILES['filename']['tmp_name'], $tempname);
        if (! $res) {
            Notification::error('Unable to copy file to temporary directory');
            Url::redirect('dbtools/importXML');
        }

        // Run the import tool
        $pages = ImportCMS::import($tempname);

        unlink($tempname);

        // Render table of pages that need widgets replaced
        $list = new Itemlist();
        $list->main_columns = [
            'Old ID' => 'old_id',
            'New ID' => 'new_id',
            'Widgets' => 'widgets',
        ];
        $list->items = $pages;

        echo $list->render();

        $this->template('Successfully imported Sprout 2 pages');
    }


    /**
     * Render page drop-down for given sub-site
     *
     * @param int $subsite_id
     * @return void Echos HTML directly
     */
    public function ajaxPageIds($subsite_id)
    {
        $subsite_id = (int) $subsite_id;
        AdminAuth::checkLogin();

        Form::nextFieldDetails('Parent page', false, 'Import as child pages of selected parent page');
        echo Form::pageDropdown('page_id', [], ['subsite' => $subsite_id]);
    }
}
