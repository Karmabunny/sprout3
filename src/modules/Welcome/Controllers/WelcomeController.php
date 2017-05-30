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

namespace SproutModules\Karmabunny\Welcome\Controllers;

use Exception;
use PDOException;

use Kohana;

use Sprout\Controllers\Controller;
use Sprout\Exceptions\QueryException;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Auth;
use Sprout\Helpers\Constants;
use Sprout\Helpers\DatabaseSync;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Json;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Session;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Url;
use Sprout\Helpers\Validator;
use Sprout\Helpers\View;


class WelcomeController extends Controller
{

    public function __construct()
    {
        Session::instance();
    }


    /**
     * Redirect home page traffic to the welcome checklist
     */
    public function redirect()
    {
        Url::redirect('welcome/checklist');
    }


    /**
     * Show a phpinfo() view along with some extra information
     */
    public function phpInfo()
    {
        $view = new View('modules/Welcome/phpinfo');

        $view->vars = array(
            'PHP version' => phpversion(),
            'PHP sapi' => php_sapi_name(),
            'Server software' => @$_SERVER['SERVER_SOFTWARE'],
            'Server OS' => PHP_OS,
            'DOCROOT' => DOCROOT,
            'KOHANA' => KOHANA,
            'APPPATH' => APPPATH,
            'HTTP_X_FORWARDED_FOR' => @$_SERVER['HTTP_X_FORWARDED_FOR'],
            'REMOTE_ADDR' => @$_SERVER['REMOTE_ADDR'],
            'PHP date' => date('Y-m-d H:i:s'),
            'PHP TZ' => date_default_timezone_get(),
        );

        $skin = new View('sprout/admin/login_layout');
        $skin->browser_title = 'PHP information';
        $skin->main_title = 'PHP information';
        $skin->main_content = $view->render();
        echo $skin->render();
    }


    /**
     * Display the welcome checklist
     */
    public function checklist()
    {
        unset($_SESSION['database_config']);

        $view = new View('modules/Welcome/checklist');
        $view->results = [
            'dbconf' => $this->testDbconf(),
            'superop' => $this->testSuperOp(),
            'dbsync' => $this->testDbsync(),
            'sample' => $this->testSampleContent(),
            'welcome' => $this->testWelcome(),
        ];

        $view->overall_success = true;
        foreach($view->results as $row) {
            if ($row[0] == false) {
                $view->overall_success = false;
                break;
            }
        }

        // Find the line number which has the welcome module registration
        $conf = file_get_contents(DOCROOT . 'config/config.php');
        $pos = strpos($conf, "'Welcome'");
        $num = substr_count($conf, "\n", 0, $pos);
        $view->welcome_line_num = $num + 1;

        $skin = new View('sprout/admin/login_layout');
        $skin->browser_title = 'Welcome to SproutCMS';
        $skin->main_title = 'Welcome to SproutCMS';
        $skin->main_content = $view->render();
        echo $skin->render();
    }


    /**
     * Test the database config is correct
     *
     * @return array [0] boolean overall result [1] string message
     */
    private function testDbconf()
    {
        if (!file_exists(DOCROOT . 'config/database.php')) {
            return [false];
        }

        try {
            Pdb::getConnection();
            return [true];
        } catch (PDOException $ex) {
            return [false, $ex->getMessage()];
        }
    }


    /**
     * Test whether one or more super operators have been created
     *
     * @return array [0] boolean overall result [1] string message
     */
    private function testSuperOp()
    {
        try {
            $ops = Kohana::config('super_ops.operators');
        } catch (Exception $ex) {
            $ops = [];
        }

        if (count($ops) > 0) {
            return [0 => true, 1 => 'Local file'];
        }

        return [0 => false];
    }


    /**
     * Test that tables are available
     *
     * @return array [0] boolean overall result [1] string message
     */
    private function testDbsync()
    {
        try {
            $q = "SELECT * FROM ~pages LIMIT 1";
            Pdb::query($q, [], 'null');
            return [true];
        } catch (PDOException $ex) {
            return [false, 'Unable to connect to database'];
        } catch (QueryException $ex) {
            return [false, $ex->getMessage()];
        }
    }


