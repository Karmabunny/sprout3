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

use Exception;
use InvalidArgumentException;
use ReflectionException;
use ReflectionMethod;

use Kohana;
use Kohana_404_Exception;

use Sprout\Controllers\Admin\CategoryAdminController;
use Sprout\Controllers\Admin\ManagedAdminController;
use Sprout\Controllers\Admin\PageAdminController;
use karmabunny\pdb\Exceptions\ConstraintQueryException;
use karmabunny\pdb\Exceptions\QueryException;
use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Helpers\Admin;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AdminDashboard;
use Sprout\Helpers\AdminError;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\AdminSeo;
use Sprout\Helpers\BaseView;
use Sprout\Helpers\Category;
use Sprout\Helpers\Constants;
use Sprout\Helpers\Cron;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\FileIndexing;
use Sprout\Helpers\Form;
use Sprout\Helpers\Html;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\ModerateInterface;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\PerRecordPerms;
use Sprout\Helpers\Register;
use Sprout\Helpers\Replication;
use Sprout\Helpers\Request;
use Sprout\Helpers\Router;
use Sprout\Helpers\Session;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Subsites;
use Sprout\Helpers\Tags;
use Sprout\Helpers\Text;
use Sprout\Helpers\TwoFactor\GoogleAuthenticator;
use Sprout\Helpers\Upload;
use Sprout\Helpers\Url;
use Sprout\Helpers\UserAgent;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\Validator;

/**
 * Main class to handle admin processing.
 * This delegates processing to controllers registered with {@see Register::adminControllers}
 */
class AdminController extends Controller
{

    /**
    * Does some general admin loading
    **/
    public function __construct()
    {
        parent::__construct();
        Session::instance();

        // Check the IP whitelist
        if (PHP_SAPI != 'cli') {
            $whitelist = Kohana::config('sprout.admin_ips');
            if ($whitelist and count($whitelist) > 0) {
                if (! Sprout::ipaddressInArray(Request::userIp(), $whitelist)) {
                    throw new Kohana_404_Exception();
                }
            }
        }

        // If it's the wrong server, switch to the right one.
        $admin_url = Replication::adminUrl();
        if ($admin_url) {
            Url::redirect($admin_url);
        }

        AdminPerms::loadAccessFlags();

        // A little domain-name check for multi-site installs
        $domain = Kohana::config('sprout.admin_domain');
        if ($domain and $domain != $_SERVER['HTTP_HOST']) {
            Url::redirect('http://' . $domain . Kohana::config('config.site_domain') . Url::current());
        }

        Register::docImport('csv', 'Sprout\\Helpers\\DocImport\\DocImportCSV', 'CSV');
        Register::docImport('txt', 'Sprout\\Helpers\\DocImport\\DocImportPlaintext', 'Plain text');
        Register::docImport('docx', 'Sprout\\Helpers\\DocImport\\DocImportDOCX', 'Microsoft Word 2007 and later');
        Register::coreContentControllers();

        // Most methods require auth, but a few do not
        $methods_no_auth = ['login', 'loginAction', 'loginTwoFactor', 'loginTwoFactorAction', 'logout', 'userAgent'];

        // Also, some initalisation doesn't work properly when not authenticated
        if (!in_array(Router::$method, $methods_no_auth) and PHP_SAPI !== 'cli') {
            AdminAuth::checkLogin();

            // Load page tree
            Navigation::loadPageTree(@$_SESSION['admin']['active_subsite'], true);

            // Execute some code for each module
            // This usually just loads some menu items
            $module_paths = Register::getModuleDirs();
            foreach ($module_paths as $path) {
                $path .= '/admin_load.php';
                if (file_exists($path)) include_once $path;
            }
        }

        // Default config
        if (! Kohana::config('sprout.admin_intro')) {
            Kohana::configSet('sprout.admin_intro', 'admin/dashboard');
        }
    }

    /**
    * Home page of admin area
    **/
    public function index()
    {
        AdminAuth::checkLogin();
        Url::redirect(Kohana::config('sprout.admin_intro'));
    }

    /**
    * Shows a login form
    **/
    public function login()
    {
        if (AdminAuth::isLoggedIn()) {
            Url::redirect(Kohana::config('sprout.admin_intro'));
        }

        $view = new PhpView('sprout/admin/login_layout');
        $this->setDefaultMainviewParams($view);

        $view->nav = null;
        $view->admin_authenticated = false;
        $view->browser_title = 'Login';
        $view->main_title = 'Login';

        $msg = Sprout::extraPage(Constants::EXTRAPAGES_ADMIN_LOGIN);
        if ($msg and empty($_GET['nomsg'])) {
            $view->main_content = new PhpView('sprout/admin/login_message');
            $view->main_content->msg = $msg;

        } else {
            $view->main_content = new PhpView('sprout/admin/login_form');
        }

        if (!empty($_GET['username'])) {
            $view->main_content->username = trim($_GET['username']);
        }

        echo $view->render();
    }

    /**
    * Processes a user login
    **/
    public function loginAction()
    {
        Csrf::checkOrDie();

        Session::instance();
        Session::regenerate();

        $_POST['Username'] = trim($_POST['Username']);
        $_POST['Password'] = trim($_POST['Password']);
        $_POST['redirect'] = trim($_POST['redirect']);

        if ($_POST['Username'] == '' or $_POST['Password'] == '') {
            Notification::error("Username or password not specified.");
            Url::redirect('admin/login?username=' . Enc::url($_POST['Username']) . '&redirect=' . Enc::url($_POST['redirect']) . '&nomsg=1');
        }

        $result = AdminAuth::checkRateLimit($_POST['Username'], Request::userIp());

        if ($result !== true) {
            list($aspect, $limit) = $result;
            Notification::error('Login rate limit exceeded.');
            Notification::error("Limit: {$aspect}, {$limit}");
            Url::redirect('admin/login&redirect=' . Enc::url($_POST['redirect']) . '&nomsg=1');
        }

        $result = AdminAuth::processLogin($_POST['Username'], $_POST['Password']);

        if (! $result) {
            $result = AdminAuth::processRemote($_POST['Username'], $_POST['Password']);
        }

        if (! $result) {
            $result = AdminAuth::processLocal($_POST['Username'], $_POST['Password']);
        }

        AdminAuth::saveLoginAttempt($_POST['Username'], Request::userIp(), $result === true ? 1 : 0);

        if (! $result) {
            Notification::error('Incorrect username or password specified');
            Url::redirect('admin/login?username=' . Enc::url($_POST['Username']) . '&redirect=' . Enc::url($_POST['redirect']) . '&nomsg=1');
        }

        // Login requires two-factor auth
        if (isset($_SESSION['admin']['tfa_id'])) {
            Url::redirect('admin/login-two-factor?redirect=' . $_POST['redirect']);
        }

        $this->loginComplete();
    }


    /**
     * Show the two-factor-auth ui for a half-logged-in operator
     */
    public function loginTwoFactor()
    {
        if (!isset($_SESSION['admin']['tfa_id'])) {
            Url::redirect('admin/login');
        }

        try {
            $q = "SELECT tfa_method FROM ~operators WHERE id = ?";
            $tfa_method = Pdb::query($q, [$_SESSION['admin']['tfa_id']], 'val');
        } catch (RowMissingException $ex) {
            Url::redirect('admin/login');
        }

        switch ($tfa_method) {
            case 'none':
                $this->loginComplete();
                break;

            case 'totp':
                $view = new PhpView('sprout/tfa/totp_login');
                $view->action_url = 'admin/login-two-factor-action';
                break;

            default:
                throw new Exception('Unknown TFA method');
        }

        $skin = new PhpView('sprout/admin/login_layout');
        $skin->browser_title = 'Login';
        $skin->main_title = 'Login';
        $skin->main_content = $view->render();
        echo $skin->render();
    }


    /**
     * Process the result of a two-factor-auth for a half-logged-in operator
     */
    public function loginTwoFactorAction()
    {
        if (!isset($_SESSION['admin']['tfa_id'])) {
            Url::redirect('admin/login');
        }

        $_POST['redirect'] = trim($_POST['redirect'] ?? '');

        $q = "SELECT tfa_method, tfa_secret FROM ~operators WHERE id = ?";
        $operator = Pdb::query($q, [$_SESSION['admin']['tfa_id']], 'row');

        switch ($operator['tfa_method']) {
            case 'totp':
                $goog = new GoogleAuthenticator();
                $success = $goog->checkCode($operator['tfa_secret'], $_POST['code']);
                break;

            default:
                throw new Exception('Unknown TFA method');
        }

        if (!$success) {
            Notification::error('Two-factor authentication failed - please try again');
            Url::redirect('admin/login-two-factor?redirect=' . $_POST['redirect']);
        }

        $_SESSION['admin']['login_id'] = $_SESSION['admin']['tfa_id'];
        unset($_SESSION['admin']['tfa_id']);
        $this->loginComplete();
    }


