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

use DateInterval;
use DateTime;

use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Helpers\ColModifierActionLogData;
use Sprout\Helpers\ColModifierBinary;
use Sprout\Helpers\ColModifierDate;
use Sprout\Helpers\ColModifierHexIP;
use Sprout\Helpers\Cron;
use Sprout\Helpers\Enc;
use Sprout\Helpers\File;
use Sprout\Helpers\Itemlist;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\RefineBar;
use Sprout\Helpers\RefineWidgetSelect;
use Sprout\Helpers\RefineWidgetTextbox;


/**
 * Handles admin processing for the action log, which is a record of changes to database content
 */
class ActionLogAdminController extends ManagedAdminController
{
    protected $controller_name = 'action_log';
    protected $friendly_name = 'Activity log';
    protected $table_name = 'history_items';
    protected $action_log = false;
    protected $main_add = false;
    protected $main_delete = false;
    protected $main_where = ['item.parent_id = 0'];


    /**
    * Constructor
    **/
    public function __construct()
    {
        parent::__construct();

        $this->main_order = 'item.id DESC';

        $this->main_columns = array(
            'Type' => 'type',
            'Date' => array(new ColModifierDate('d/m/Y H:i:s'), 'date_added'),
            'Editor' => 'modified_editor',
            'Table' => 'record_table',
            'Record' => [new ColModifierActionLogData(), 'id'],
        );

        $this->refine_bar = new RefineBar();
        $types = Pdb::extractEnumArr($this->table_name, 'type');
        $this->refine_bar->addWidget(new RefineWidgetSelect('type', 'Type', $types));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('record_table', 'Table'));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('record_id', 'Record ID'));
        $this->refine_bar->addWidget(new RefineWidgetTextbox('modified_editor', 'Editor'));
    }


    public function _addSave(&$item_id) { return false; }
    public function _isEditSaved($item_id) { return false; }
    public function _editSave($item_id) { return false; }
    public function _getAddForm() { return false; }


    /**
     * List of tools
     */
    public function _getTools()
    {
        $tools = parent::_getTools();
        unset($tools['import']);

        $tools[] = '<li><a href="SITE/admin/extra/action_log/login_attempts">View login attempts</a></li>';

        return $tools;
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


    /**
    * Mods
    **/
    protected function _editPreRender($view, $item_id)
    {
        // Previous
        $q = "SELECT id
            FROM ~history_items
            WHERE record_table = ? AND id < ?
            ORDER BY id DESC
            LIMIT 1";
        try {
            $row = Pdb::q($q, [$view->data['record_table'], $item_id], 'row');
            $view->prev_id = $row['id'];
        } catch (RowMissingException $ex) {
            // No problem
        }

        // Next
        $q = "SELECT id
            FROM ~history_items
            WHERE record_table = ? AND id > ?
            ORDER BY id ASC
            LIMIT 1";
        try {
            $row = Pdb::q($q, [$view->data['record_table'], $item_id], 'row');
            $view->next_id = $row['id'];
        } catch (RowMissingException $ex) {
            // No problem
        }

        $ctlr_class = $view->data['controller'];
        if (class_exists($ctlr_class)) {
            $ctlr = new $ctlr_class();
            $view->controller = $ctlr->getControllerName();
        }
    }


    /**
    * Shows a list of tables
    **/
    public function _getNavigation()
    {


        $q = "SELECT record_table, COUNT(id) AS num FROM ~history_items GROUP BY record_table ORDER BY record_table";
        $res = Pdb::q($q, [], 'pdo');

        $ret = '<ul class="list-style-1">';
        foreach ($res as $row) {
            $nice = ucfirst(str_replace('_', ' ', $row['record_table']));
            $ret .= "<li class=\"action-log\"><a href=\"admin/contents/action_log?record_table=";
            $ret .= Enc::html($row['record_table']) . "\">" . Enc::html($nice) . " ({$row['num']})</a></li>";
        }
        $ret .= '</ul>';

        $res->closeCursor();

        return $ret;
    }


    /**
     * List if recent logins
     */
    public function _extraLoginAttempts()
    {
        $q = "SELECT * FROM ~login_attempts ORDER BY id DESC LIMIT 25";
        $res = Pdb::query($q, [], 'arr');

        $itemlist = new Itemlist();
        $itemlist->main_columns = array(
            'Username' => 'username',
            'Success' => array(new ColModifierBinary(), 'success'),
            'Date' => array(new ColModifierDate(), 'date_added'),
            'IP Address' => array(new ColModifierHexIP(), 'ip'),
        );
        $itemlist->items = $res;

        return array(
            'title' => 'Login Attempts',
            'content' => $itemlist->render()
        );
    }


    public function cronCleanup()
    {
        Cron::start('Clean up action log');

        $date = new DateTime();
        $date->sub(new DateInterval('P3M'));
        $date = $date->format('Y-m-d H:i:s');

        $q = "DELETE FROM ~history_items
            WHERE date_modified <= ? AND
                (record_table != 'files' OR type != 'Delete' OR restored_date > '1')";
        $affected = Pdb::q($q, [$date], 'count');
        Cron::message("{$affected} ordinary record(s) deleted");

        $q = "DELETE FROM ~history_items WHERE id = ?";
        $del_st = Pdb::q($q, [], 'prep');

        $num_deleted = 0;
        $q = "SELECT id, type, restored_date, data
            FROM ~history_items
            WHERE date_modified <= ? AND record_table = 'files'";
        $res = Pdb::q($q, [$date], 'pdo');
        foreach ($res as $row) {
            $data = json_decode($row['data'], true);
            if (File::delete($data['filename'])) {
                Cron::message('Deleted file ' . $data['filename']);
            } else {
                Cron::message('Failed to delete file ' . $data['filename']);
            }
            Pdb::execute($del_st, [$row['id']], 'null');
            ++$num_deleted;
        }
        $res->closeCursor();
        Cron::message("{$num_deleted} stale file reference(s) deleted");

        Cron::success();
    }
}
