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

use Kohana;
use Sprout\Helpers\BaseView;
use Sprout\Helpers\Captcha;
use Sprout\Helpers\Csrf;
use Sprout\Helpers\Email;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Notification;
use Sprout\Helpers\RateLimit;
use Sprout\Helpers\Session;
use Sprout\Helpers\Spam;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\Url;
use Sprout\Helpers\Validator;
use Sprout\Helpers\PhpView;


/**
* - No description yet -
**/
class EmailShareController extends Controller
{

    /**
    * Constructor
    **/
    public function __construct()
    {
        parent::__construct();
        Session::instance();
    }


    /**
     * Validate that a given URL is absolute and for this site
     *
     * Allows for subdomains of the current site, so example.com can share a link to app.example.com
     * This is a ends-with chec, so example.com cannot share example.com.au
     *
     * @param string $url
     * @return bool True if valid, false if not
     */
    private static function validateUrl($url)
    {
        $parts = parse_url($url);

        // Require an absolute url, i.e. has a scheme and a host
        if (empty($parts['scheme'])) return false;
        if (empty($parts['host'])) return false;

        // Require the hostname to end in the the server name
        $server_name = preg_replace('!^www\.!', '', $_SERVER['SERVER_NAME']);
        $length = strlen($server_name);
        if (substr($parts['host'], -$length) !== $server_name) {
            return false;
        }

        return true;
    }


    /**
    * Form to share
    **/
    public function share()
    {
        $data = @$_SESSION['email_share']['field_values'];
        if (!$data) $data = [];

        if (empty($data['url']) and !empty($_GET['url'])) {
            if (self::validateUrl($_GET['url'])) {
                $data['url'] = $_GET['url'];
            } else {
                throw new Exception('Invalid URL');
            }
        }

        if (empty($data['title'])) {
            if (!empty($_GET['title'])) {
                $data['title'] = $_GET['title'];
            } else if (!empty($data['url'])) {
                $data['title'] = $data['url'];
            }
        }

        if (empty($data['url'])) {
            Notification::error('No URL to share');
            Url::redirect('result/error');
        }

        $form = new PhpView('sprout/email_share_form');
        $form->data = $data;
        if (!empty($_SESSION['email_share']['field_errors'])) {
            $form->errors = $_SESSION['email_share']['field_errors'];
        } else {
            $form->errors = [];
        }
        if (empty($_SESSION['email_share']['captcha_passed'])) {
            $form->use_captcha = true;
        }

        $page_view = BaseView::create('skin/inner');
        $page_view->page_title = 'Share a page: ' . $data['title'];
        $page_view->main_content = $form;
        $page_view->controller = 'email_share';

        echo $page_view->render();
    }

    /**
    * Send a shared email
    **/
    public function submit()
    {
        Csrf::checkOrDie();
        Spam::checkOrDie();

        // Cap submissions (both success and failure)
        $result = RateLimit::checkLimitIP('email-share-action', null, 25, 10 * 60);
        if ($result === false) {
            throw new Exception("Rate limit exceeded: 25 submissions per 10 mins");
        }

        $_POST['title'] = trim(@$_POST['title']);
        $_POST['url'] = trim(@$_POST['url']);
        $_POST['their_name'] = trim(@$_POST['their_name']);
        $_POST['their_email'] = trim(@$_POST['their_email']);
        $_POST['message'] = trim(@$_POST['message']);

        if (!self::validateUrl($_POST['url'])) {
            throw new Exception('Invalid URL');
        }

        $_SESSION['email_share']['field_values'] = Validator::trim($_POST);

        $valid = new Validator($_POST);
        $valid->required(['url', 'their_name', 'their_email']);
        $valid->check('title', 'Validity::length', 0, 255);
        $valid->check('url', 'Validity::length', 0, 255);
        $valid->check('their_name', 'Validity::length', 0, 255);
        $valid->check('their_email', 'Validity::email');
        $valid->check('their_email', 'Validity::length', 0, 255);
        $valid->check('message', 'Validity::length', 0, 500);

        if (empty($_SESSION['email_share']['captcha_passed'])) {
            if (Captcha::check()) {
                $_SESSION['email_share']['captcha_passed'] = true;
            } else {
                $valid->addGeneralError('Incorrect CAPTCHA response');
            }
        }

        if ($valid->hasErrors()) {
            RateLimit::logHitFailure('email-share-action');
            $_SESSION['email_share']['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            Url::redirect('email_share/share');
        }


        $view = new PhpView('sprout/email/email_share');
        $view->site_title = Kohana::config('sprout.site_title');
        $view->page_title = $_POST['title'];
        $view->page_url = $_POST['url'];
        $view->their_name = $_POST['their_name'];
        $view->message = $_POST['message'];

        $mail = new Email();
        $mail->AddAddress($_POST['their_email']);
        $mail->Subject = 'Shared page on ' . $view->site_title;
        $mail->SkinnedHTML($view->render());
        $mail->Send();

        RateLimit::logHitSuccess('email-share-action');

        unset($_SESSION['email_share']);
        Notification::confirm('Message sent');
        Url::redirect ('email_share/thanks?url=' . Enc::url($_POST['url']));
    }


    /**
    * Thank you page
    **/
    public function thanks()
    {
        $match = false;
        $siteRoot = substr(Sprout::absRoot(), 0, -1);
        if (empty($_GET['url']) or strpos($_GET['url'], $siteRoot) !== 0) {
            Notification::error('The URL you provided appears invalid');
            Url::redirect ('');
        }

        $form = new PhpView('sprout/email_share_thanks');
        $form->url = $_GET['url'];

        // Prepare the view
        $page_view = BaseView::create('skin/inner');
        $page_view->page_title = 'Share a page';
        $page_view->main_content = $form;
        $page_view->controller_name = 'email_share';

        echo $page_view->render();
    }

}


