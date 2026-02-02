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

use Kohana;

use Sprout\Helpers\Admin;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AttrEditor;
use Sprout\Helpers\DocImport\DocImport;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\FrontEndEntrance;
use Sprout\Helpers\Html;
use Sprout\Helpers\Json;
use Sprout\Helpers\LinkSpec;
use Sprout\Helpers\Navigation;
use Sprout\Helpers\Needs;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Register;
use Sprout\Helpers\Session;
use Sprout\Helpers\Slug;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Tags;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\Widgets;


/**
 * Handles processing of various admin AJAX methods including: widget settings; links; and categories.
 */
class AdminAjaxController extends Controller
{

    /**
     * @var Session
     */
    protected $session;


    /**
    * Constructor
    **/
    public function __construct()
    {
        parent::__construct();

        $this->session = Session::instance();

        header('Content-type: text/html; charset=UTF-8');
    }


    /**
    * Returns the HTML for the settings for an individual widget.
    *
    * @param string $widget_name The name of the widget to get settings for.
    **/
    public function widgetSettings($widget_name)
    {
        AdminAuth::checkLogin();

        // Grab data from either GET or POST
        if (!empty($_POST['prefix'])) {
            $settings = json_decode($_POST['settings'], true);
            $prefix = trim($_POST['prefix']);

        } else if (!empty($_GET['prefix'])) {
            $settings = json_decode($_GET['settings'], true);
            $prefix = trim($_GET['prefix']);

        } else {
            Json::error(
                'Error loading widget settings; '
                . ' contact ' . Kohana::config('branding.support_organisation') . ' for assistance'
            );
        }

        // Prep the widget
        try {
            $widget = Widgets::instantiate($widget_name);
            if (empty($settings)) {
                $settings = $widget->getDefaultSettings();
            }
            $widget->importSettings($settings);
        } catch (Exception $ex) {
            Kohana::logException($ex, false);
            Json::error($ex);
        }

        $info_labels = $widget->getInfoLabels();
        if (!$info_labels) {
            $info_labels = null;
        }

        // Output back to the browser
        try {
            Form::setData([$prefix => $widget->getSettings()]);
            Form::setFieldIdPrefix($prefix);
            Form::setFieldNameFormat($prefix . '[%s]');

            // TODO one day
            Form::setErrors([]);

            // Need to call settings form first (to load Needs) but then render needs first
            $form = $widget->getSettingsForm();
            $form = Needs::dynamicNeedsLoader() . $form;

            Json::confirm(array(
                'settings' => $form,
                'edit_url' => $widget->getEditUrl(),
                'description' => Enc::html($widget->getFriendlyDesc()),
                'info_labels' => $info_labels,
            ));
        } catch (Exception $ex) {
            Kohana::logException($ex, false);
            Json::error($ex);
        }
    }


    /**
     * AJAX-loaded popup content for UI to manage widget display conditions
     *
     * @return void Outputs HTML directly
     */
    public function widgetDispConds()
    {
        AdminAuth::checkLogin();

        Form::setData($_POST);

        $cond_list_params = [
            'fields' => Register::getDisplayConditions(),
            'url' => 'admin_ajax/widget_disp_cond_params',
        ];

        $view = new PhpView('sprout/admin/widget_disp_conds');
        $view->cond_list_params = $cond_list_params;
        echo $view->render();
    }


    /**
     * Callback url for {@see Fb::conditionsList} for the widget display conditions
     *
     * Input is GET params 'field', 'op', 'val'
     *
     * Output is JSON with two keys, 'op' and 'val'. They are both
     * HTML strings containing {@see Form} fields for the operator
     * dropdown and the values dropdown/textbox
     *
     * @return void Outputs JSON and then terminates
     */
    public function widgetDispCondParams()
    {
        AdminAuth::checkLogin();

        Form::setData($_GET);

        try {
            $inst = Sprout::instance($_GET['field'], ['Sprout\\Helpers\\DisplayConditions\\DisplayCondition']);
        } catch (Exception $ex) {
            Json::error($ex);
        }

        $op = Form::dropdown('op', ['-dropdown-top' => ' '], $inst->getOperators());

        $type = $inst->getParamType();
        switch ($type) {
            case 'text':
                $val = Form::text('val');
                break;
            case 'dropdown':
                $val = Form::dropdown('val', ['-dropdown-top' => ' '], $inst->getParamValues());
                break;
            default:
                Json::error('Invalid param type "' . $type . '"');
        }

        Json::out([
            'op' => $op,
            'val' => $val,
        ]);
    }


