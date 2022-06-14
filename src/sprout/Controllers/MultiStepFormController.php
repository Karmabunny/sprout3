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

use Kohana_404_Exception;

use karmabunny\pdb\Exceptions\QueryException;
use Sprout\Helpers\BaseView;
use Sprout\Helpers\Notification;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Session;
use Sprout\Helpers\Url;
use Sprout\Helpers\Validator;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\Sprout;


/**
* Supports multistep forms
*/
abstract class MultiStepFormController extends Controller {
    /**
     * Steps and functions/views used to implement them
     * Keys are step numbers, values are function (and matching view) names
     * e.g. [1 => 'intro', 2 => 'personal_details']
     */
    protected $steps = [];

    /** Key for partitioning session data associated with this form, i.e. $_SESSION[$this->session_key] */
    protected $session_key = 'DUMMY';

    /** Route to form, relative to site root */
    protected $route = 'multistep';

    /** Page title to be displayed on form steps */
    protected $page_title = 'Form';

    /** Appendage to route, where user is redirected after save on final step */
    protected $complete_function = 'complete';

    /** Directory in which views are stored, without trailing slash */
    protected $view_dir = '';

    /** Table into which data will be saved at the final step */
    protected $table = '';


    /**
    * The method used to drive the forms at each step
    * This should be called by the entry function of the subclass controller
    * @param int $step The current step
    * @return View The view containing the form for that step
    */
    protected function form($step = -1)
    {
        $step = (int) $step;

        // Allow arrays with 0-based or 1-based ordering
        $first_step = $this->firstStep();
        if ($step < $first_step) $step = $first_step;

        $session = $this->getSession();
        $this->checkStep($session, $step);

        $data = @$session['data'];
        if (!is_array($data)) $data = array();

        $view_name = $this->steps[$step];
        if (!$view_name) {
            throw new Kohana_404_Exception();
        }

        $view = new PhpView("{$this->view_dir}/{$view_name}");
        $view->submit_url = "{$this->route}/submit/{$step}";
        $view->session_key = $this->session_key;
        $view->step = $step;
        $view->steps = count($this->steps);
        $view->data = $data;
        if (!empty($session['field_errors'])) {
            $view->errors = $session['field_errors'];
        } else {
            $view->errors = [];
        }

        // Allow loading custom data (e.g. for select fields)
        if (method_exists($this, $view_name)) {
            $this->$view_name($view);
        }

        return $view;
    }


    /**
     * Display the thanks message after the process has been completed
     * @return void
     */
    public function complete()
    {
        $view = new PhpView("{$this->view_dir}/complete");

        $page_view = BaseView::create('skin/inner');
        $page_view->main_content = $view->render();
        $page_view->page_title = "{$this->page_title}: complete";
        $page_view->controller_name = $this->getCssClassName();
        echo $page_view->render();
    }


    /**
    * The method used to drive the form submissions at each step
    * This should be called by the submit function of the subclass controller
    * @param int $step The current step
    * @return void On the final step, the save() method is called. On earlier
    *         steps, the user is redirected to the form for the next step.
    */
    protected function submit($step)
    {
        $step = (int) $step;

        $session = &self::getSession();
        $this->checkStep($session, $step);

        $handler_function = $this->steps[$step];
        if (!$handler_function) throw new Kohana_404_Exception();
        $handler_function .= 'Submit';

        if (!isset($session['data'])) $session['data'] = array();

        if (!method_exists($this, $handler_function)) {
            throw new Exception("Missing handler $handler_function");
        }

        $this->$handler_function($step);

        // Allow arrays with 0-based or 1-based ordering
        $step_nums = array_keys($this->steps);
        $last_step = end($step_nums);
        if ($step == $last_step) {
            $this->save();
        } else {
            ++$step;
            $session['step'] =  $step;
            Url::redirect($this->buildUrl($step));
        }
    }


    /**
     * Gets the number (should be 0 or 1) of first step of the process
     * @return int Usually 0 or 1, although in theory step 1000 can be the first step if so desired
     */
    protected function firstStep()
    {
        return Sprout::iterableFirstKey($this->steps);
    }


    /**
    * Check the user has access to a step
    * @return void A redirect is performed if the user isn't ready for the step
    */
    protected function checkStep($session, $step)
    {
        $session_step = (int) @$session['step'];
        if ($session_step < 1) $session_step = 1;

        if ($session_step >= $step) return;

        Url::redirect($this->buildUrl($session_step));
    }


    /**
    * Instantiate the session and make sure the session key is useable
    * @return array
    */
    protected function &getSession() {
        $session = Session::instance();
        if (!isset($_SESSION[$this->session_key])) {
            $_SESSION[$this->session_key] = array();
        }
        return $_SESSION[$this->session_key];
    }