    /**
     * Set up various login params and redirect into admin
     *
     * Called after a successful login (either one-factor or two-factor)
     */
    private function loginComplete()
    {
        if (empty($_POST['Username'])) $_POST['Username'] = '';
        if (empty($_POST['redirect'])) $_POST['redirect'] = '';

        $subsite = Subsites::getFirstAccessable();
        if (! $subsite) {
            Notification::error('No subsites are accessible by your user account');
            Url::redirect('admin/login?username=' . Enc::url($_POST['Username']) . '&redirect=' . Enc::url($_POST['redirect']) . '&nomsg=1');
        }

        // Permissions system requires users to be in a category
        if (!AdminAuth::isSuper()) {
            $cats = Category::categoryList('operators', AdminAuth::getId());
            if (count($cats) == 0) {
                Notification::error('Your user account isn\'t in any categories.');
                Url::redirect('admin/login?username=' . Enc::url($_POST['Username']) . '&redirect=' . Enc::url($_POST['redirect']) . '&nomsg=1');
            }
        }

        $_SESSION['admin']['active_subsite'] = $subsite;

        Notification::confirm('You are now logged in to the admin control panel');
        if (!empty($_POST['redirect']) and Url::checkRedirect($_POST['redirect'], true)) {
            Url::redirect($_POST['redirect']);
        }

        Url::redirect(Kohana::config('sprout.admin_intro'));
    }


    /**
    * Processes a user logout
    **/
    public function logout()
    {
        try {
            Admin::unlock();
        } catch (QueryException $ex) {
            // Assume DB has no tables
        }
        AdminAuth::logout();

        Session::instance();
        Session::regenerate();

        Notification::confirm('You are now logged out');
        Url::redirect('admin/login');
    }


    /**
    * View the various styles available in the admin area
    **/
    public function styleGuide($section)
    {
        $section = preg_replace('![^_a-z]!', '', $section);
        AdminAuth::checkLogin();

        $buttons = new PhpView('sprout/admin/style_guide/index');

        if ($section != 'index') {
            $inner_view = new PhpView('sprout/admin/style_guide/' . $section);
        } else {
            $inner_view = '';
        }

        $view = new PhpView('sprout/admin/main_layout');
        $ctlr = Admin::getController('Sprout\Controllers\Admin\PageAdminController');
        $this->setDefaultMainviewParams($view);
        $this->setNavigation($view, $ctlr);
        $view->controller_name = '_style_guide';
        $view->browser_title = 'Style guide';
        $view->main_title = 'SproutCMS Style Guide';
        $view->main_content = $buttons . $inner_view;

        echo $view->render();
    }


