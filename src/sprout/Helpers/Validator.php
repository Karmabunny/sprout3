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
namespace Sprout\Helpers;

use InvalidArgumentException;

use Sprout\Exceptions\ValidationException;


/**
 * New validation class for Sprout 3.
 * Used with the {@see Validity} class.
 *
 * @example
 *    // Plain example
 *
 *    $valid = new Validator($_POST);
 *
 *    $valid->required(['name', 'email']);
 *
 *    $valid->check('name', 'Validity::length', 1, 100);
 *    $valid->check('email', 'Validity::email');
 *
 *    if ($valid->hasErrors()) {
 *        $_SESSION['register']['field_errors'] = $valid->getFieldErrors();
 *        $valid->createNotifications();
 *        Url::redirect('user/register');
 *    }
 * @example
 *    // Multiedit example for a course with students
 *
 *    $has_error = false;
 *
 *    $valid = new Validator($_POST);
 *    $valid->required(['name']);
 *    $valid->check('name', 'Validity::length', 1, 100);
 *
 *    if ($valid->hasErrors()) {
 *        $_SESSION['course_edit']['field_errors'] = $valid->getFieldErrors();
 *        $valid->createNotifications();
 *        $has_error = true;
 *    }
 *
 *    if (empty($_POST['multiedit_students'])) {
 *        $_POST['multiedit_students'] = [];
 *    }
 *
 *    $record_num = 0;
 *    foreach ($_POST['multiedit_students'] as $idx => $data) {
 *        if (MultiEdit::recordEmpty($data)) continue;
 *
 *        ++$record_num;
 *
 *        $multi_valid = new Validator($data);
 *        $multi_valid->setLabels([
 *            'name' => 'Name for student ' . $record_num,
 *            'email' => 'Email address for student ' . $record_num,
 *        ]);
 *
 *        $multi_valid->required(['name', 'email']);
 *        $multi_valid->check('name', 'Validity::length', 1, 100);
 *        $multi_valid->check('email', 'Validity::email');
 *
 *        if ($multi_valid->hasErrors()) {
 *            $_SESSION['course_edit']['field_errors']['multiedit_students'][$idx] = $multi_valid->getFieldErrors();
 *            $multi_valid->createNotifications();
 *            $has_error = true;
 *        }
 *    }
 *
 *    if ($has_error) {
 *        Url::redirect('course/edit');
 *    }
 */
class Validator
{
    protected $labels;
    protected $data;
    protected $field_errors;
    protected $general_errors;


    /**
     * Recursive trim data
     *
     * Alters in-place AND returns the array
     * This allows for use such as:
     *
     *    $_SESSION['register']['field_values'] = Validator::trim($_POST);
     *
     * When used like this, the session gets set and the POST data is also trimmed,
     * so can be used directly for database inserts.
     *
     * @param array $data Data to trim. Passed by-reference.
     * @return array Trimmed data
     */
    public static function trim(array &$data)
    {
        foreach ($data as $key => $val) {
            if (is_string($val)) {
                $data[$key] = trim($val);
            } elseif (is_array($val)) {
                self::trim($val);
            }
        }

        return $data;
    }


    /**
     * @param array $data Data to validate
     */
    public function __construct(array $data)
    {
        $this->labels = null;
        $this->data = $data;
        $this->field_errors = array();
        $this->general_errors = array();
    }


    /**
     * Field labels make error messages a little friendlier
     *
     * @param array $labels Field labels
     */
    public function setLabels(array $labels)
    {
        $this->labels = $labels;
    }


    /**
     * Update the data to validate
     *
     * @param array $data Data to validate
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }


    /**
     * Set the value for a single data field
     *
     * @param string $field The field to set
     * @param mixed $value The value to set on the field
     */
    public function setFieldValue($field, $value)
    {
        $this->data[$field] = $value;
    }


    /**
     * Set the label for a single field
     *
     * @param string $field The field to set
     * @param string $label The label to set on the field
     */
    public function setFieldLabel($field, $label)
    {
        $this->labels[$field] = $label;
    }