    /**
     * Test that sample content has been added
     *
     * @return array [0] boolean overall result [1] string message
     */
    private function testSampleContent()
    {
        try {
            $q = "SELECT COUNT(*) FROM ~pages LIMIT 1";
            $num_pages = Pdb::query($q, [], 'val');
            return [$num_pages > 0];
        } catch (PDOException $ex) {
            return [false, 'Unable to connect to database'];
        } catch (QueryException $ex) {
            return [false, $ex->getMessage()];
        }
    }


    /**
     * Test that the welcome module isn't installed
     *
     * @return array [0] boolean overall result [1] string message
     */
    private function testWelcome()
    {
        return [
            !Sprout::moduleInstalled('Welcome')
        ];
    }


    /**
     * Show a UI for generating a database config
     */
    public function dbConfForm()
    {
        unset($_SESSION['database_config']);

        $data = Form::loadFromSession('db_conf');
        if (empty($data)) {
            Form::setData([
                'host' => 'localhost',
            ]);
        }

        $view = new View('modules/Welcome/db_conf_form');

        $skin = new View('sprout/admin/login_layout');
        $skin->browser_title = 'Database sync';
        $skin->main_title = 'Database sync';
        $skin->main_content = $view->render();
        echo $skin->render();
    }


    /**
     * Ajax method to test the db connection for a given set of params
     */
    public function dbConfTest()
    {
        if (empty($_POST['host'])) Json::out(['result' => 'You must specify a host']);
        if (empty($_POST['user'])) Json::out(['result' => 'You must specify a user']);
        if (empty($_POST['pass'])) Json::out(['result' => 'You must specify a pass']);
        if (empty($_POST['database'])) Json::out(['result' => 'You must specify a database']);

        try {
            new \PDO(
                "mysql:host={$_POST['host']};dbname={$_POST['database']}",
                $_POST['user'],
                $_POST['pass']
            );
            Json::out(['result' => 'Connection successful']);
        } catch (PDOException $ex) {
            Json::out(['result' => $ex->getMessage()]);
        }
    }


    /**
     * Display the generated database config
     */
    public function dbConfResult()
    {
        $_SESSION['db_conf']['field_values'] = Validator::trim($_POST);

        $valid = new Validator($_POST);
        $valid->required(['production', 'host', 'user', 'pass', 'database']);

        if ($valid->hasErrors()) {
            $_SESSION['db_conf']['field_errors'] = $valid->getFieldErrors();
            Url::redirect('welcome/db_conf_form');
        } else {
            unset($_SESSION['db_conf']);
        }

        $_SESSION['database_config'] = $_POST;

        $view = new View('modules/Welcome/db_conf_result');
        $view->db_config_url = 'welcome/db_conf_database';

        if ($_POST['production'] == 'live') {
            $view->pass_config_url = 'welcome/db_conf_password';
            $view->pass_config = self::genPassConfig($_POST);

            $parts = explode(DIRECTORY_SEPARATOR, rtrim(DOCROOT, DIRECTORY_SEPARATOR));
            array_pop($parts);
            $view->pass_filename = implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . 'database.config.php';
        } else {
            $view->host_config_url = 'welcome/db_conf_hosts';
            $view->host_config = self::genDevHostsConfig();

            $view->dev_hostname = php_uname('n');
        }

        $skin = new View('sprout/admin/login_layout');
        $skin->browser_title = 'Database sync';
        $skin->main_title = 'Database sync';
        $skin->main_content = $view->render();
        echo $skin->render();
    }


    /**
     * Generate and download a database config file
     */
    public static function dbConfDatabase()
    {
        $config = self::genDbConfig($_SESSION['database_config']);

        header('Content-type: application/php');
        header('Content-disposition: attachment; filename="database.php"');
        echo $config;
        exit(0);
    }


    /**
     * Generate and download a dev hosts config file
     */
    public static function dbConfHosts()
    {
        $config = self::genDevHostsConfig();

        header('Content-type: application/php');
        header('Content-disposition: attachment; filename="dev_hosts.php"');
        echo $config;
        exit(0);
    }


