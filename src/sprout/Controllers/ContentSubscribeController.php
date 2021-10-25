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

use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\Cron;
use Sprout\Helpers\Email;
use Sprout\Helpers\Enc;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Sprout;
use Sprout\Helpers\SubsiteSelector;
use Sprout\Helpers\Subsites;
use Sprout\Helpers\Url;
use Sprout\Helpers\View;


/**
 * Handles subscriptions to various types of content in a centralised manner
 *
 * Subscribers are emailed regarding new/updated content as various subscription handlers see fit
 */
class ContentSubscribeController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }


    /**
    * Unsubscribe a user from a subscription (form)
    **/
    public function unsub($id, $code)
    {
        $id = (int) $id;
        $code = trim($code);

        // Check the id and code match
        $q = "SELECT email
            FROM ~content_subscriptions
            WHERE id = ? AND code = ?
            LIMIT 1";
        try {
            $email = Pdb::q($q, [$id, $code], 'val');
        } catch (RowMissingException $ex) {
            Notification::error('Invalid id or unsubscription code');
            Url::redirect('result/error');
        }

        // Fetch all of the subscriptions for this email address.
        // Codes are derived from the email address so they'll all be the same.
        $q = "SELECT id, handler_class, handler_settings
            FROM ~content_subscriptions
            WHERE code = ? AND email = ?
            ORDER BY id";
        $res = Pdb::q($q, [$code, $email], 'arr');

        $subs = array();
        foreach ($res as $row) {
            // Create instance
            $inst = Sprout::instance($row['handler_class'], 'Sprout\\Helpers\\Subscribe');

            // Load settings
            $settings = json_decode($row['handler_settings'], true);
            if (!is_array($settings)) $settings = [];

            // Get the name
            $result = $inst->getName($settings);
            if (! $result) continue;

            $subs[$row['id']] = $result;
        }

        $view = new View('sprout/content_unsubscribe_form');
        $view->subscriptions = $subs;
        $view->id = $id;
        $view->code = $code;

        $page_view = new View('skin/inner');
        $page_view->page_title = 'Unsubscribe';
        $page_view->main_content = $view;
        $page_view->controller_name = $this->getCssClassName();
        echo $page_view->render();
    }


    /**
    * Unsubscribe a user from a subscription (action)
    **/
    public function unsubAction($id, $code)
    {
        $id = (int) $id;
        $code = trim($code);

        // Check the id and code match
        $q = "SELECT email
            FROM ~content_subscriptions
            WHERE id = ? AND code = ?
            LIMIT 1";
        try {
            $email = Pdb::q($q, [$id, $code], 'row');
        } catch (RowMissingException $ex) {
            Notification::error('Invalid id or unsubscription code');
            Url::redirect('result/error');
        }

        // Did they actually choose anything?
        if (empty($_POST['unsubscribe'])) {
            Notification::error('You didn\'t select anything');
            Url::redirect("content_subscribe/unsub/{$id}/{$code}");
        }

        // Fetch the IDs of all of the subscriptions for this email address.
        // Codes are derived from the email address so they'll all be the same.
        $q = "SELECT id
            FROM ~content_subscriptions
            WHERE code = ? AND email = ?";
        $ids = Pdb::q($q, [$code, $email], 'col');

        // Now we can iterate over the unsubscriptions, and remove them
        foreach ($_POST['unsubscribe'] as $id) {
            if (! in_array($id, $ids)) continue;

            Pdb::delete('content_subscriptions', ['id' => $id]);
        }

        Notification::confirm('You have been unsubscribed');
        Url::redirect('result/error');
    }


    /**
    * Send the content subscription emails to the users who have registered
    **/
    public function cronSendSubscriptions()
    {
        Cron::start("Content subscribe");

        Cron::message('Fetching subscriptions');

        $q = 'SELECT id, content_id, name, code, mobile FROM ~subsites';
        $subsites = Pdb::query($q, [], 'arr');

        foreach ($subsites as $subsite) {
            Cron::message('');
            Cron::message('');
            Cron::message('Subscriptions for subsite: ' . $subsite['name']);

            // Fake the subsite selection so that the subscription handlers and email sending all behaves correctly
            SubsiteSelector::$subsite_id = $subsite['id'];
            SubsiteSelector::$content_id = $subsite['content_id'] ?: $subsite['id'];
            SubsiteSelector::$subsite_code = $subsite['code'];
            SubsiteSelector::$mobile = $subsite['mobile'];

            // Get the records
            $q = "SELECT id, code, handler_class, handler_settings, name, email, subsite_id
                FROM ~content_subscriptions
                WHERE subsite_id = ?
                ORDER BY id";
            $res = Pdb::q($q, [$subsite['id']], 'pdo');

            // TODO: should this be per subscription or should be store when it last ran?
            $since = time() - 86400;

            Cron::message('Loading lists');

            // Get a unique list of class/settings
            $lists = array();
            $users = array();
            foreach ($res as $row) {

                if (!isset($users[$row['email']])) {
                    $users[$row['email']] = array(
                        'id' => $row['id'],
                        'code' => $row['code'],
                        'name' => $row['name'],
                        'subs' => array()
                    );
                }

                $users[$row['email']]['subs'][] = md5($row['handler_class'] . '.' . $row['handler_settings']);

                $key = md5($row['handler_class'] . '.' . $row['handler_settings']);
                if (isset($lists[$key])) continue;

                $class = $row['handler_class'];

                // Create class instance
                try {
                    $inst = Sprout::instance($class, 'Sprout\\Helpers\\Subscribe');
                } catch (InvalidArgumentException $ex) {
                    Cron::message("    Loading '{$class}' failed: {$ex->getMessage()}");
                    continue;
                }

                // Load settings
                $settings = json_decode($row['handler_settings'], true);
                if (!is_array($settings)) $settings = [];

                // Run method to fetch items
                try {
                    $result = $inst->getList($settings, $since);
                    if (! is_array($result)) throw new Exception("Returned result is not an array");

                } catch (Exception $ex) {
                    Cron::message("    Loading '{$class}' failed: {$ex->getMessage()}");
                    continue;
                }

                Cron::message("    Loaded '{$class}'; Key '{$key}'; Num rows: " . count($result));

                // Set the URL as the key for each item
                // This prevents duplicates when the items are sent out
                $uniq_result = array();
                foreach ($result as $row) {
                    $uniq_result[$row['url']] = $row;
                }

                // Save result
                $lists[$key] = $uniq_result;
            }

            Cron::message('');
            Cron::message('Building user lists');

            $res->closeCursor();

            Cron::message('Num users: ' . count($users));
            Cron::message('');

            // For each user, build, sort and send out the lists
            $none = 0;
            $success = 0;
            $failure = 0;
            foreach ($users as $email => $deets) {
                $items = array();
                foreach ($deets['subs'] as $listkey) {
                    if ($lists[$listkey]) {
                        $items = array_merge($items, $lists[$listkey]);
                    }
                }

                if (count($items) == 0) {
                    $none++;
                    continue;
                }

                usort($items, 'Sprout\\Helpers\\ContentSubscribe::tsSort');

                foreach ($items as &$row) {
                    if ($row['url'][0] == '/') {
                        $row['url'] = Subsites::getAbsRoot($subsite['id']) . ltrim($row['url'], '/');
                    }
                }

                $subsite_title = Subsites::getConfig('site_title', $subsite['id']);

                $view = new View('sprout/email/content_subscribe');
                $view->unsubscribe_url = Subsites::getAbsRoot($subsite['id']) . "content_subscribe/unsub/{$deets['id']}/{$deets['code']}";
                $view->name = $deets['name'];
                $view->email = $email;
                $view->items = $items;
                $view->subsite_title = $subsite_title;

                $mail = new Email();
                $mail->AddAddress($email);
                $mail->Subject = 'Updates on the ' . $subsite_title . ' website';
                $mail->SkinnedHTML($view->render());
                $result = $mail->Send();

                Cron::message($email . ($result ? '; success' : '; failure'));

                if ($result) { $success++; } else { $failure++; }
            }
        }

        if (!$success and $failure) {
            Cron::failure('All emails we tried to send failed; is there a server config error?');
        }

        if ($success or $failure) {
            Cron::message('');
        }

        Cron::message('');
        Cron::message('No items:  ' . $none);
        Cron::message('Success:   ' . $success);
        Cron::message('Failed:    ' . $failure);

        Cron::success();
    }


    /**
    * Tool to clean up subscriptions which refer to classes that don't exist.
    **/
    public function cleanupInvalidClasses()
    {
        AdminAuth::checkLogin();

        // Get the records
        $q = "SELECT handler_class
            FROM ~content_subscriptions
            GROUP BY handler_class
            ORDER BY handler_class";
        $res = Pdb::q($q, [], 'arr');

        echo '<pre>';

        if (! $_GET['delete']) {
            echo 'Not deleting, use GET param delete=1 to delete categories.', PHP_EOL, PHP_EOL;
        } else {
            echo 'Deleting invalid subscriptions, if found.', PHP_EOL, PHP_EOL;
        }

        foreach ($res as $row) {
            $delete = false;

            if (class_exists($row['handler_class'])) {
                echo '<span style="color: #090;">[Found  ] ', Enc::html($row['handler_class']), '</span>', PHP_EOL;
            } else {
                echo '<span style="color: #900;">[MISSING] ', Enc::html($row['handler_class']), '</span>', PHP_EOL;
                $delete = true;
            }

            if ($_GET['delete'] and $delete) {
                Pdb::delete('content_subscriptions', ['handler_class' => $row['handler_class']]);
                echo '<span style="color: #090;">[Deleted] ', Enc::html($row['handler_class']), '</span>', PHP_EOL;
            }
        }

        echo '</pre>';
    }

}
