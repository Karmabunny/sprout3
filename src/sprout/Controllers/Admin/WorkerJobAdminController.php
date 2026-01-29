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

namespace Sprout\Controllers\Admin;

use Kohana;

use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Constants;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Json;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\RefineBar;
use Sprout\Helpers\RefineWidgetSelect;
use Sprout\Helpers\RefineWidgetTextbox;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Url;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\Worker;


/**
* Handles most processing for Worker Jobs
**/
class WorkerJobAdminController extends ListAdminController
{
    protected $friendly_name = 'Worker Jobs';
    protected $navigation_name = 'Dev tools';
    protected $add_defaults = array(
        'active' => 1,
    );
    protected $main_order = 'item.date_added DESC';
    protected $main_delete = false;


    /**
    * Constructor
    **/
    public function __construct()
    {
        $this->main_columns = [
            'Name' => 'name',
            'Status' => 'status',
            'Channel' => 'channel',
            'Timeout' => 'timeout',
            'Priority' => 'priority',
            'Date' => 'date_added',
        ];

        $this->refine_bar = new RefineBar();
        $this->refine_bar->setGroup('Job');
        $this->refine_bar->addWidget(new RefineWidgetTextbox('name', 'Name'));
        $this->refine_bar->addWidget(new RefineWidgetSelect('status', 'Status', Constants::$job_status));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('channel', 'Channel'));

        parent::__construct();
    }


    /** @inheritdoc */
    public static function _getContentPermissionGroups(): array
    {
        return [];
    }


    /**
    * Returns the contents of the navigation pane for the list
    **/
    public function _getTools()
    {
        return null;
    }

    public function _getNavigation()
    {
        $nav = new PhpView('sprout/dbtools/navigation');
        return $nav->render();
    }


    /**
     * Return the fields to show in the sidebar when adding or editing a record.
     * These fields are shown under a heading of "Visibility"
     *
     * Key is the field name, value is the field label
     *
     * @return array
     */
    public function _getVisibilityFields()
    {
        return [];
    }


    public function _getAddForm()
    {
        return [
            'title' => 'Not permitted',
            'content' => ''
        ];
    }
    public function _addSave(&$item_id) { return false; }
    public function _editSave($item_id) { return false; }


    /**
     * UI for running worker jobs manually
     */
    public function _extraManualRun()
    {
        $view = new PhpView('sprout/admin/worker_job_manual_run');

        return [
            'title' => 'Worker job manual run',
            'content' => $view->render(),
        ];
    }


    /**
    * Manually runs a worker job
    * Extra args can be provided as required.
    **/
    public function manualRunAction()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        if (empty($_POST['class_name'])) {
            Notification::error('You must provide a valid class name');
            Url::redirect('admin/extra/worker_job/manual_run');
        }

        if (!empty($_POST['args'])) {
            $args = @json_decode($_POST['args'], true);
            if (empty($args)) {
                Notification::error('Unable to parse arguments JSON');
                Url::redirect('admin/extra/worker_job/manual_run');
            }
        } else {
            $args = [];
        }

        // Instance class - this may throw an exception if class not found or invalid
        $inst = Sprout::instance(
            $_POST['class_name'],
            ['Sprout\\Helpers\\WorkerBase']
        );

        // Set up worker environment
        header('Content-type: text/plain');
        Kohana::closeBuffers();
        set_time_limit(0);

        // Output the class and the args
        echo str_pad('Class:', 10), $_POST['class_name'], PHP_EOL;
        foreach ($args as $index => $arg) {
            if (is_array($arg)) $arg = '[array]';
            if (is_object($arg)) $arg = '[object]';
            echo str_pad("Arg {$index}:", 10), $arg, PHP_EOL;
        }
        echo str_repeat('-', 80), PHP_EOL;

        Worker::$starttime = time();
        call_user_func_array(array($inst, 'run'), $args);
    }


    /**
    * Return the status and log for the worker job edit view ajax update
    **/
    public function jsonStatus($job_id)
    {
        AdminAuth::checkLogin();
        $job_id = (int) $job_id;

        $q = "SELECT status, metric1val, metric2val, metric3val, log FROM ~worker_jobs WHERE id = ?";
        $row = Pdb::q($q, [$job_id], 'row');

        Json::out($row);
    }

}


