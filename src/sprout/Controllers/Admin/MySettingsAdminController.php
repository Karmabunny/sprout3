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
use Sprout\Helpers\Security;
use Sprout\Helpers\TwoFactor\GoogleAuthenticator;
use Sprout\Helpers\Url;
use Sprout\Helpers\Validator;
use Sprout\Helpers\PhpView;


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
        return '';
    }

    public function _getTools()
    {
        $tools = [];
        $tools[] = '<li><a href="admin/extra/my_settings/details">Details and password</a></li>';
        $tools[] = '<li><a href="admin/extra/my_settings/twoFactor">Setup two-factor auth</a></li>';
        return $tools;
    }


    /**
     * UI for updating details of the currently logged-in operator
     */
    public function _extraDetails()
    {
        $view = new PhpView('sprout/admin/my_settings/details');

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
            $complexity = Security::passwordComplexity($_POST['password1'], 8, 2, true);
            if (!empty($complexity)) {
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


    /**
     * Show a UI to either setup TFA or to disable TFA
     */
    public function _extraTwoFactor()
    {
        $q = "SELECT tfa_method, username FROM ~operators WHERE id = ?";
        $operator = Pdb::query($q, [AdminAuth::getLocalId()], 'row');

        if ($operator['tfa_method'] == 'none') {
            $goog = new GoogleAuthenticator();

            if (empty($_SESSION['tfa_secret'])) {
                $_SESSION['tfa_secret'] = $goog->generateSecret();
            }

            $issuer = Kohana::config('sprout.site_title') . ' admin';
            $qr_data = $goog->getQRData(
                $issuer, $operator['username'], $_SERVER['HTTP_HOST'], $_SESSION['tfa_secret']
            );
            $qr_img = $goog->getQRImageUrl($qr_data);

            $view = new PhpView('sprout/tfa/totp_setup');
            $view->action_url = 'admin/call/my_settings/tfaTotpSetupAction';
            $view->secret = $_SESSION['tfa_secret'];
            $view->qr_img = $qr_img;

        } else {
            $view = new PhpView('sprout/tfa/disable');
            $view->action_url = 'admin/call/my_settings/tfaDisableAction';
        }

        return [
            'title' => 'Two factor authentication',
            'content' => $view->render(),
        ];
    }


    /**
     * Setup TFA using the TOTP method
     */
    public function tfaTotpSetupAction()
    {
        $goog = new GoogleAuthenticator();
        $success = $goog->checkCode($_SESSION['tfa_secret'], $_POST['code']);

        if (!$success) {
            unset($_SESSION['tfa_secret']);
            Notification::error('Verifiction failed - please re-scan and try again');
            Url::redirect('admin/extra/my_settings/twoFactor');
        }

        $data = [];
        $data['tfa_method'] = 'totp';
        $data['tfa_secret'] = $_SESSION['tfa_secret'];
        Pdb::update('operators', $data, ['id' => AdminAuth::getLocalId()]);

        unset($_SESSION['tfa_secret']);
        Notification::confirm('Two factor auth has been enabled');
        Url::redirect('admin/extra/my_settings/twoFactor');
    }


    /**
     * Disable TFA
     */
    public function tfaDisableAction()
    {
        $data = [];
        $data['tfa_method'] = 'none';
        $data['tfa_secret'] = '';
        Pdb::update('operators', $data, ['id' => AdminAuth::getLocalId()]);

        Notification::confirm('Two factor auth has been disabled');
        Url::redirect('admin/extra/my_settings/twoFactor');
    }

}