    /**
     * @param int $step
     * @param array $reqd Names of required fields, e.g. ['name', 'email']
     * @param array $rules Each element is an array which is passed to {@see Validator::check},
     *        e.g. [['first_name', 'Validity::length', 0, 20], ['last_name', 'Validity::length', 0, 20]]
     * @param mixed $valid a {@see Validator}, or null to create one on the fly. Specifying a Validator
     *        allows the addition of rules/errors before this method is called.
     * @return void A redirect will occur if validation fails
     */
    protected function validate($step, array $reqd, array $rules, Validator $valid = null)
    {
        $session = &self::getSession();

        if (!$valid) {
            Validator::trim($_POST);
            $valid = new Validator($_POST);
        }

        $valid->required($reqd);

        foreach ($rules as $rule) {
            $name = Sprout::iterableFirstValue($rule);
            $session['data'][$name] = @$_POST[$name];
            call_user_func_array([$valid, 'check'], $rule);
        }

        if ($valid->hasErrors()) {
            $session['field_errors'] = $valid->getFieldErrors();
            $valid->createNotifications();
            Url::redirect($this->buildUrl($step));
        }
        unset($session['field_errors']);
    }


    /**
    * Builds a URL to the form at a particular step
    * @param int $step
    * @param bool $include_first Include /0 or /1 ending for the first step
    * @return string the URL
    */
    protected function buildUrl($step, $include_first = false)
    {
        $url = $this->route;
        if ($step != $this->firstStep() or $include_first) {
            $url .= "/{$step}";
        }
        return $url;
    }


    /**
     * Do any final preparation / data massaging; then call saveData and finally redirect.
     * This should generally be overridden.
     */
    protected function save()
    {
        try {
            $id = $this->saveData($this->table);

            // If copy-pasting this function, add post-insert stuff like emailing a thank you notice here

            Url::redirect("{$this->route}/{$this->complete_function}");

        } catch (QueryException $ex) {
            Notification::error('A database error occurred. Please contact us to resolve the issue.');

            // Return to last step of the form
            $keys = array_keys($this->steps);
            Url::redirect($this->build_url(end($keys)));
        }
    }


    /**
    * Save the data, e.g. into a database, after successful validation at the
    * final step
    * @param string $table The table to save the data in, e.g. 'members'
    * @param array $sub_tables The subtables which should be used. The keys
    *        must match those used in the $session['data'] array. Each of the
    *        values is itself an array with one element. The 0-th value is the
    *        subtable to save the data in, and the 1st value is the column in
    *        that table which links to the core table,
    *        e.g. ['preferences' => ['member_prefs', 'member_id']]
    * @param array $extra_fields Extra field values which should be set before
    *        performing the inserts. The keys are the table names, and each
    *        value is an array of column name to value mappings,
    *        e.g. ['members' => ['date_added' => Pdb::now(), ...]]
    * @return int|null The insert id from the newly created record,
    *         or null if no table is specified
    */
    protected function saveData($table, array $sub_tables = array(), array $extra_fields = array()) {
        $session = &self::getSession();
        $data = $session['data'];

        if (IN_PRODUCTION) unset($_SESSION[$this->session_key]);

        // Nothing can be saved in DB if table isn't specified
        if ($table == '') return null;

        // Move data for subtables out of the data for the core table
        $sub_data = array();
        foreach ($sub_tables as $key => $config) {
            $sub_data[$key] = array();

            // Transpose data, e.g. ['name'][1-5] => [1-5]['name']
            $values = $data[$key];
            foreach ($values as $field => $arr) {
                foreach ($arr as $num => $value) {
                    $sub_data[$key][$num][$field] = $value;
                }
            }
            unset($data[$key]);
        }

        // Insert data into core table
        $insert_data = array();
        foreach ($data as $field => $val) {
            $insert_data[$field] = $val;
        }
        if (isset($extra_fields[$table])) {
            foreach ($extra_fields[$table] as $field => $val) {
                $insert_data[$field] = $val;
            }
        }
        $insert_data['date_added'] = Pdb::now();
        $insert_data['date_modified'] = Pdb::now();

        Pdb::transact();
        $id = Pdb::insert($table, $insert_data);

        // Insert data into subtables
        foreach ($sub_tables as $key => $config) {
            list($sub_name, $link_col) = $config;

            $insert_data = array($link_col => $id);
            foreach ($sub_data[$key] as $record) {
                foreach ($record as $field => $val) {
                    $insert_data[$field] = $val;
                }
                if (isset($extra_fields[$sub_name])) {
                    foreach ($extra_fields[$sub_name] as $field => $val) {
                        $insert_data[$field] = $val;
                    }
                }
                Pdb::insert($sub_name, $insert_data);
            }
        }
        Pdb::commit();

        return $id;
    }
}