    /**
     * Dashboard shown when a user first logs in to the admin
     */
    public function dashboard()
    {
        AdminAuth::checkLogin();

        $first = AdminPerms::getFirstAccessable();
        if ($first === null) {
            Url::redirect('admin/intro/my_settings');
        } else if ($first != 'page') {
            Url::redirect('admin/intro/' . $first);
        }

        $ctlr = Admin::getController('Sprout\Controllers\Admin\PageAdminController');
        if (! $ctlr) return;

        $dash_html = AdminDashboard::render();

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);
        $this->setNavigation($view, $ctlr);
        $view->controller_name = '_dashboard';
        $view->browser_title = 'Dashboard';
        $view->main_title = 'SproutCMS Administration';
        $view->main_content = $dash_html;
        echo $view->render();
    }


    /**
     * Closes the 'first run' box, which is shown on the admin dashboard
     *
     * @return void Redirects to the admin dashboard
     */
    public function closeFirstrun()
    {
        AdminAuth::checkLogin();

        Pdb::update(
            'operators',
            ['firstrun' => 0],
            ['id' => AdminAuth::getId()]
        );

        Url::redirect('admin/dashboard');
    }


    /**
    * Shows an introduction for a specified type.
    *
    * @param string $type The type to show an intro for.
    **/
    public function intro($type)
    {
        AdminAuth::checkLogin();

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);

        $this->setNavigation($view, $ctlr);

        $main = $ctlr->_intro();
        if (! is_array($main)) {
            $main = array('title' => $ctlr->getFriendlyName(), 'content' => $main);
        }

        $view->browser_title = $ctlr->getFriendlyName();
        $view->main_title = $ctlr->getFriendlyName();
        $view->main_title = $main['title'];
        $view->main_content = $main['content'];
        echo $view->render();
    }


    /**
    * Shows a search form for the specified item
    *
    * @param string $type The type of item to show the search form for
    **/
    public function search($type)
    {
        AdminAuth::checkLogin();

        $this->unlock($type);

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'contents', false)) return;

        $this->setNavigation($view, $ctlr);

        $main = $ctlr->_getSearchForm();
        if ($main instanceof AdminError) {
            $this->error($main->getMessage(), $ctlr);
            return;
        }

        if (! is_array($main)) {
            $main = array('title' => $ctlr->getFriendlyName(), 'content' => $main);
        }

        if (!isset($main['title']) or !isset($main['content'])) {
            throw new InvalidArgumentException('Return value from _getSearchForm must contain title + content');
        }

        $view->browser_title = strip_tags($main['title']);
        $view->main_title = $main['title'];
        $view->main_content = $main['content'];

        echo $view->render();
    }


    /**
    * Shows an edit form for the specified item
    *
    * @param string $type The type of item to show the edit form of
    **/
    public function contents($type)
    {
        AdminAuth::checkLogin();

        $this->unlock($type);

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'contents', false)) return;

        $this->setNavigation($view, $ctlr);

        $main = $ctlr->_getContents();
        if ($main instanceof AdminError) {
            $this->error($main->getMessage(), $ctlr);
            return;
        }

        if (! is_array($main)) {
            $main = array('title' => $ctlr->getFriendlyName(), 'content' => $main);
        }

        if (!isset($main['title']) or !isset($main['content'])) {
            throw new InvalidArgumentException('Return value from _getContents must contain title + content');
        }

        $view->browser_title = strip_tags($main['title']);
        $view->main_title = $main['title'];
        $view->main_content = $main['content'];

        echo $view->render();
    }


    /**
    * Shows an export form for the specified type
    *
    * @param string $type The type of item to show the export form of
    **/
    public function export($type)
    {
        AdminAuth::checkLogin();

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'export', false)) return;

        $this->setNavigation($view, $ctlr);

        $main = $ctlr->_getExport();
        if ($main instanceof AdminError) {
            $this->error($main->getMessage(), $ctlr);
            return;
        }

        if (!isset($main['title']) or !isset($main['content'])) {
            throw new InvalidArgumentException('Return value from _getExport must contain title + content');
        }

        $view->browser_title = strip_tags($main['title']);
        $view->main_title = $main['title'];
        $view->main_content = $main['content'];

        echo $view->render();
    }

    /**
    * Executes the export action for a specific item
    *
    * @param string $type The type of item to export
    **/
    public function exportAction($type)
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'export', true)) return;

        $result = $ctlr->_exportData();

        if ($result == false) {
            Notification::error('There was an error performing the export');
            Url::redirect("admin/export/{$type}");
        }

        $length = strlen($result['data']);
        header("Content-type: {$result['type']}");
        header("Content-disposition: attachment; filename={$result['filename']}");
        header("Content-length: {$length}");

        // MSIE needs "public" when under SSL - http://support.microsoft.com/kb/316431
        header('Pragma: public');
        header('Cache-Control: public, max-age=1');

        echo $result['data'];
    }


    /**
    * File upload box for importing, options are the next step
    *
    * @param string $type The type of item to show the import form of
    **/
    public function importUpload($type)
    {
        AdminAuth::checkLogin();

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'import', false)) return;

        $this->setNavigation($view, $ctlr);

        if ($type == 'page') {
            $title = 'Document import';
            $main = $ctlr->_importUploadForm();

        } else {
            $title = 'Import ' . strtolower($ctlr->getFriendlyName());
            $main = new PhpView('sprout/admin/import_upload');
            $main->type = $type;
            $main->xls = FileIndexing::isExtSupported('xls');
        }

        $view->browser_title = strip_tags($title);
        $view->main_title = $title;
        $view->main_content = $main;

        echo $view->render();
    }

    /**
    * Copies the file to a temporary directory
    **/
    public function importUploadAction($type)
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $formats = array('csv');
        if (FileIndexing::isExtSupported('xls')) {
            $formats[] = 'xls';
        }

        // validate upload
        $error = false;
        if (! Upload::required($_FILES['import'])) {
            $error = 'No file provided';
        } else if (! Upload::valid($_FILES['import'])) {
            $error = 'File upload error';
        } else if (! Upload::type($_FILES['import'], $formats)) {
            $error = 'Incorrect file type, accepted types are: ' . implode(', ', $formats);
        }

        if (! $error) {
            $timestamp = time();
            $tempname = STORAGE_PATH . "temp/import_{$timestamp}.csv";

            if (preg_match('/\.xls$/', $_FILES['import']['name'])) {
                // Load XLS from fileindexing tool
                $plaintext = FileIndexing::getPlaintext($_FILES['import']['tmp_name'], 'xls');
                if (! $plaintext) {
                    $error = 'Unable to copy file to temporary directory (read)';
                }

                $res = @file_put_contents($tempname, $plaintext);
                if ($res === false) {
                    $error = 'Unable to copy file to temporary directory (write)';
                }

            } else if (preg_match('/\.csv$/', $_FILES['import']['name'])) {
                // Copy the CSV directly
                $res = @copy($_FILES['import']['tmp_name'], $tempname);
                if (! $res) {
                    $error = 'Unable to copy file to temporary directory';
                }

            } else {
                $error = 'Unknown file type';
            }
        }

        if ($error) {
            Notification::error($error);
            Url::redirect("admin/import_upload/{$type}");
        }

        Url::redirect("admin/import_options/{$type}?timestamp={$timestamp}");
    }

    /**
    * Shows the import form for the specified item
    *
    * @param string $type The type of item to show the import form of
    **/
    public function importOptions($type)
    {
        AdminAuth::checkLogin();

        $_GET['timestamp'] = (int)@$_GET['timestamp'];

        $_GET['ext'] = trim($_GET['ext'] ?? '');
        if (! $_GET['ext']) $_GET['ext'] = 'csv';

        $filename = STORAGE_PATH . "temp/import_{$_GET['timestamp']}.{$_GET['ext']}";
        if (! file_exists($filename)) {
            $this->error("Uploaded import file not found on server");
            return;
        }

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'import', false)) return;

        $this->setNavigation($view, $ctlr);

        $main = $ctlr->_getImport($filename);
        if ($main instanceof AdminError) {
            $this->error($main->getMessage(), $ctlr);
            return;
        }

        if (! is_array($main)) {
            $main = array('title' => $ctlr->getFriendlyName(), 'content' => $main);
        }

        if (!isset($main['title']) or !isset($main['content'])) {
            throw new InvalidArgumentException('Return value from _getSearchForm must contain title + content');
        }

        $view->browser_title = strip_tags($main['title']);
        $view->main_title = $main['title'];
        $view->main_content = $main['content'];

        echo $view->render();
    }

    /**
    * Executes the import action
    *
    * @param string $type The type of item to import
    **/
    public function importAction($type)
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $_POST['timestamp'] = (int) @$_POST['timestamp'];

        $_POST['ext'] = trim($_POST['ext'] ?? '');
        if (! $_POST['ext']) $_POST['ext'] = 'csv';

        $filename = STORAGE_PATH . "temp/import_{$_POST['timestamp']}.{$_POST['ext']}";
        if (! file_exists($filename)) {
            $this->error("Uploaded import file not found on server");
            return;
        }

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'import', true)) return;

        $result = $ctlr->_importData($filename);

        if ($result == false) {
            Notification::error('There was an error performing the import');
            Url::redirect("admin/import_options/{$type}?timestamp={$_POST['timestamp']}&ext={$_POST['ext']}");
        }

        $ctlr->_invalidateCaches('import');

        @unlink($filename);

        Notification::confirm('Import has been completed successfully');
        Url::redirect("admin/contents/{$type}");
    }

    /**
    * Shows the import form for the specified item
    *
    * @param string $type The type of item to show the import form of
    **/
    public function emailReports($type)
    {
        AdminAuth::checkLogin();

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'email_report', false)) return;

        $this->setNavigation($view, $ctlr);

        $main = $ctlr->_getEmailReports();
        if ($main instanceof AdminError) {
            $this->error($main->getMessage(), $ctlr);
            return;
        }

        if (! is_array($main)) $main = array('title' => $ctlr->getFriendlyName(), 'content' => $main);

        $view->browser_title = strip_tags($main['title']);
        $view->main_title = $main['title'];
        $view->main_content = $main['content'];

        echo $view->render();
    }

    /**
    * Shows the import form for the specified item
    *
    * @param string $type The type of item to show the import form of
    **/
    public function emailReportAdd($type)
    {
        AdminAuth::checkLogin();

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'email_report', 'add')) return;

        $this->setNavigation($view, $ctlr);

        $main = $ctlr->_addEmailReport();
        if ($main instanceof AdminError) {
            $this->error($main->getMessage(), $ctlr);
            return;
        }

        if (! is_array($main)) $main = array('title' => $ctlr->getFriendlyName(), 'content' => $main);

        $view->browser_title = strip_tags($main['title']);
        $view->main_title = $main['title'];
        $view->main_content = $main['content'];

        unset($_SESSION['admin']['field_values']);
        unset($_SESSION['admin']['field_errors']);

        echo $view->render();
    }

    /**
    * Executes the save action for a specific email report
    *
    * @param string $type The type of item to save
    **/
    public function emailReportAction($type)
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'email_report', 'add')) return;

        $_SESSION['admin']['field_values'] = $_POST;

        $valid = new Validator($_POST);
        $valid->required(['email_report_name', 'email_report_format', 'multiedit_recipients']);

        $has_email = false;
        foreach($_POST['multiedit_recipients'] as $recipient) {
            if (empty($recipient['name']) and empty($recipient['email'])) continue;
            $has_email = true;
            if (empty($recipient['name']) or empty($recipient['email'])) {
                $valid->addGeneralError('Recipients must contain both a name and email');
                Notification::error('Recipients must contain both a name and email');
            }
        }

        if (!$has_email) {
            $valid->addGeneralError('Please add at least one recipient');
            Notification::error('Please add at least one recipient');
        }

        if ($valid->hasErrors()) {
            $_SESSION['admin']['field_errors'] = $valid->getFieldErrors();
            Notification::error('Please ensure all required fields are completed');
            Url::redirect('admin/email_report_add/' . $ctlr->getControllerName());
        }

        Pdb::transact();
        $now = Pdb::now();

        $update_fields = [];
        $update_fields['name'] = $_POST['email_report_name'];
        $update_fields['filters'] = $_POST['refine_fields']; // Json encoded at post
        $update_fields['format'] = $_POST['email_report_format'];
        $update_fields['controller'] = $ctlr->getControllerName();
        $update_fields['controller_class'] = get_class($ctlr);
        $update_fields['active'] = (int) ($_POST['email_report_active'] ?? 0);

        $admin = AdminAuth::getDetails();
        $update_fields['created_operator'] = $admin['name'];

        $update_fields['date_added'] = $now;
        $update_fields['date_modified'] = $now;

        $report_id = Pdb::insert('email_reports', $update_fields);

        $idx = 1;

        foreach ($_POST['multiedit_recipients'] as $recipient) {
            $update_fields = [];
            $update_fields['email_report_id'] = $report_id;
            $update_fields['name'] = $recipient['name'];
            $update_fields['email'] = $recipient['email'];
            $update_fields['record_order'] = $idx++;
            Pdb::insert('email_report_recipients', $update_fields);
        }

        Pdb::commit();

        unset($_SESSION['admin']['field_values']);
        unset($_SESSION['admin']['field_errors']);

        Notification::confirm('Your email report has been created');
        Url::redirect('admin/email_reports/' . $ctlr->getControllerName());
    }

    /**
    * Sends a specified email report
    *
    * @param int $report_id The report to load and send
    **/
    public function emailReportSend($report_id)
    {
        AdminAuth::checkLogin();

        $report = Pdb::get('email_reports', $report_id);

        $ctlr = Admin::getController($report['controller_class']);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'email_report', true)) return;

        $res = $ctlr->_sendEmailReport($report);
        if ($res) {
            Notification::confirm('Report has been sent');
        } else {
            Notification::error('Report sending  failed');
        }

        Url::redirect('admin/email_reports/' . $ctlr->getControllerName());
    }


    /**
    * Shows an error message in the admin skin
    *
    * @param string $message The message to show. Should be plain-text.
    * @param ManagedAdminController $ctlr A controller to show the navigation of.
    **/
    private function error($message, ManagedAdminController $ctlr = null)
    {
        AdminAuth::checkLogin();

        $content = new PhpView('sprout/admin/error');
        $content->message = $message;

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);

        if ($ctlr) {
            $this->setNavigation($view, $ctlr);
        }

        $view->browser_title = 'Error';
        $view->main_title = 'Error';
        $view->main_content = $content;

        echo $view->render();
    }


    /**
    * Check access for a given controller/access flag combo
    * If this returns false, it will echo out an error message; the calling code should just return.

    * @param ManagedAdminController $ctlr A controller to check
    * @param string $access_flag The access flag to check, e.g. 'add', 'edit', etc
    * @param bool $action True if it's an action method, false if it's a form method.
    * @return bool True if auth is okay, false if it is not.
    **/
    private function checkAccess(ManagedAdminController $ctlr, $access_flag, $action)
    {
        AdminAuth::checkLogin();

        if ($ctlr instanceof CategoryAdminController) {
            $ctlr = $ctlr->getParentInst();
            $access_flag = 'categories';
        }

        if (AdminPerms::controllerAccess($ctlr->getControllerName(), $access_flag)) {
            return true;
        }

        if ($action) {
            Notification::error('Access Denied; Section: ' . $ctlr->getFriendlyName() . '; Action: ' . $access_flag);
            Url::redirect('admin');

        } else {
            $content = new PhpView('sprout/admin/access_denied');
            $content->friendly_name = $ctlr->getFriendlyName();
            $content->access_flag = $access_flag;

            $view = new PhpView('sprout/admin/main_layout');
            $this->setDefaultMainviewParams($view);

            if ($ctlr) {
                $this->setNavigation($view, $ctlr);
            }

            $view->browser_title = 'Access denied';
            $view->main_title = 'Access denied';
            $view->main_content = $content;
            echo $view->render();
        }

        return false;
    }


    /**
     * Checks that an admin user has access to an individual record
     *
     * @param ManagedAdminController $ctlr The controller which manages the table containing the record
     * @param int $item_id The id of the row to be edited/deleted
     * @return bool True if access allowed
     */
    function checkRecordAccess(ManagedAdminController $ctlr, $item_id)
    {
        $restrict = PerRecordPerms::controllerRestricted($ctlr);
        if (!$restrict) return true;

        $params = [];
        $cat_clause = PerRecordPerms::getCategoryClause();

        $params[] = $ctlr->getControllerName();
        $params[] = $item_id;

        $q = "SELECT {$cat_clause}
            FROM ~per_record_permissions
            WHERE controller = ? AND item_id = ?";
        $res = Pdb::q($q, $params, 'col');

        if (count($res) == 0) return true;

        return (bool) Sprout::iterableFirstValue($res);
    }


    /**
     * Ensure 'active' flag and 'tags' POST fields have values set, if they are nonexistant
     *
     * @param ManagedAdminController $ctlr
     */
    private function cleanupCommonPostData(ManagedAdminController $ctlr)
    {
        if (!isset($_POST['tags'])) {
            $_POST['tags'] = '';
        }

        $visibility = $ctlr->_getVisibilityFields();
        foreach ($visibility as $name => $label) {
            if (empty($_POST[$name])) {
                $_POST[$name] = 0;
            } elseif ($_POST[$name] != '0' and $_POST[$name] != '1') {
                $_POST[$name] = 0;
            }
        }

        // Ensure that the session always has data, so that the initial lookup is treated
        // differently from a form submission with no categories selected
        if (!isset($_POST['_prm_categories'])) {
            $_POST['_prm_categories'] = [];
        }
    }


    /**
     * Render a list of sub-actions into HTML as links
     *
     * Each sub-action should be an array, with the following keys:
     *    url     Link URL
     *    name    Link text
     *    class   Optional class
     *    new_tab Optional bool to show in new window/tab
     *
     * Use a special entry with a key of "_preview" and a value of the
     * preview URL to set up a preview button
     *
     * @param array $list Sub-actions to render
     * @return HTML
     */
    private function renderSubActions(array $list)
    {
        $out = '<ul class="list-style-1">';

        foreach ($list as $key => $item) {
            if ($key === '_preview') continue;

            $class = 'sub-action';
            if (isset($item['class'])) $class .= ' ' . $item['class'];

            $out .= '<li>';
            $out .= '<a href="' . Enc::html($item['url']) . '" class="' . Enc::html($class) . '"';
            if (isset($item['new_tab']) and $item['new_tab'] === true) {
                $out .= ' target="_blank"';
            }
            $out .= '>';
            $out .= Enc::html($item['name']);
            $out .= '</a>';
            $out .= '</li>';
        }

        $out .= '</ul>';

        return $out;
    }


    /**
     * Generates HTML for fields relating to per-record permissions in the 'save changes' box
     *
     * @param string ManagedAdminController $ctlr The controller to check permissions for
     * @param int $item_id The ID of the record being edited (0 when adding a new record)
     * @return string HTML
     */
    protected function perRecordPermissionsFields(ManagedAdminController $ctlr, $item_id)
    {
        if (!PerRecordPerms::controllerRestricted($ctlr)) {
            return '';
        }

        // Preload operator categories for per-user permissions
        if ($item_id > 0) {
            $q = "SELECT operator_categories
                FROM ~per_record_permissions
                WHERE controller = ? AND item_id = ?";
            $access = Pdb::q($q, [$ctlr->getControllerName(), $item_id], 'arr');
            if (count($access) > 0) {
                $access = Sprout::iterableFirstValue($access);

                if (Form::getData('_prm_categories') === null) {
                    $cat_ids = array_filter(explode(',', trim($access['operator_categories'], ',')));
                    Form::setFieldValue('_prm_categories', $cat_ids);
                }
            }
        }

        $out = '';
        if (AdminPerms::canAccess('access_operators')) {
            $cat_list = AdminAuth::getAllCategories();
        } else {
            $cat_list = [];
            $cat_ids = AdminAuth::getOperatorCategories();

            if (count($cat_ids) > 0) {
                $params = [];
                $conds = [
                    ['id', 'IN', $cat_ids],
                ];
                $where = Pdb::buildClause($conds, $params);

                $q = "SELECT id, name
                    FROM ~operators_cat_list
                    WHERE {$where}
                    ORDER BY name";
                $cat_list = Pdb::q($q, $params, 'map');
            }
        }

        // Don't display primary administrators category; they always get access
        $primary_cat_id = AdminAuth::getPrimaryCategoryId();
        unset($cat_list[$primary_cat_id]);

        $checked_cats = Form::getData('_prm_categories');

        // Pre-tick all categories if on add form
        // N.B. primary admins don't have any categories ticked because they belong to ALL categories,
        // and it defeats the purpose of per-record controls if everyone has access by default.
        if ($item_id == 0 and count($checked_cats) == 0) {
            if (!AdminAuth::inCategory($primary_cat_id)) {
                $checked_cats = array_keys($cat_list);
                Form::setFieldValue('_prm_categories', $checked_cats);
            }
        }

        $allow_cats = '';
        if ($item_id == 0 or AdminAuth::inCategory($primary_cat_id)) {
            Form::nextFieldDetails('Allow changes by', false);
            $allow_cats = Form::checkboxSet('_prm_categories', [], $cat_list);

            // Hack in 'all operators' option for primary admins
            if (AdminAuth::inCategory($primary_cat_id)) {
                $all = '<div class="field-element__input-set">';
                $all .= '<div class="fieldset-input"><input type="checkbox" value="1" name="_prm_all_cats" id="_prm_all"';
                if ($checked_cats == ['*'] or ($item_id == 0 and Form::getData('_prm_all_cats'))) {
                    $all .= ' checked';
                }
                $all .= '><label for="_prm_all">All operators</label></div>';
                $allow_cats = str_replace('<div class="field-element__input-set">', $all, $allow_cats);
            }
        }

        $out .= $allow_cats;

        return $out;
    }


    /**
    * Shows an add form for the specified item
    *
    * @param string $type The type of item to show the add form of
    **/
    public function add($type)
    {
        AdminAuth::checkLogin();

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'add', false)) return;

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);

        $this->setNavigation($view, $ctlr);

        $main = $ctlr->_getAddForm();
        if ($main instanceof AdminError) {
            $this->error($main->getMessage(), $ctlr);
            return;
        }
        if (!is_array($main)) {
            throw new InvalidArgumentException('Return value from _getAddForm must be an array');
        }
        if (!isset($main['title']) or !isset($main['content'])) {
            throw new InvalidArgumentException('Return value from _getAddForm must contain title + content');
        }
        if ($ctlr->_isAddSaved() and Text::containsFormTag($main['content'])) {
            throw new Exception("Add view must not include the form tag");
        }

        if (Request::isAjax()) {
            $class = 'admin-ajax action-add type-' . Enc::id($type);
            echo '<h2 class="popup-title">', $main['title'], '</h2>';
            echo '<div class="', $class, '">';
            echo '<form action="admin/add_save/' . Enc::html($ctlr->getControllerName()) . '" method="post">';
            echo Csrf::token();
            echo $main['content'];
            echo '<div class="action-bar"><button type="submit" class="button button-regular button-green icon-after icon-save">Save changes</button></div>';
            echo '</form>';
            echo '</div>';
            return;
        }

        // Create tags area, and inject it into content after the <form> tag
        $tags = new PhpView('sprout/admin/main_tags');
        $tags->type = $type;
        $tags->suggestions = Tags::suggestTags($ctlr->getTableName());
        $tags->table = $ctlr->getTableName();

        $tags->current_tags = @$_SESSION['admin']['tags'];
        unset ($_SESSION['admin']['tags']);

        if ($ctlr->_isAddSaved()) {
            $single = Inflector::singular($ctlr->getFriendlyName());
            $content = '<form action="admin/add_save/' . Enc::html($ctlr->getControllerName()) . '" method="post" id="edit-form" class="-clearfix">';

            $content .= Csrf::token();
            $content .= '<div class="mainbar-with-right-sidebar">';
            $content .= $tags->render();
            $content .= $main['content'];
            $content .= '</div>';

            $content .= '<div class="right-sidebar">';
            $content .= '<div class="right-sidebar-inner">';
            $content .= '<div class="save-changes-box">';

            $html = $ctlr->_getCustomAddSaveHTML();
            if ($html) {
                $content .= $html;
            } else {
                $visibility = $ctlr->_getVisibilityFields();
                $sub_actions = $ctlr->_getAddSubActions();

                $content .= '<h2 class="icon-before icon-add">Add ' . Enc::html($single) . '</h2>';
                if (!empty($visibility)) {
                    Form::nextFieldDetails('Visibility', false);
                    $content .= Form::checkboxBoolList(null, [], $visibility);
                }

                $content .= $this->perRecordPermissionsFields($ctlr, 0);

                if ($ctlr->isPerSubsite()) {
                    $subsites = Pdb::lookup('subsites');
                    Form::nextFieldDetails('Subsite', false);
                    Form::setFieldValue('subsite_id', $_SESSION['admin']['active_subsite']);
                    $content .= Form::dropdown('subsite_id', ['-dropdown-top' => 'Show on all sites'], $subsites);
                }

                $content .= $this->renderSubActions($sub_actions);
                $content .= '<div class="save-changes-box-bottom -clearfix">';
                if (!empty($sub_actions['_preview'])) {
                    $content .= '<a href="' . Enc::html($sub_actions['_preview']) . '" class="save-changes-preview-button button button-regular button-blue icon-after icon-remove_red_eye">Preview</a>';
                }
                $content .= '<button type="submit" class="save-changes-save-button button button-regular button-green icon-after icon-add">Save changes</button>';
                $content .= '</div>';
            }

            $content .= '</div>';
            $content .= '</div>';
            $content .= '</div>';

            $content .= '</form>';
        } else {
            $content = $main['content'];
        }

        $view->browser_title = strip_tags($main['title']);
        $view->main_title = $main['title'];
        $view->main_content = $content;
        $view->has_tags = true;
        $view->main_class = 'do-action-box';

        echo $view->render();
    }

    /**
    * Executes the save action for a specific item
    *
    * @param string $type The type of item to add
    **/
    public function addSave($type)
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'add', true)) return;

        $this->cleanupCommonPostData($ctlr);

        $_SESSION['admin']['tags'] = $_POST['tags'];

        $id = 0;
        $result = $ctlr->_addSave($id);

        // Set per-record permissions
        if ($result) {
            PerRecordPerms::save($ctlr, $id);
        }

        if (Request::isAjax()) {
            $result = (int) $result;
            echo json_encode(array('result' => $result));
            exit;
        }

        if ($result == false) {
            Notification::error('There was an error saving your changes');
            if (!empty($_POST['current_url'])) {
                Url::redirect($_POST['current_url']);
            }
            Url::redirect("admin/add/{$type}");
        }

        $new_tags = Tags::splitupTags($_POST['tags']);
        $tag_result = Tags::update($ctlr->getTableName(), $id, $new_tags);
        unset ($_SESSION['admin']['tags']);

        if ($tag_result == false) {
            Notification::error('There was an error updating the tags for this item');
        }

        $ctlr->_invalidateCaches('add', $id);

        unset ($_SESSION['admin']['field_values']);

        $single = strtolower(Inflector::singular($ctlr->getFriendlyName()));
        $message = "Your {$single} has been added";

        if (!Notification::has(Notification::TYPE_CONFIRM)) {
            Notification::confirm($message, []);
        }

        if (is_string($result)) {
            Url::redirect($result);
        } else {
            Url::redirect("admin/edit/{$type}/{$id}");
        }
    }

    /**
    * Shows an edit form for the specified item
    *
    * @param string $type The type of item to show the edit form of
    * @param int $id The id of the record to edit
    **/
    public function edit($type, $id)
    {
        AdminAuth::checkLogin();
        $id = (int) $id;

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);
        $view->has_tags = true;

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'edit', false)) return;
        if (! $this->checkRecordAccess($ctlr, $id)) return;

        $this->setNavigation($view, $ctlr);

        $main = $ctlr->_getEditForm($id);
        if ($main instanceof AdminError) {
            $this->error($main->getMessage(), $ctlr);
            return;
        }
        if (!is_array($main)) {
            throw new InvalidArgumentException('Return value from _getEditForm must be an array');
        }
        if (!isset($main['title']) or !isset($main['content'])) {
            throw new InvalidArgumentException('Return value from _getEditForm must contain title + content');
        }
        // Disallow view if it contains a <FORM> tag or output will contain nested-forms and that doesn't work
        if ($ctlr->_isEditSaved($id) and Text::containsFormTag($main['content'])) {
            throw new Exception("Edit view must not include the form tag");
        }

        // Create tags area, and inject it into content after the <form> tag
        $tags = new PhpView('sprout/admin/main_tags');
        $tags->suggestions = Tags::suggestTags($ctlr->getTableName());
        $tags->table = $ctlr->getTableName();

        $tags->current_tags = @$_SESSION['admin']['tags'];
        if (empty($_SESSION['admin']['tags'])) {
            $tags->current_tags = implode(', ', Tags::byRecord($ctlr->getTableName(), $id));
        }
        unset ($_SESSION['admin']['tags']);

        // Check for SEO enabled content
        $view->enable_seo = !empty(AdminSeo::$content)? true : false;

        if ($ctlr->_isEditSaved($id)) {
            $content = '<form action="admin/edit_save/' . Enc::html($ctlr->getControllerName()) . '/' . $id;
            $content .= '" method="post" id="edit-form" class="-clearfix" enctype="multipart/form-data">';
            $content .= Csrf::token();
            $content .= '<div class="mainbar-with-right-sidebar">';
            $content .= $tags->render();
            $content .= AdminSeo::getAnalysis();
            $content .= $main['content'];
            $content .= '</div>';

            $content .= '<div class="right-sidebar">';
            $content .= '<div class="right-sidebar-inner">';
            $content .= '<div class="save-changes-box">';

            $html = $ctlr->_getCustomEditSaveHTML($id);
            if ($html) {
                $content .= $html;
            } else {
                $visibility = $ctlr->_getVisibilityFields();
                $sub_actions = $ctlr->_getEditSubActions($id);

                $content .= '<h2 class="icon-before icon-save">Save changes</h2>';
                if (!empty($visibility)) {
                    Form::nextFieldDetails('Visibility', false);
                    $content .= Form::checkboxBoolList(null, [], $visibility);
                }

                $content .= $this->perRecordPermissionsFields($ctlr, $id);

                if ($ctlr->isPerSubsite()) {
                    $subsites = Pdb::lookup('subsites');
                    Form::nextFieldDetails('Subsite', false);
                    $content .= Form::dropdown('subsite_id', ['-dropdown-top' => 'Show on all sites'], $subsites);
                }
                $content .= $this->renderSubActions($sub_actions);
                $content .= '<div class="save-changes-box-bottom -clearfix">';
                if (!empty($sub_actions['_preview'])) {
                    $content .= '<a href="' . Enc::html($sub_actions['_preview']) . '" class="save-changes-preview-button button button-regular button-blue icon-after icon-remove_red_eye">Preview</a>';
                }
                $content .= '<button type="submit" class="save-changes-save-button button button-regular button-green icon-after icon-save">Save changes</button>';
                $content .= '</div>';
            }

            $content .= '</div>';
            $content .= '</div>';
            $content .= '</div>';

            $content .= '</form>';
        } else {
            $content = $main['content'];
        }

        $this->lock($type, $id, $view);

        // Render the main view
        $view->browser_title = Text::limitChars(strip_tags($main['title']), 50, '...');
        $view->main_title = $main['title'];
        $view->main_content = $content;
        $view->main_class = 'do-action-box';

        $url = $ctlr->_getEditLiveUrl($id);
        if ($url) {
            $view->live_url = Admin::ensureUrlAbsolute($url);
        }

        echo $view->render();
    }

    /**
    * Executes the save action for a specific item
    *
    * @param string $type The type of item to save
    * @param int $id The id of the record to save
    **/
    public function editSave($type, $id)
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $id = (int) $id;

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'edit', true)) return;
        if (! $this->checkRecordAccess($ctlr, $id)) return;

        $this->unlock($type, $id);
        $this->cleanupCommonPostData($ctlr);

        $_SESSION['admin']['tags'] = $_POST['tags'];

        $result = $ctlr->_editSave($id);

        if (Request::isAjax()) {
            $result = (int) $result;
            echo json_encode(array('result' => $result));
            return;
        }

        if ($result == false) {
            Notification::error('There was an error saving your changes');
            Url::redirect("admin/edit/{$type}/{$id}");
        }

        // Update per-record permissions
        if ($result and AdminPerms::canAccess('access_operators')) {
            PerRecordPerms::save($ctlr, $id);
        }

        $new_tags = Tags::splitupTags($_POST['tags']);
        $tag_result = Tags::update($ctlr->getTableName(), $id, $new_tags);
        unset ($_SESSION['admin']['tags']);

        if ($tag_result == false) {
            Notification::error('There was an error updating the tags for this item');
        }

        $ctlr->_invalidateCaches('edit', $id);

        unset ($_SESSION['admin']['field_values']);
        if (!Notification::has(Notification::TYPE_CONFIRM)) {
            Notification::confirm('Your changes have been saved');
        }

        if (is_string($result)) {
            Url::redirect($result);
        } else {
            Url::redirect("admin/edit/{$type}/{$id}");
        }
    }


    /**
     * Shows a delete form for the specified item
     * @param string $type Shorthand controller name; see {@see Register::adminControllers}
     * @param int $id The id of the record to show
     */
    public function delete($type, $id)
    {
        AdminAuth::checkLogin();

        $ctlr = Admin::getController($type);
        if (!$ctlr) return;
        if (!$this->checkAccess($ctlr, 'delete', false)) return;
        if (!$this->checkRecordAccess($ctlr, $id)) return;
        if (!$ctlr->_isDeleteSaved($id)) return;

        $main = $ctlr->_getDeleteForm($id);
        if ($main instanceof AdminError) {
            $this->error($main->getMessage(), $ctlr);
            return;
        }

        if (!is_array($main)) {
            throw new InvalidArgumentException('Return value from _getDeleteForm must be an array');
        }

        if (!isset($main['title']) or !isset($main['content'])) {
            throw new InvalidArgumentException('Return value from _getDeleteForm must contain title + content');
        }

        if ($ctlr->_isDeleteSaved($id) and Text::containsFormTag($main['content'])) {
            throw new Exception("Delete view must not include the form tag");
        }

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);
        $this->setNavigation($view, $ctlr);

        $single = Inflector::singular($ctlr->getFriendlyName());
        $content = '<form action="admin/delete_save/' . Enc::html($ctlr->getControllerName()) . '/' . $id . '" method="post" id="edit-form">';

        $content .= Csrf::token();

        $content .= '<div class="mainbar-with-right-sidebar">';
        $content .= $main['content'];
        $content .= '</div>';

        $content .= '<div class="right-sidebar">';
        $content .= '<div class="right-sidebar-inner">';
        $content .= '<div class="save-changes-box">';

        $content .= '<h2 class="icon-before icon-delete">Delete ' . Enc::html($single) . '</h2>';
        $content .= $this->renderSubActions($ctlr->_getDeleteSubActions($id));
        $content .= '<div class="save-changes-box-bottom -clearfix">';
        $content .= '<button type="submit" class="save-changes-save-button button button-regular button-red button-ref icon-after icon-delete">Delete ' . Enc::html($single) . '</button>';
        $content .= '</div>';

        $content .= '</div>';
        $content .= '</div>';
        $content .= '</div>';

        $content .= '</form>';

        $view->browser_title = Text::limitChars(strip_tags($main['title']), 50, '...');
        $view->main_title = $main['title'];
        $view->main_content = $content;
        $view->main_class = 'delete';

        echo $view->render();
    }

    /**
    * Executes the delete action for a specific item
    *
    * @param string $type The Type of the item to delete
    * @param int $id The id of the record to delete
    **/
    public function deleteSave($type, $id)
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'delete', true)) return;
        if (! $this->checkRecordAccess($ctlr, $id)) return;

        try {
            $ctlr->_deletePreSave($id);
        } catch (Exception $ex) {
            Notification::error($ex->getMessage());
            Url::redirect("admin/delete/{$type}/{$id}");
        }

        $result = false;
        try {
            $result = $ctlr->_deleteSave($id);
        } catch (ConstraintQueryException $ex) {
            $item_name = Inflector::singular($ctlr->getFriendlyName());
            Notification::error("This {$item_name} is in use and can't be deleted");
            Url::redirect("admin/edit/{$type}/{$id}");
        }
        if ($result) $ctlr->_deletePostSave($id);

        $tag_result = Tags::update($ctlr->getTableName(), $id, array());
        if (! $tag_result) $result = false;

        if (Request::isAjax()) {
            $result = (int) $result;
            echo json_encode(array('result' => $result));
            exit;
        }

        if ($result == false) {
            Notification::error('There was a database error deleting the specified item');
            Url::redirect("admin/delete/{$type}/{$id}");
        }

        $ctlr->_invalidateCaches('delete', $id);

        Notification::confirm('Deletion was successful');

        if (is_string($result)) {
            Url::redirect($result);
        } else {
            Url::redirect("admin/contents/{$type}");
        }
    }


    /**
    * Shows a duplication form for the specified item
    * This uses the edit form with some string replacements
    *
    * @param string $type The type of item to show the duplication form of
    * @param int $id The id of the record to duplicate
    **/
    public function duplicate($type, $id)
    {
        AdminAuth::checkLogin();
        $id = (int) $id;

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);
        $view->has_tags = true;

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'edit', false)) return;
        if (! $this->checkRecordAccess($ctlr, $id)) return;

        if (! $ctlr->getDuplicateEnabled()) {
            $this->error("Duplication is not enabled for this controller");
            return;
        }

        $this->setNavigation($view, $ctlr);

        $main = $ctlr->_getDuplicateForm($id);
        if ($main instanceof AdminError) {
            $this->error($main->getMessage(), $ctlr);
            return;
        }

        if (! is_array($main)) {
            $main = array('title' => $ctlr->getFriendlyName(), 'content' => $main);
        }

        if (!isset($main['title']) or !isset($main['content'])) {
            throw new InvalidArgumentException('Return value from _getDuplicateForm must contain title + content');
        }

        if (Text::containsFormTag($main['content'])) {
            throw new Exception("Duplicate view must not include the form tag");
        }

        // Create tags area, and inject it into content after the <form> tag
        $tags = new PhpView('sprout/admin/main_tags');
        $tags->suggestions = Tags::suggestTags($ctlr->getTableName());
        $tags->table = $ctlr->getTableName();

        // Inject tags UI
        $tags->current_tags = @$_SESSION['admin']['tags'];
        if (empty($_SESSION['admin']['tags'])) {
            $tags->current_tags = implode(', ', Tags::byRecord($ctlr->getTableName(), $id));
        }
        unset ($_SESSION['admin']['tags']);

        // Rejig the edit form to be about duplication instead of editing
        $name_find = array ('edit_save', 'editing', 'Editing', 'Save changes');
        $name_replace = array ('duplicate_save', 'duplicating', 'Duplicating', 'Duplicate');
        $main['content'] = str_replace($name_find, $name_replace, $main['content']);
        $main['title'] = str_replace($name_find, $name_replace, $main['title']);

        if ($ctlr->_isEditSaved($id)) {
            $single = Inflector::singular($ctlr->getFriendlyName());
            $content = '<form action="admin/duplicate_save/' . Enc::html($ctlr->getControllerName()) . '/' . $id . '" method="post" id="edit-form">';

            $content .= Csrf::token();
            $content .= '<div class="mainbar-with-right-sidebar">';
            $content .= $tags->render();
            $content .= $main['content'];
            $content .= '</div>';

            $content .= '<div class="right-sidebar">';
            $content .= '<div class="right-sidebar-inner">';
            $content .= '<div class="save-changes-box">';

            $html = $ctlr->_getCustomDuplicateSaveHTML($id);
            if ($html) {
                $content .= $html;
            } else {
                $visibility = $ctlr->_getVisibilityFields();
                $sub_actions = $ctlr->_getDuplicateSubActions($id);

                $content .= '<h2 class="icon-before icon-save">Duplicate ' . Enc::html($single) . '</h2>';
                if (!empty($visibility)) {
                    Form::nextFieldDetails('Visibility', false);
                    $content .= Form::checkboxBoolList(null, [], $visibility);
                }
                $content .= '<div class="save-changes-box-bottom -clearfix">';
                if (!empty($sub_actions['_preview'])) {
                    $content .= '<a href="' . Enc::html($sub_actions['_preview']) . '" class="save-changes-preview-button button button-regular button-blue icon-after icon-remove_red_eye">Preview</a>';
                }
                $content .= '<button type="submit" class="save-changes-save-button button button-regular button-green icon-after icon-save">Save changes</button>';
                $content .= '</div>';
            }

            $content .= '</div>';
            $content .= '</div>';
            $content .= '</div>';

            $content .= '</form>';
        } else {
            $content = $main['content'];
        }


        $this->lock($type, $id, $view);

        // Render the main view
        $view->browser_title = Text::limitChars(strip_tags($main['title']), 50, '...');
        $view->main_title = $main['title'];
        $view->main_content = $content;
        $view->main_class = 'do-action-box';

        echo $view->render();
    }

    /**
    * Executes the save action for a record duplication
    *
    * @param string $type The type of item to save
    * @param int $id The id of the record to save
    **/
    public function duplicateSave($type, $orig_id)
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $orig_id = (int) $orig_id;

        $ctlr = Admin::getController($type);
        if (! $ctlr) return;
        if (! $this->checkAccess($ctlr, 'edit', true)) return;
        if (! $this->checkRecordAccess($ctlr, $orig_id)) return;

        $this->cleanupCommonPostData($ctlr);

        $_SESSION['admin']['tags'] = $_POST['tags'];

        // Start transaction
        Pdb::transact();

        // Create new id
        // Nasty hack to prevent errors with null values in fields with foreign key constraints
        // This shouldn't be an issue as the actual data from the POST submission should comply with the constraints
        Pdb::q('SET foreign_key_checks = 0', [], 'count');
        $id = Pdb::insert($ctlr->getTableName(), array('date_added' => Pdb::now()));

        // Set "id" columns from multiedit records to zero for force an insert
        foreach ($_POST as $key => &$val) {
            if (is_array($val) and strpos($key, 'multiedit_') === 0) {
                foreach ($val as &$multiedit_row) {
                    $multiedit_row['id'] = 0;
                }
                unset($multiedit_row);
            }
        }
        unset($val);

        $result = $ctlr->_duplicateSave($id);

        // Re-enable foreign key constraints now that the real data has been saved
        Pdb::q('SET foreign_key_checks = 1', [], 'count');

        // Commit
        if ($result == true) {
            // Copy across per-record permissions, or create new ones if none exist for the original record
            try {
                $perms = PerRecordPerms::fetchDetails($ctlr, $orig_id);

                $_POST['_prm_categories'] = $perms['categories'];

                PerRecordPerms::save($ctlr, $id);
            } catch (RowMissingException $ex) {
                $_POST['_prm_categories'] = '*';

                PerRecordPerms::save($ctlr, $id);
            }

            Pdb::commit();
        }

        if (Request::isAjax()) {
            $result = (int) $result;
            echo json_encode(array('result' => $result));
            return;
        }

        if ($result == false) {
            Notification::error('There was an error saving your changes');
            Url::redirect("admin/duplicate/{$type}/{$orig_id}");
        }

        $new_tags = Tags::splitupTags($_POST['tags']);
        $tag_result = Tags::update($ctlr->getTableName(), $id, $new_tags);
        unset ($_SESSION['admin']['tags']);

        if ($tag_result == false) {
            Notification::error('There was an error updating the tags for this item');
        }

        $ctlr->_invalidateCaches('duplicate', $id);

        unset ($_SESSION['admin']['field_values']);
        Notification::confirm('Your changes have been saved');
        Url::redirect("admin/edit/{$type}/{$id}");
    }


    /**
    * Moderation
    **/
    public function moderate()
    {
        AdminAuth::checkLogin();

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);
        $this->setNavigation($view, new PageAdminController());


        $moderators = Register::getModerators();

        if (count($moderators) == 0) {
            $this->error("No moderation classes are registered.");
            return;
        }

        $out = '<form action="SITE/admin/moderate_action" method="post">';
        $out .= Csrf::token();

        foreach ($moderators as $class) {
            /** @var ModerateInterface $inst */
            $inst = Sprout::instance($class, ModerateInterface::class);

            $html = $inst->render();
            $html = Html::namespace("moderate[{$class}]", $html, true);
            $out .= $html;
        }

        $out .= '<div class="action-bar">';
        $out .= '<button type="submit" class="button button-regular button-green icon-after icon-save">Save changes</button>';
        $out .= '</div>';
        $out .= '</form>';

        $view->browser_title = 'Content Moderation';
        $view->main_title = 'Content Moderation';
        $view->main_content = $out;

        echo $view->render();
    }


    /**
    * Processes the moderation form
    **/
    public function moderateAction()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        if (@!is_array($_POST['moderate'])) $_POST['moderate'] = array();

        Pdb::transact();

        /** @var ModerateInterface[] $moderations */
        $moderations = [];

        $approve = 0;
        $delete = 0;

        foreach ($_POST['moderate'] as $class => $records) {
            if (! is_array($records)) continue;

            /** @var ModerateInterface $inst */
            $inst = Sprout::instance($class, ModerateInterface::class);
            $moderations[] = $inst;

            foreach ($records as $id => $data) {

                $id = (int) $id;
                $do = $data['action'] ?? null;

                if ($do == 'app') {
                    $inst->approve($id);
                    $approve++;

                } else if ($do == 'del') {
                    $inst->delete($id);
                    $delete++;
                }

                $inst->setData($id, $data);
            }
        }

        Pdb::commit();

        // Process these after the commit so we're not lying.
        foreach ($moderations as $inst) {
            $inst->complete();
        }

        // TODO this should use translations.

        if ($approve) {
            $message = "Approved {$approve} ". Inflector::plural('record', $approve);
            Notification::confirm($message);
        }

        if ($delete) {
            $message = "Deleted {$delete} ". Inflector::plural('record', $delete);
            Notification::confirm($message);
        }

        Url::redirect('admin/moderate');
    }


    /**
    * Calls any 'extra' commands that might be provided by the controller
    *
    * Method names will be prefixed with '_extra' and must be public
    * Names which are lower_cased will be converted to camelCase
    *
    * Supports varargs - additional args are passed to the underlying function
    * Called method should return an array, with two keys:
    *     title     string    Main title
    *     content   string    HTML for the main content area
    *
    * @example
    *     class BookingAdminController extends ManagedAdminController {
    *         // Call with url admin/extra/booking/send_email/:record_id
    *         public function _extraSendEmail($record_id) {
    *             return ['title' => '...', 'content' => '...'];
    *         }
    *     }
    *
    * @param string $type The class name of the method to call (must extend ManagedAdminController)
    * @param string $method The method name to call
    * @return void Outputs HTML
    **/
    public function extra($class, $method)
    {
        AdminAuth::checkLogin();

        $method = preg_replace('/[^a-zA-Z0-9_]/', '', $method);
        if (empty($method)) {
            throw new InvalidArgumentException('Invalid method specified');
        }

        $method = '_extra' . ucfirst(Text::lc2camelCase($method));

        $ctlr = Admin::getController($class);

        try {
            $reflect = new ReflectionMethod($ctlr, $method);
        } catch (ReflectionException $ex) {
            throw new InvalidArgumentException('Method "' . $method . '" does not exist');
        }
        if (!$reflect->isPublic()) {
            throw new InvalidArgumentException('Method "' . $method . '" does not exist');
        }

        $view = new PhpView('sprout/admin/main_layout');
        $this->setDefaultMainviewParams($view);
        $this->setNavigation($view, $ctlr);

        $args = func_get_args();
        $args = array_slice($args, 2);
        $main = call_user_func_array([$ctlr, $method], $args);

        if ($main instanceof AdminError) {
            $this->error($main->getMessage(), $ctlr);
            return;
        }

        if (!is_array($main)) {
            $main = [
                'title' => $ctlr->getFriendlyName(),
                'content' => $main
            ];
        }

        if (!isset($main['title']) or !isset($main['content'])) {
            throw new InvalidArgumentException("Return value from extra '{$method}' must contain title + content");
        }

        $view->browser_title = strip_tags($main['title']);
        $view->main_title = $main['title'];
        $view->main_content = $main['content'];
        echo $view->render();
    }


    /**
     * Directly calls a method provided by an admin controller
     * Suports varargs - additional args are passed to the underlying function
     *
     * @param string $class The shorthand class name, e.g. 'page'
     * @param string $method The method name, e.g. 'reorder_top'
     * @return void Does whatever the called function does, e.g. echo or redirect
     */
    public function call($class, $method)
    {
        AdminAuth::checkLogin();

        $ctlr = Admin::getController($class);
        if (!$ctlr or !($ctlr instanceof ManagedAdminController)) {
            throw new InvalidArgumentException('Controller "' . $class . '" does not exist');
        }

        if (!method_exists($ctlr, $method)) {
            throw new InvalidArgumentException('Method "' . $method . '" does not exist');
        }

        $reflect = new ReflectionMethod($ctlr, $method);
        if (!$reflect->isPublic()) {
            throw new InvalidArgumentException('Method "' . $method . '" does not exist');
        }

        $args = func_get_args();
        $args = array_slice($args, 2);

        call_user_func_array([$ctlr, $method], $args);
    }


    /**
    * Sets the active subsite, and then redirects back to the admin area.
    * Uses the post variable "subsite".
    **/
    public function setActiveSubsite()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $_POST['subsite'] = (int) @$_POST['subsite'];
        if ($_POST['subsite'] <= 0) {
            die('Invalid POST data');
        }

        // Does the operator actually have access to edit this subsite?
        if (!AdminPerms::canAccessSubsite($_POST['subsite'])) {
            Notification::error('Access denied');
            Url::redirect(Kohana::config('sprout.admin_intro'));
        }

        $_SESSION['admin']['active_subsite'] = $_POST['subsite'];

        Notification::confirm('Subsite changed');
        Url::redirect(Kohana::config('sprout.admin_intro'));
    }


    /**
    * Sets up the sidebar navigation for a view to show the navigation for a specific controller.
    *
    * @param BaseView $view The view to set the navigation parameters for.
    * @param Controller $ctlr The controller to use for navigation (and searching if supported).
    **/
    private function setNavigation(BaseView $view, Controller $ctlr)
    {
        // If no navigation has been set, use the default
        if (empty($view->nav)) {
            $view->nav = $ctlr->_getNavigation();
        }

        $view->controller_name = $ctlr->getControllerName();
        $view->controller_navigation_name = $ctlr->getNavigationName();
        $view->nav_tools = $ctlr->_getTools();
    }


    /**
    * Sets the a bunch of parameters for a the main view.
    *
    * @param BaseView $view The view to set the parameters for.
    **/
    private function setDefaultMainviewParams($view)
    {
        $view->admin_authenticated = true;

        // Browser version checks. FF3+, IE7+
        $browser_ok = false;
        if (Kohana::userAgent('browser') == 'Firefox') {
            $browser_ok = true;
        } else if (Kohana::userAgent('browser') == 'Chrome') {
            $browser_ok = true;
        } else if (Kohana::userAgent('browser') == 'Internet Explorer' and version_compare(Kohana::userAgent('version'), '7.0', '>=')) {
            $browser_ok = true;
        }

        // Set a message if the browser is not supported.
        if (! $browser_ok) {
            $view->info_message = new PhpView('sprout/admin/message_bad_browser');
        }

        // Header under the sprout logo
        $view->header_subtitle = '';

        // The subsite is not present for a partially complete 2FA login.
        if (!empty($_SESSION['admin']['active_subsite'])) {
            $view->live_url = Subsites::getAbsRoot($_SESSION['admin']['active_subsite']);
        }
    }


    /**
     * Does lock checking, locking, or lock messages.
     *
     * @param string $type Admin controller slug, e.g. 'page'
     * @param int $id Record id which is being edited
     * @param BaseView $view Main layout view to provide lock details into
     */
    private function lock($type, $id, BaseView $view)
    {
        if (! Admin::locksEnabled()) return;

        $type = (string) $type;
        $id = (int) $id;

        $lock = Admin::getLock($type, $id);

        if ($lock == null) {
            // No lock; acquire it
            $lock_id = Admin::lock($type, $id);
            $view->currlock = [
                'id' => (int)$lock_id,
                'ctlr' => $type,
                'record_id' => $id,
                'edit_token' => Csrf::getTokenValue(),
            ];

        } else if ($lock['lock_key'] == $_SESSION['admin']['lock_key']) {
            // Is locked to this session
            Admin::pingLock($lock['id']);
            $view->currlock = [
                'id' => (int)$lock['id'],
                'ctlr' => $type,
                'record_id' => $id,
                'edit_token' => Csrf::getTokenValue(),
            ];

        } else {
            // Locked to a different session
            $view->locked = $lock;
        }
    }


    /**
    * Unlocks lock for a given record/controller, all records for a controller, or all locks
    **/
    private function unlock($type = null, $id = null)
    {
        Admin::unlock($type, $id);
    }


    /**
    * Unlock a record.
    * Called via ajax in the beforeunload javascript event
    **/
    public function ajaxUnlock()
    {
        AdminAuth::checkLogin();

        if (!Csrf::check()) {
            die('Session timeout or missing security token');
        }

        Admin::unlock($_POST['ctlr'], $_POST['record_id']);
        echo '.';
    }


    /**
     * Restore a deleted record from log data
     * @param int $log_id ID in the history_items table
     * @return void
     */
    public function restore($log_id)
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();
        $log_id = (int) $log_id;

        // Gather data
        $q = "SELECT id, record_id, record_table, controller, data
            FROM ~history_items
            WHERE id = ? AND type = 'Delete'";
        $records = Pdb::q($q, [$log_id], 'map-arr');
        if (count($records) == 0) {
            throw new Kohana_404_Exception();
        }

        $main_record = Sprout::iterableFirstValue($records);
        $new = $records;
        while (count($new) > 0) {
            $params = [];
            $q = "SELECT id, record_table, data FROM ~history_items WHERE type = 'Delete' AND ";
            $q .= Pdb::buildClause([['parent_id', 'IN', array_keys($new)]], $params);
            $new = Pdb::q($q, $params, 'map-arr');
            foreach ($new as $id => $data) {
                $records[$id] = $data;
            }
        }

        // Restore data
        Pdb::transact();
        foreach ($records as $row) {
            Pdb::validateIdentifier($row['record_table']);
            $data = json_decode($row['data'], true);
            $cols = $values = '';
            foreach ($data as $field => $value) {
                Pdb::validateIdentifier($field);
                if ($cols) {
                    $cols .= ', ';
                    $values .= ', ';
                }
                $cols .= $field;
                $values .= ':' . $field;
            }
            $q = "INSERT INTO ~{$row['record_table']} ({$cols}) VALUES ({$values})";
            try {
                Pdb::q($q, $data, 'null');
            } catch (QueryException $ex) {
                Pdb::rollback();
                Notification::error('Database error during restore');
                Url::redirect('admin/edit/action_log/' . $log_id);
            }
        }
        $op = AdminAuth::getDetails();
        $data = ['restored_date' => Pdb::now(), 'restored_operator' => @$op['name']];
        Pdb::update('history_items', $data, ['id' => $log_id]);
        Pdb::commit();

        Notification::confirm('Data has been restored');
        $ctlr_class = $main_record['controller'];
        if (class_exists($ctlr_class)) {
            $ctlr = new $ctlr_class();
            Url::redirect('admin/edit/' . Enc::url($ctlr->getControllerName()) . '/' . $main_record['record_id']);
        } else {
            Url::redirect('admin/edit/action_log/' . $log_id);
        }
    }


    /**
     * Browser information
     * @return void Echos HTML
     */
    public function userAgent()
    {
        $data = UserAgent::getInfo();
        $data['full_ua'] = $_SERVER['HTTP_USER_AGENT'];
        $data['body_classes'] = UserAgent::getBodyClasses();

        echo '<style>';
        echo 'table { border-collapse: collapse; margin: 50px auto; }';
        echo 'h1,p { font-family: sans-serif; text-align: center; margin: 50px auto; }';
        echo 'td { font-family: sans-serif; padding: 10px 15px; border: 1px #eee solid; }';
        echo '</style>';

        echo '<h1>User-agent</h1>';
        echo "<table>\n";
        foreach ($data as $field => $val) {
            echo "<tr>\n";
            echo "<td><b>", Enc::html($field), "</b></td>\n";
            echo "<td>", Enc::html($val), "</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
        echo '<p>Uses data from the <a href="https://github.com/Karmabunny/user-agents.json">user-agents.json</a> project.</p>';
    }


    /**
    * Activates AutoLaunch revisions
    **/
    public function cronGenericActivate()
    {
        Cron::start('Generic autolaunch system');

        $tbl_prefix = Pdb::prefix();

        // Find autolaunch/autonuke tables
        $q = "SHOW TABLE STATUS";
        $db_tables = Pdb::query($q, [], 'pdo');

        $tables = array();
        foreach ($db_tables as $tbl) {
            if (strpos($tbl['Name'], $tbl_prefix) !== 0) {
                continue;
            }

            if ($tbl['Name'] === "{$tbl_prefix}page_revisions") {
                continue;
            }

            $q = "SHOW COLUMNS FROM {$tbl['Name']}";
            $db_cols = Pdb::query($q, [], 'pdo');

            $tables[$tbl['Name']] = 0;
            foreach ($db_cols as $col) {
                if ($col['Field'] == 'date_launch') $tables[$tbl['Name']]++;
                if ($col['Field'] == 'date_expire') $tables[$tbl['Name']]++;
                if ($col['Field'] == 'active') $tables[$tbl['Name']]++;
            }
        }

        Pdb::transact();

        foreach ($tables as $tbl => $num_cols) {
            if ($num_cols !== 3) continue;

            $tbl_no_prefix = substr($tbl, strlen($tbl_prefix));

            Cron::message("Processing table {$tbl}");

            try {
                // Launch
                $q = "SELECT id
                    FROM {$tbl}
                    WHERE active = 0
                        AND date_launch != '0000-00-00'
                        AND date_launch IS NOT NULL
                        AND date_launch <= NOW()
                        AND (
                            date_expire > NOW()
                            OR date_expire = '0000-00-00'
                            OR date_expire IS NULL
                        )";
                $res = Pdb::q($q, [], 'arr');

                foreach ($res as $row) {
                    Cron::message("Activating record {$row['id']}");
                    Pdb::update($tbl_no_prefix, ['active' => 1], ['id' => $row['id']]);
                }


                // Unlaunch
                $q = "SELECT id
                    FROM {$tbl}
                    WHERE active = 1
                        AND date_expire != '0000-00-00'
                        AND date_expire IS NOT NULL
                        AND date_expire < NOW()";
                $res = Pdb::q($q, [], 'arr');

                foreach ($res as $row) {
                    Cron::message("Expiring record {$row['id']}");
                    Pdb::update($tbl_no_prefix, ['active' => 0], ['id' => $row['id']]);
                }
            } catch (QueryException $ex) {
                return Cron::failure('Database error');
            }
        }

        Pdb::commit();

        Cron::success();
    }


    public function heartbeat()
    {
        AdminAuth::checkLogin();

        echo "Ah ah ah ah, stayin' alive, stayin' alive...";


        // We piggyback the heartbeat to keep locks up-to-date
        if (isset($_GET['lock_id'])) {
            Admin::pingLock($_GET['lock_id']);
            Admin::clearOldLocks();
        }
    }
}
