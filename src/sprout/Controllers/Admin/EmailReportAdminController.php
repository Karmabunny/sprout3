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

use Exception;
use InvalidArgumentException;
use Kohana;
use Sprout\Controllers\Admin\ListAdminController;
use Sprout\Helpers\Admin;
use Sprout\Helpers\ColModifierBinary;
use Sprout\Helpers\Cron;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Url;

/**
 * Handles admin processing for Email reports
 */
class EmailReportAdminController extends ListAdminController
{
    protected $controller_name = 'email_report';
    protected $friendly_name = 'Email reports';
    protected $add_defaults = [
        'active' => 1,
    ];

    /** Should this controller log add/edit/delete actions? */
    protected $action_log = true;
    protected $main_add = false;


    /**
    * Constructor
    **/
    public function __construct()
    {
        $this->main_columns = [
            'Name' => 'name',
            'Controller' => 'controller',
            'Active' => [new ColModifierBinary(), 'active'],
        ];

        $this->initRefineBar();

        parent::__construct();
    }


    /**
    * Pre-render hook for adding
    **/
    protected function _addPreRender($view)
    {
        parent::_addPreRender($view);
    }


    /**
     * Returns a page title and HTML for a form to add a record
     * @return array Two elements: 'title' and 'content'
     */
    public function _getAddForm()
    {
        Notification::error('Reports must be added using the tooling inside each section of the admin area');
        Url::redirect('admin/intro/email_report');
    }


    /**
     * Return the sub-actions for adding; for spec {@see AdminController::renderSubActions}
     * @return array
     */
    public function _getAddSubActions()
    {
        $actions = parent::_getAddSubActions();
        // Add your actions here, like this: $actions[] = [ ... ];
        return $actions;
    }


    /**
     * Saves the provided POST data into a new record in the database
     *
     * @param int $item_id After saving, the new record id will be returned in this parameter
     * @return bool|string True on success, false on failure, or a redirect URL
     */
    public function _addSave(&$item_id)
    {
        Pdb::transact();
        if (!parent::_addSave($item_id)) {
            Pdb::rollback();
            return false;
        }

        $this->fixRecordOrder($item_id);
        Pdb::commit();
        return true;
    }


    /**
    * Pre-render hook for editing
    **/
    protected function _editPreRender($view, $item_id)
    {
        parent::_editPreRender($view, $item_id);
    }


    /**
     * Return the sub-actions for editing; for spec {@see AdminController::renderSubActions}
     * @return array
     */
    public function _getEditSubActions($item_id)
    {
        $actions = parent::_getEditSubActions($item_id);
        $item = Pdb::get($this->table_name, $item_id);

        // Add your actions here, like this: $actions[] = [ ... ];
        $actions[] = [
            'url' => 'admin/email_report_send//' . $item_id,
            'name' => 'Send report now',
            'class' => 'icon-email icon-before icon-content',
        ];
        return $actions;
    }


    /**
     * Saves the provided POST data into the specified record
     *
     * @param int $item_id The record to update
     * @return bool|string True on success, false on failure, or a redirect URL
     */
    public function _editSave($item_id)
    {
        $item_id = (int) $item_id;
        if ($item_id <= 0) throw new InvalidArgumentException('$item_id must be greater than 0');

        return parent::_editSave($item_id);
    }


    /**
     * Send all active reports in the system
     */
    public function cronSendReports()
    {
        Cron::start('Sending automated email reports');

        $q = "SELECT * FROM ~email_reports WHERE active = 1";
        $reports = Pdb::query($q, [], 'arr');

        $count = count($reports);
        Cron::message("Found {$count} report(s) to send");

        if ($count == 0) {
            Cron::success();
        }

        $success = $fail = 0;
        foreach ($reports as $report) {
            try {
                /** @var ManagedAdminController */
                $ctlr = Admin::getController($report['controller_class']);
            } catch (Exception $e) {
                Kohana::logException($e);
                $ctlr = null;
            }

            if (! $ctlr) {
                $fail++;
                Cron::message("Report '{$report['name']}' FAILED - invalid controller '{$report['controller_class']}'");
            }

            $res = $ctlr->_sendEmailReport($report);
            if ($res) {
                $success++;
                Cron::message("Report '{$report['name']}' has been sent");
            } else {
                $fail++;
                Cron::message("Report '{$report['name']}' FAILED - email sending failed");
            }
        }

        if ($success == 0) {
            Cron::failure("All reports failed to send");
        }

        Cron::success();
    }

}