    /**
    * Popup for adding an addon
    **/
    public function addAddon($area_id, $field_name)
    {
        AdminAuth::checkLogin();

        $area_id = (int) $area_id;
        $field_name = trim(preg_replace('/[^_0-9a-zA-Z]/', '', $field_name));

        $areas = Kohana::config('sprout.widget_areas');
        $area = $areas[$area_id];
        $avail_widgets = $area->getWidgets();

        // Prep list and sort
        $widgets = array();
        foreach ($avail_widgets as $name) {
            $inst = Widgets::instantiate($name);
            $widgets[$inst->getFriendlyName()] = array(
                $name,
                $inst->getFriendlyName(),
                $inst->getFriendlyDesc(),
                $inst->getNotAvailableReason(),
            );
        }
        ksort($widgets);

        // Render view
        $view = new PhpView('sprout/ajax/add_addon');
        $view->field_name = $field_name;
        $view->widgets = $widgets;
        echo $view->render();
    }


    public function footerCompat()
    {
        AdminAuth::checkLogin();

        echo new PhpView('sprout/admin/footer_compat');
    }


    public function getTagSuggestions($table = null)
    {
        AdminAuth::checkLogin();

        $ret = Tags::suggestTags($table, $_GET['prefix']);
        Json::out($ret);
    }

    public function getEntranceArguments($class)
    {
        AdminAuth::checkLogin();

        $inst = Sprout::instance($class);

        if (!($inst instanceof FrontEndEntrance)) {
            Json::error('Invalid class; missing interface');
        }

        $args = $inst->_getEntranceArguments();

        if (empty($args)) {
            $args = array('' => '- Nothing available -');
        }

        Json::out($args);
    }


    /**
    * Adds a category
    **/
    public function addCategory()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        $_POST['name'] = trim($_POST['name'] ?? '');
        $_POST['table'] = trim($_POST['table'] ?? '');

        if ($_POST['name'] == '') Json::error('Please enter a category name to add');
        if ($_POST['table'] == '') Json::error('Invalid arguments');

        $data = [
            'name' => $_POST['name'],
            'date_added' => Pdb::now(),
        ];

        // Incorporate slug where possible
        try {
            $data['slug'] = Slug::create($_POST['table'], $_POST['name']);
            $insert_id = Pdb::insert($_POST['table'], $data);
        } catch (Exception $ex) {
            unset($data['slug']);
            try {
                $insert_id = Pdb::insert($_POST['table'], $data);
            } catch (Exception $ex) {
                Kohana::logException($ex, false);
                Json::error('Database error');
            }
        }

        $out = array(
            'success' => 1,
            'id' => $insert_id,
            'name' => $_POST['name'],
        );