    /**
     * Generate a database config from given parameters
     *
     * @param array $data Config params; 'production', 'host', 'user', 'pass', 'database'
     * @return string
     */
    private static function genDbConfig(array $data)
    {
        $db_config = file_get_contents(__DIR__ . '/../config_tmpl/database.php');

        if ($data['production'] == 'live') {
            $db_config = str_replace('{{PROD-HOST}}', $data['host'], $db_config);
            $db_config = str_replace('{{PROD-USER}}', $data['user'], $db_config);
            $db_config = str_replace('{{PROD-DATABASE}}', $data['database'], $db_config);
            $db_config = str_replace('{{TEST-USER}}', '-- username --', $db_config);
            $db_config = str_replace('{{TEST-PASS}}', '-- password --', $db_config);
            $db_config = str_replace('{{TEST-DATABASE}}', '-- database --', $db_config);
            $db_config = str_replace('{{TEST-HOST}}', 'localhost', $db_config);
        } else {
            $db_config = str_replace('{{TEST-HOST}}', $data['host'], $db_config);
            $db_config = str_replace('{{TEST-USER}}', $data['user'], $db_config);
            $db_config = str_replace('{{TEST-PASS}}', $data['pass'], $db_config);
            $db_config = str_replace('{{TEST-DATABASE}}', $data['database'], $db_config);
            $db_config = str_replace('{{PROD-USER}}', '-- username --', $db_config);
            $db_config = str_replace('{{PROD-DATABASE}}', '-- database --', $db_config);
            $db_config = str_replace('{{PROD-HOST}}', 'localhost', $db_config);
        }

        return $db_config;
    }


    /**
     * Generate a dev hosts config, which contains the existing config and the current hostname
     *
     * @return string
     */
    private static function genDevHostsConfig()
    {
        if (file_exists(DOCROOT . 'config/dev_hosts.php')) {
            require DOCROOT . 'config/dev_hosts.php';
            if (@is_array($dev_hosts)) {
                $dev_hosts = array_filter($dev_hosts);
            }
        }
        if (empty($dev_hosts)) {
            $dev_hosts = [];
        }
        $dev_hosts[] = php_uname('n');
        $dev_hosts = array_unique($dev_hosts);

        $lines = [
            '<?php',
            '$dev_hosts = ['
        ];
        foreach ($dev_hosts as $host) {
            $lines[] = "    '" . addslashes($host) . "',";
        }
        $lines[] = '];';

        return implode("\n", $lines);
    }


    /**
     * Generate and download a password config file
     */
    public static function dbConfPassword()
    {
        $config = self::genPassConfig($_SESSION['database_config']);

        header('Content-type: application/php');
        header('Content-disposition: attachment; filename="database.config.php"');
        echo $config;
        exit(0);
    }


    /**
     * Generate a database password file from given parameters
     *
     * @param array $data Config params; 'pass'
     * @return string
     */
    private static function genPassConfig(array $data)
    {
        $pass_config = file_get_contents(__DIR__ . '/../config_tmpl/prod_password.php');
        $pass_config = str_replace('{{PROD-PASS}}', $data['pass'], $pass_config);
        return $pass_config;
    }


    /**
     * Run a database sync
     */
    public function sync()
    {
        $sync = new DatabaseSync(true);
        $sync->loadStandardXmlFiles();
        $sync->sanityCheck();

        if ($sync->hasLoadErrors()) {
            $out = $sync->getLoadErrorsHtml();
            die('Sync failed sanity check: ' . $out);
        }

        $log = $sync->updateDatabase();
        if (empty($log)) {
            $log = '<p>Everything is up to date</p>';
        }

        $view = new View('modules/Welcome/sync');
        $view->log = $log;

        $skin = new View('sprout/admin/login_layout');
        $skin->browser_title = 'Database sync';
        $skin->main_title = 'Database sync';
        $skin->main_content = $view->render();
        echo $skin->render();
    }


    /**
     * Show a UI to create a super-operator
     */
    public function superOperatorForm()
    {
        Form::loadFromSession('super_op');

        $view = new View('modules/Welcome/super_op_form');

        $skin = new View('sprout/admin/login_layout');
        $skin->browser_title = 'Super operator';
        $skin->main_title = 'Super operator';
        $skin->main_content = $view->render();
        echo $skin->render();
    }


    /**
     * Ensure password has enough complexity
     */
    private static function passwordComplexity($str)
    {
        // Longer than this won't be brute forced any time soon.
        if (strlen($str) >= 16) {
            return true;
        }

        $errs = [];
        if (!preg_match('/[0-9]/', $str)) {
            $errs[] = 'Must contain a number character';
        }
        if (!preg_match('/[A-Z]/', $str)) {
            $errs[] = 'Must contain an uppercase character';
        }
        if (!preg_match('/[a-z]/', $str)) {
            $errs[] = 'Must contain a lowercase character';
        }

        if (count($errs) == 0) return true;
        return $errs;
    }