    /**
     * For a given function, expand the namespace for Sprout helpers
     *
     * @param callable|string $func The function to expand with the Sprout\Helpers namespace.
     *        Strings representing function names are affected; closures are not.
     * @return callable $func
     */
    protected function expandNs($func)
    {
        if (is_string($func) and strpos($func, '::') !== false) {
            list($class, $func) = explode('::', $func);
            $class = Sprout::nsClass($class, ['Sprout\Helpers']);
            $func = $class . '::' . $func;
        }
        return $func;
    }


    /**
     * Check the value of a field against a validation method, storing any error messages received
     * Additional arguments are passed to the underlying method
     *
     * Methods which are on classes within the Sprout\Helpers namespace do not need the namespace
     * specified on the function name
     *
     * If a field has already been checked with {@see Validator::required} and the field was empty,
     * this function will not report errors (but will still return an appropriate value)
     *
     * If a empty value is provided, it is not validated - returns true
     *
     * @param string $field_name The field to check
     * @param callable $func The function or method to call.
     * @return bool True if validation was successful, false if it failed
     */
    public function check($field_name, $func)
    {
        if (!isset($this->data[$field_name]) or self::isEmpty($this->data[$field_name])) {
            return true;
        }

        $func = self::expandNs($func);

        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        array_unshift($args, $this->data[$field_name]);

        try {
            call_user_func_array($func, $args);
            return true;

        } catch (ValidationException $ex) {
            $this->addFieldError($field_name, $ex->getMessage());
            return false;
        }
    }


    /**
     * Run a validation check against each value in an array.
     * Behaviour is very similar to the {@see Validator::check} method.
     *
     * Only supports single-depth arrays
     *
     * Errors are put into the field_errors array under a subkey matching the array key
     *
     * Return value is an array of key => boolean with the validation result for each key
     *
     * @example
     *    $data = ['vals' => [1, 2, 'A', 'B', 5]];
     *    $validator = new Validator($data);
     *    $result = $validator->arrayCheck('vals', 'Validity::positiveInt');
     *    // $result now contains [true, true, false, false, true]
     *    $errs = $validator->getFieldErrors();
     *    // $errs now contains [ 'vals' => [2 => [...], 3 => [...]] ]
     *
     * @param string $field_name The field to check
     * @param callable $func The function or method to call.
     * @return array Key => Boolean True if validation was successful, false if it failed
     */
    public function arrayCheck($field_name, $func)
    {
        if (!isset($this->data[$field_name]) or self::isEmpty($this->data[$field_name])) {
            return true;
        }
        if (!is_array($this->data[$field_name])) {
            throw new InvalidArgumentException("Field <{$field_name}> is not an array");
        }

        $func = self::expandNs($func);

        $args = func_get_args();
        array_shift($args);
        array_unshift($args, $this->data[$field_name]);

        $results = [];
        foreach ($this->data[$field_name] as $index => $value) {
            $args[0] = $value;

            try {
                call_user_func_array($func, $args);
                $results[$index] = true;

            } catch (ValidationException $ex) {
                $this->addArrayFieldError($field_name, $index, $ex->getMessage());
                $results[$index] = false;
            }
        }

        return $results;
    }


    /**
     * Check multiple fields against a validation method
     *
     * This is similar to {@see Validator::check} but it's designed for different validation
     * methods, which work on a set of fields instead of a single field (e.g. Validity::oneRequired)
     *
     * Additional arguments are passed to the underlying method
     * Methods which are on classes within the Sprout\Helpers namespace do not need the namespace
     * specified on the function name
     *
     * @param array $fields The fields to check
     * @param callable $func The function or method to call.
     * @return bool True if validation was successful, false if it failed
     */
    public function multipleCheck(array $fields, $func)
    {
        $func = self::expandNs($func);

        $vals = array();
        foreach ($fields as $field_name) {
            $vals[] = @$this->data[$field_name];
        }

        $args = func_get_args();
        array_shift($args);
        array_shift($args);
        array_unshift($args, $vals);

        try {
            call_user_func_array($func, $args);
            return true;

        } catch (ValidationException $ex) {
            $this->addMultipleFieldError($fields, $ex->getMessage());
            return false;
        }
    }