        Json::out($out);
    }


    /**
    * Load an attribute editor for a given field
    **/
    public function attrEditor()
    {
        AdminAuth::checkLogin();

        $_POST['val'] = trim($_POST['val'] ?? '');
        $_POST['attr_name'] = trim($_POST['attr_name'] ?? '');

        // Find the attr
        $attrs = Register::getPageattrs();

        if (empty($attrs[$_POST['attr_name']])) {
            throw new Exception('Invalid field');
        }

        $attr = $attrs[$_POST['attr_name']];

        // Check the class is valid
        $class_name = $attr[1];
        if (! class_exists($class_name)) {
            throw new Exception('Invalid class for field');
        }

        // Create instance
        $inst = new $class_name();
        if (!($inst instanceof AttrEditor)) {
            throw new Exception('Invalid class for field');
        }

        // And render
        $html = $inst->render($_POST['val'], $_POST['attr_name']);
        $html = Needs::replacePathsString($html);
        $js = $inst->javascript($_POST['val'], $_POST['attr_name']);

        Json::confirm(array(
            'html' => $html,
            'js' => $js,
        ));
    }


    /**
    * Load an attribute editor for a given field
    **/
    public function lnkEditor()
    {
        AdminAuth::checkLogin();

        $_POST['field'] = trim($_POST['field'] ?? '');
        $_POST['type'] = trim($_POST['type'] ?? '');
        $_POST['type'] = '\\' . ltrim($_POST['type'], '\\');

        if (empty($_POST['val'])) {
            $_POST['val'] = '';
        } else if (is_string($_POST['val'])) {
            $_POST['val'] = trim($_POST['val']);
        } else if (is_array($_POST['val'])) {
            $_POST['val'] = array_map('trim', $_POST['val']);
        }

        if ($_POST['type'] == '') {
            Json::confirm(array(
                'html' => '',
            ));
        }

        // Helps out attributes which use the page tree
        Navigation::loadPageTree($_SESSION['admin']['active_subsite'], true);

        // Ensures fields using the 'Form' class will have usable id attributes
        Form::setFieldIdPrefix('lnkspec-' . time() . rand(0, 999) . '-');

        // Find the attr
        $specs = Register::getLinkspecs();
        if (empty($specs[$_POST['type']])) {
            throw new Exception('Invalid LinkSpec class');
        }

        // Create instance
        $class_name = $_POST['type'];
        $inst = new $class_name();
        if (!($inst instanceof LinkSpec)) {
            throw new Exception('Invalid class for field');
        }

        // And render
        $html = $inst->getEditForm($_POST['field'], $_POST['val']);
        $html = Needs::replacePathsString($html);

        Json::confirm(array(
            'html' => $html,
            'class' => Sprout::removeNs(get_class($inst)),
        ));
    }


    /**
     * Set a given JavaScript tour as complete, preventing it from showing again.
     */
    public function setTourCompleted($tour_name)
    {
        AdminAuth::checkLogin();
        Admin::setTourCompleted($tour_name);
        echo '.';
    }


    /**
    * Called by button handlers on the two RTEs and loaded in a facebox window
    * Shows an interface to load stuff into the editor
    * The editor id will br provided in $elemid
    **/
    public function richtextImport($elemid)
    {
        AdminAuth::checkLogin();

        $view = new PhpView('sprout/ajax/document_import');
        $view->elemid = $elemid;
        echo $view->render();
    }


    /**
    * Handle an upload of a document. Echos DIV-warapped JSON with the result of the import.
    *
    * Keys include:
    *    'html'    The imported HTML of the document
    **/
    public function richtextImportIframe()
    {
        AdminAuth::checkLogin();

        try {
            $inst = DocImport::instance($_FILES['import']['name']);

            $result = $inst->load($_FILES['import']['tmp_name']);

            // PHP-8+ deprecated this because it's disabled by default.
            if (PHP_VERSION_ID < 80000) {
                libxml_disable_entity_loader();
            }

            $dom = new DOMDocument();
            $dom->loadXML($result);

            // TODO: import images

            $images = array();
            $headings = array(1 => 2, 2 => 3, 3 => 4, 4 => 5, 5 => 6);
            $html = DocImport::getHtml($dom, $images, $headings);

            $out = array(
                'html' => $html
            );

        } catch (Exception $ex) {
            $out = array('error' => $ex->getMessage());
        }

        echo '<div>', Enc::html(json_encode($out)), '</div>';
        exit;
    }


    /**
     * Demo AJAX JSON callback url for {@see Fb::conditionsList}
     *
     * Input is GET params 'field', 'op', 'val'
     *
     * Output is JSON with two keys, 'op' and 'val'. They are both
     * HTML strings containing {@see Form} fields for the operator
     * dropdown and the values dropdown/textbox
     *
     * @return void Outputs JSON and then terminates
     */
    public function styleGuideDemoConditions()
    {
        AdminAuth::checkLogin();

        Form::setData($_GET);

        $op = '';
        $val = '';

        switch ($_GET['field']) {
            case 'name':
                $op = Form::dropdown('op', ['-dropdown-top' => ' '], [
                    '=' => 'Equals',
                    '!=' => 'Not equals',
                    'begin' => 'Begins with',
                    'end' => 'Ends with',
                ]);
                $val = Form::text('val');
                break;

            case 'age':
                $op = Form::dropdown('op', ['-dropdown-top' => ' '], [
                    '=' => 'Equals',
                    '!=' => 'Not equals',
                    '>' => 'Greater than',
                    '>=' => 'Greater than or equal',
                    '<' => 'Less than',
                    '<=' => 'Less than or equal',
                ]);
                $val = Form::text('val');
                break;

            case 'gender':
                $op = Form::dropdown('op', ['-dropdown-top' => ' '], [
                    '=' => 'Is',
                    '!=' => 'Is not',
                ]);
                $val = Form::dropdown('val', ['-dropdown-top' => ' '], [
                    'f' => 'Female',
                    'm' => 'Male',
                    'o' => 'Other',
                ]);
                break;
        }

        Json::out([
            'op' => $op,
            'val' => $val,
        ]);
    }

}


