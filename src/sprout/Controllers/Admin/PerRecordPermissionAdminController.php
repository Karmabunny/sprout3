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

use Sprout\Helpers\Admin;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\AdminError;
use Sprout\Helpers\AdminPerms;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Form;
use Sprout\Helpers\Inflector;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\PerRecordPerms;
use Sprout\Helpers\Register;
use Sprout\Helpers\Url;
use Sprout\Helpers\PhpView;


/**
 * Manages which controllers have per-record permissions enabled
 */
class PerRecordPermissionAdminController extends NoRecordsAdminController
{
    protected $controller_name = 'per_record_permission';
    protected $friendly_name = 'Per-record permissions';
    protected $table_name = 'per_record_controllers';


    /**
     * Gets the list of controllers which can have per-category permissions set
     *
     * @return array [shorthand name => human-readable label]
     */
    protected function getControllerList()
    {
        $controllers = Register::getAdminControllers();

        unset($controllers['page']);   // already has tree-based permissions system
        unset($controllers['file']);   // quite complex to implement with file selectors

        // These are tied to forms and are saved in a separate table for each form.
        // In any case, the permissions really apply to the forms themselves; there's no obvious
        // case for restricting access to individual form submissions
        unset($controllers['form_submission']);

        foreach ($controllers as $shorthand => $ctlr_class) {
            $reflect = new \ReflectionClass($ctlr_class);
            $props = $reflect->getDefaultProperties();

            // Ignore category controllers
            if ($reflect->isSubclassOf('Sprout\\Controllers\\Admin\\CategoryAdminController')) {
                unset($controllers[$shorthand]);
                continue;
            }

            // Ignore controllers without records
            if ($reflect->isSubclassOf('Sprout\\Controllers\\Admin\\NoRecordsAdminController')) {
                unset($controllers[$shorthand]);
                continue;
            }

            $controllers[$shorthand] = $props['friendly_name'];
        }
        asort($controllers);

        return $controllers;
    }


    /**
     * Return the navigation for this controller
     *
     * @return string HTML
     */
    public function _getNavigation()
    {
        $out = "<ul class=\"list-style-1\">";
        $out .= "<li><a href=\"admin/contents/{$this->controller_name}\">Configure tabs</a></li>";
        $out .= "<li><a href=\"admin/extra/per_record_permission/reset\">Reset a single tab</a></li>";
        $out .= "</ul>";

        return $out;
    }


    /**
     * Generate a form where operators can specify which controllers should have per-record permissions enabled
     *
     * This is instead of the normal behaviour: HTML which represents a list of records for a controller
     *
     * @return array Contains elements 'title' and 'content' as per {@see ManagedAdminController::_getContents}
     */
    public function _getContents()
    {
        if (!AdminPerms::canAccess('access_operators')) return new AdminError('Access denied');

        $controllers = $this->getControllerList();

        $view = new PhpView('sprout/admin/per_record_perms');
        $view->enabled = Pdb::q('SELECT name FROM ~per_record_controllers WHERE active = 1', [], 'col');
        $view->controllers = $controllers;

        return [
            'title' => Enc::html($this->friendly_name),
            'content' => $view->render(),
        ];
    }