    /**
     * Sadly, the PHP builtin empty() considers '0' to be empty, but it actually isn't
     *
     * @param mixed $val
     * @return bool True if empty, false if not.
     */
    public static function isEmpty($val)
    {
        if (is_array($val) and count($val) == 0) {
            return true;
        } else if ($val == '') {
            return true;
        }
        return false;
    }


    /**
     * Checks various fields are required
     * If a field is required and no value is provided, no other validation will be proessed for that field.
     *
     * @param array $fields Fields to check
     */
    public function required(array $fields)
    {
        foreach ($fields as $field_name) {
            if (!isset($this->data[$field_name])) {
                $this->field_errors[$field_name] = ['required' => 'This field is required'];
            } elseif (self::isEmpty($this->data[$field_name])) {
                $this->field_errors[$field_name] = ['required' => 'This field is required'];
            }
        }
    }


    /**
     * Add an error message for a given field to the field errors list
     *
     * @param string $field_name The field to add the error message for
     * @param string $message The message text
     */
    public function addFieldError($field_name, $message)
    {
        if (!isset($this->field_errors[$field_name])) {
            $this->field_errors[$field_name] = [$message];
        } else {
            $this->field_errors[$field_name][] = $message;
        }
    }


    /**
     * Add an error message for a given field to the field errors list
     * This variation is for array validation, e.g. an array of integers
     *
     * @param string $field_name The field to add the error message for
     * @param int $index The array index of the field to report error for
     * @param string $message The message text
     */
    public function addArrayFieldError($field_name, $index, $message)
    {
        if (!isset($this->field_errors[$field_name])) {
            $this->field_errors[$field_name] = [];
        }
        if (!isset($this->field_errors[$field_name][$index])) {
            $this->field_errors[$field_name][$index] = [$message];
        } else {
            $this->field_errors[$field_name][$index][] = $message;
        }
    }


    /**
     * Add an error message from a multiple-field validation (e.g. checking at least one is set)
     *
     * @param array $fields The fields to add the error message for
     * @param string $message The message text
     */
    public function addMultipleFieldError(array $fields, $message)
    {
        foreach ($fields as $f) {
            $this->addFieldError($f, $message);
        }
    }


    /**
     * Get an array of all field errors, indexed by field name
     * Fields may have multiple errors defined
     *
     * @return array
     */
    public function getFieldErrors()
    {
        return $this->field_errors;
    }


    /**
     * Add a general error message, e.g. for errors affecting many fields
     *
     * @param string $message The message text
     */
    public function addGeneralError($message)
    {
        $this->general_errors[] = $message;
    }


    /**
     * Get an array of all general errors
     *
     * @return array
     */
    public function getGeneralErrors()
    {
        return $this->general_errors;
    }


    /**
     * @return bool True if there were any validation errors, false if there wasn't
     */
    public function hasErrors()
    {
        if (count($this->field_errors)) return true;
        if (count($this->general_errors)) return true;
        return false;
    }


    /**
     * Create notification error messages for each error
     *
     * @param string $scope Set the scope for the notifications
     */
    public function createNotifications($scope = 'default')
    {
        foreach ($this->general_errors as $msg) {
            Notification::error($msg, 'plain', $scope);
        }

        foreach ($this->field_errors as $field => $msg) {
            if (isset($this->labels[$field])) {
                Notification::error($this->labels[$field] . ' -- ' . implode(', ', $msg), 'plain', $scope);
            } else {
                $label = ucfirst(str_replace('_', ' ', $field));
                Notification::error($label . ' -- ' . implode(', ', $msg), 'plain', $scope);
            }
        }
    }


    /**
     * Return errors as a mapped array that can be used as needed
     *
     * @return array An array of Field Label = Error msg
     */
    public function errorsAsArray()
    {
        $errors = [];

        foreach ($this->general_errors as $msgs) {
            $errors[''][] = $msgs;
        }
        foreach ($this->field_errors as $field => $msgs) {
            $errors[$this->labels[$field]] = $msgs;
        }

        $out = [];
        foreach ($errors as $label => $msgs) {
            $out[$label] = implode('. ', $msgs);
        }

        return $out;
    }

}
