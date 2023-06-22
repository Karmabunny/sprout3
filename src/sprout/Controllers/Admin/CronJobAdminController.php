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
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Notification;
use Sprout\Helpers\RefineBar;
use Sprout\Helpers\RefineWidgetSelect;
use Sprout\Helpers\RefineWidgetTextbox;
use Sprout\Helpers\Register;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Url;
use Sprout\Helpers\PhpView;


/**
* Handles most processing for Cron Jobs
**/
class CronJobAdminController extends ListAdminController
    implements NoRecordPermissionsInterface
{
    protected $friendly_name = 'Cron Jobs';
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
            'Date' => 'date_added',
        ];

        $this->refine_bar = new RefineBar();
        $this->refine_bar->setGroup('Job');
        $this->refine_bar->addWidget(new RefineWidgetTextbox('name', 'Name'));
        $this->refine_bar->addWidget(new RefineWidgetSelect('status', 'Status', array('Incomplete', 'Complete')));

        parent::__construct();
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

    /** @inheritdoc */
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
     * UI for running cron jobs manually
     */
    public function _extraManualRun()
    {
        $jobs = Register::getAllCronJobs();

        $job_list = [];
        foreach ($jobs as $sched => $sj) {
            $job_list[$sched] = [];
            foreach ($sj as $job) {
                $key = $sched . '__' . $job[0] . '__' . $job[1];

                $ns_hunks = explode('\\', $job[0]);
                $class = array_pop($ns_hunks);
                $val = "{$class}::{$job[1]}";

                $job_list[$sched][$key] = $val;
            }
        }

        $view = new PhpView('sprout/admin/cron_job_manual_run');
        $view->jobs = $job_list;

        return [
            'title' => 'Cron job manual run',
            'content' => $view->render(),
        ];
    }


    /**
     * Manually runs a cron job
     */
    public function manualRunAction()
    {
        AdminAuth::checkLogin();
        Csrf::checkOrDie();

        if (empty($_POST['job'])) {
            Notification::error('You must select a cron job');
            Url::redirect('admin/extra/cron_job/manual_run');
        }

        list($sched, $class, $func) = explode('__', $_POST['job']);

        // Check the job is registered and reject any which are not
        $jobs = Register::getCronJobs($sched);
        $found = false;
        foreach ($jobs as $j) {
            if ($j[0] === $class and $j[1] === $func) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            Notification::error('Specified cron job is not registered');
            Url::redirect('admin/extra/cron_job/manual_run');
        }

        // Instance class - this may throw an exception if class not found or invalid
        $inst = Sprout::instance(
            $class,
            ['Sprout\\Controllers\\Controller']
        );

        // Set up cron environment
        header('Content-type: text/plain');
        Kohana::closeBuffers();
        set_time_limit(0);

        echo 'Class:  ', $class, PHP_EOL;
        echo 'Func:   ', $func, PHP_EOL;
        echo str_repeat('-', 80), PHP_EOL;

        call_user_func([$inst, $func]);
    }

}


