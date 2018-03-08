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
use Sprout\Helpers\Form;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Url;
use Sprout\Helpers\Validator;
use Sprout\Helpers\View;


/**
 * Changes settings for the currently logged in user
 */
class MySettingsAdminController extends NoRecordsAdminController
{
    protected $controller_name = 'my_settings';
    protected $friendly_name = 'My settings';


    public function _intro()
    {
        Url::redirect('admin/extra/my_settings/details');
    }

    public function _getNavigation()
    {
        return '&nbsp;';
    }


    /**
     * UI for updating details of the currently logged-in operator
     */
    public function _extraDetails()
    {
        $view = new View('sprout/admin/my_settings');

        $data = Form::loadFromSession('admin_my_settings');
        if (!$data) {
            Form::setData(AdminAuth::getDetails());
        }

        return $view;
    }


    /**
     * Action to update operator details for the currently logged-in operator
     *
     * @return void Redirects
     */
    public function detailsAction()
    {
        Csrf::checkOrDie();
        $_SESSION['admin_my_settings']['field_values'] = Validator::trim($_POST);

        $valid = new Validator($_POST);
        $valid->required(['name', 'email']);
        $valid->check('name', 'Validity::length', 1, 200);
        $valid->check('email', 'Validity::length', 1, 200);
        $valid->check('email', 'Validity::email');
        $valid->multipleCheck(['password1', 'password2'], 'Validity::allMatch');

        if (!empty($_POST['password1']) and $_POST['password1'] === $_POST['password2']) {
            // Check old password is correct
            $result = AdminAuth::checkPassword($_POST['old_password'], AdminAuth::getId());
            if ($result === false) {
                $valid->addFieldError('old_password', 'Old password is incorrect');
            }

            // Check password is complex enough
            $complexity = Sprout::passwordComplexity($_POST['password1']);
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
            $_SESSION['admin_my_settings']['field_errors'] = $valid->getFieldErrors();
            Url::redirect('admin/extra/my_settings/details');
        }

        Pdb::transact();

        $data = array();
        $data['name'] = $_POST['name'];
        $data['email'] = $_POST['email'];
        $data['date_modified'] = Pdb::now();
        Pdb::update('operators', $data, ['id' => AdminAuth::getId()]);

        if (!empty($_POST['password1'])) {
            AdminAuth::changePassword($_POST['password1'], AdminAuth::getId());
        }

        Pdb::commit();

        unset($_SESSION['admin_my_settings']);
        Notification::confirm('Settings have been saved');
        Url::redirect('admin/extra/my_settings/details');
    }

}