    /**
     * Create a super operator
     *
     * @return void Redirects
     */
    public function superOperatorAction()
    {
        $_SESSION['super_op']['field_values'] = Validator::trim($_POST);

        $valid = new Validator($_POST);
        $valid->required(['username', 'password1', 'password2']);
        $valid->check('username', 'Validity::length', 0, 50);
        $valid->check('username', 'Validity::uniqueValue', 'operators', 'username', 0, 'An operator already exists with that username');
        $valid->check('username', 'Validity::regex', '/^[a-zA-Z0-9]+$/');
        $valid->check('password1', 'Validity::length', 8, 60);
        $valid->check('password2', 'Validity::length', 8, 60);
        $valid->multipleCheck(['password1', 'password2'], 'Validity::allMatch');

        if (!empty($_POST['password1']) and $_POST['password1'] === $_POST['password2']) {
            $complexity = self::passwordComplexity($_POST['password1']);
            if ($complexity !== true) {
                $valid->addFieldError('password1', 'Not complex enough');
                $valid->addFieldError('password2', 'Not complex enough');

                Notification::error('Password does not meet complexity requirements:');
                foreach ($complexity as $c) {
                    Notification::error(" \xC2\xA0 \xC2\xA0 " . $c);
                }
            }
        }

        if ($valid->hasErrors()) {
            $_SESSION['super_op']['field_errors'] = $valid->getFieldErrors();
            Url::redirect('welcome/super_op_form');
        } else {
            unset($_SESSION['super_op']['field_errors']);
        }

        $hashed = Auth::hashPassword($_POST['password1'], Constants::PASSWORD_BCRYPT12);

        $params = [
            'user' => $_POST['username'],
            'hash' => $hashed[0],
            'salt' => $hashed[2],
        ];
        Url::redirect('welcome/super_op_result?' . http_build_query($params));
    }


    /**
     * Show the generated super operator details
     */
    public function superOperatorResult()
    {
        $users = AdminAuth::injectLocalSuperConf($_GET['user'], $_GET['hash'], $_GET['salt']);

        $view = new View('modules/Welcome/super_op_result');
        $view->users = $users;

        $skin = new View('sprout/admin/login_layout');
        $skin->browser_title = 'Super operator';
        $skin->main_title = 'Super operator';
        $skin->main_content = $view->render();
        echo $skin->render();
    }


    /**
     * Add sample content
     */
    public function addSampleAction()
    {
        $this->addSamplePages();

        Notification::confirm('Sample content has been added');
        Url::redirect('welcome/checklist');
    }


    private function addSamplePages()
    {
        $xml = file_get_contents(DOCROOT . 'modules/Welcome/sample_pages.xml');
        $xml = simplexml_load_string($xml);

        $parent_lookup = [];
        foreach ($xml->page as $elem) {
            $name = (string)$elem['name'];
            $page_id = (int)$elem['id'];
            $template = (string)$elem['template'];
            $content = (string)$elem;

            if (isset($elem['parent'])) {
                $parent_id = $parent_lookup[(string)$elem['parent']];
            } else {
                $parent_id = 0;
            }

            $data = [];
            $data['parent_id'] = $parent_id;
            $data['subsite_id'] = 1;
            $data['name'] = $name;
            $data['slug'] = Enc::urlname($name);
            $data['active'] = 1;
            $data['show_in_nav'] = 1;
            $data['alt_template'] = ($template ?: 'skin/inner');
            $data['date_added'] = Pdb::now();
            $data['date_modified'] = Pdb::now();
            $page_id = Pdb::insert('pages', $data);
            $parent_lookup[$name] = $page_id;

            $data = [];
            $data['page_id'] = $page_id;
            $data['type'] = 'standard';
            $data['status'] = 'live';
            $data['modified_editor'] = 'Sample pages tool';
            $data['changes_made'] = 'Added sample page';
            $data['date_added'] = Pdb::now();
            $data['date_modified'] = Pdb::now();
            $revision_id = Pdb::insert('page_revisions', $data);

            $data = [];
            $data['page_revision_id'] = $revision_id;
            $data['area_id'] = 1;
            $data['active'] = 1;
            $data['type'] = 'RichText';
            $data['settings'] = json_encode(['text' => $content]);
            $data['record_order'] = 1;
            Pdb::insert('page_widgets', $data);
        }
    }

}