    /**
     * Save which controllers should have per-record permissions enabled
     */
    public function save()
    {
        Csrf::checkOrDie();
        if (!AdminPerms::canAccess('access_operators')) return new AdminError('Access denied');

        $controllers = @$_POST['controllers'];
        if (!is_array($controllers)) $controllers = [];

        if (count($controllers) == 0) {
            Pdb::update('per_record_controllers', ['active' => 0], [1]);
        } else {
            Pdb::transact();

            $q = "UPDATE ~per_record_controllers SET active = 0 WHERE id = ?";
            $deactivate = Pdb::prepare($q);

            $q = "UPDATE ~per_record_controllers SET active = 1 WHERE id = ?";
            $activate = Pdb::prepare($q);

            $q = "INSERT INTO ~per_record_controllers (name, active) VALUES (?, 1)";
            $insert = Pdb::prepare($q);

            $q = "SELECT id, name, active
                FROM ~per_record_controllers";
            $res = Pdb::q($q, [], 'pdo');
            $extant = [];
            foreach ($res as $row) {
                if (in_array($row['name'], $controllers)) {
                    if (!$row['active']) {
                        Pdb::execute($activate, [$row['id']], 'null');
                        $this->logEdit('per_record_controllers', $row['id'], $row);
                    }
                } else {
                    if ($row['active']) {
                        Pdb::execute($deactivate, [$row['id']], 'null');
                        $this->logEdit('per_record_controllers', $row['id'], $row);
                    }
                }
                $extant[$row['name']] = $row['id'];
            }
            $res->closeCursor();

            foreach ($controllers as $controller) {
                if (isset($extant[$controller])) continue;

                Pdb::execute($insert, [$controller], 'null');
                $id = Pdb::getLastInsertId();
                $this->logAdd('per_record_controllers', $id);
            }

            Pdb::commit();
        }

        Notification::confirm('Configuration updated');

        Url::redirect('admin/contents/' . $this->controller_name);
    }


    public function _extraReset()
    {
        $controllers = $this->getControllerList();

        $q = "SELECT name FROM ~per_record_controllers WHERE active = 1 ORDER BY name";
        $active_controllers = Pdb::q($q, [], 'col');

        foreach ($controllers as $controller => $label) {
            if (!in_array($controller, $active_controllers)) {
                unset($controllers[$controller]);
            }
        }

        $out = '<form method="post" action="admin/call/' . $this->controller_name . '/resetSave">';
        $out .= Csrf::token();

        Form::nextFieldDetails('Tab to reset all per-record permissions on', true);
        $out .= Form::dropdown('controller', [], $controllers);

        $checked_cats = Form::getData('_prm_categories');

        $all_cats = AdminAuth::getAllCategories();
        unset($all_cats[AdminAuth::getPrimaryCategoryId()]);

        Form::nextFieldDetails('Allow changes by', false);
        $allow_cats = Form::checkboxSet('_prm_categories', [], $all_cats);

        // Hack in 'all operators' option
        $all = '<div class="field-element__input-set">';
        $all .= '<div class="fieldset-input"><input type="checkbox" value="1" name="_prm_all_cats" id="_prm_all"';
        if ($checked_cats == ['*']) $all .= ' checked';
        $all .= '><label for="_prm_all">All operators</label></div>';
        $allow_cats = str_replace('<div class="field-element__input-set">', $all, $allow_cats);

        $out .= $allow_cats;

        $out .= '<p><button type="submit" class="button">Reset permissions</button></p>';

        $out .= '</form>';

        return [
            'title' => Enc::html($this->friendly_name),
            'content' => $out,
        ];
    }


    public function resetSave()
    {
        Csrf::checkOrDie();
        $url = 'admin/extra/' . $this->controller_name . '/reset';

        $errs = false;
        if (empty($_POST['controller'])) {
            $errs = true;
            Notification::error('No tab specified');
            Url::redirect($url);
        }

        // Determine actual class name from Register
        try {
            $ctlr = Admin::getController($_POST['controller']);
        } catch (Exception $ex) {
            Notification::error('Invalid tab specified');
            Url::redirect($url);
        }

        $table = $ctlr->getTableName();
        $q = "SELECT id FROM ~{$table}";
        $res = Pdb::q($q, [], 'col');

        Pdb::transact();

        foreach ($res as $id) {
            PerRecordPerms::save($ctlr, $id);
        }

        Pdb::commit();

        $msg = 'Permissions updated for ' . Inflector::numPlural(count($res), 'record');
        $msg .= ' for ' . $ctlr->getFriendlyName();
        Notification::confirm($msg);

        Url::redirect($url);
    }

}
