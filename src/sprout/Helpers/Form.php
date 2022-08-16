<?php
/**
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 *
 * This class was originally from Kohana 2.3.4
 * Copyright 2007-2008 Kohana Team
 */
namespace Sprout\Helpers;

use ReflectionMethod;


/**
 * Helper functions for outputting form elements.
 *
 * Wraps form fields (e.g. from {@see Fb}) with additional HTML.
 *
 * Most wrapping will done using the __callStatic method which actually just calls {@see Form::fieldAuto}.
 * That method uses reflection to look for a custom docblock tag, @wrap-in-fieldset.
 * If that docblock tag is found, the field is wrapped in {@see Form::fieldFieldset}
 * If that docblock tag is not found (the most common case), the field is wrapped in {@see Form::fieldPlain}
 *
 * If the field being wrapped isn't in the Fb helper, the methods fieldAuto, fieldPlain, and fieldFieldset
 * can be invoked directly.
 *
 * The outermost wrapper DIV around the field has a class of "field-element".
 * Additional classes are also added:
 *  - The method and class name, in the format 'field-element--id-<name>'
 *  - If an "id" attribute is set, in the format 'field-element--id-<id>'
 *  - If the field is required, 'field-element--required'
 *  - If the field is disabled, 'field-element--disabled'
 *  - If the field has an error, 'field-element--error'
 *  - One or more custom classes can be specified using the attribute "-wrapper-class".
 *    Each (array or space separated) class is prefixed with 'field-element--'
 *
 * @example
 *    Form::setData($data);
 *    Form::setErrors($errors);
 *
 *    Form::nextFieldDetails('First name', true);
 *    echo Form::text('first_name');
 *
 *    Form::nextFieldDetails('Email', true, 'Please enter your email address');
 *    echo Form::email('email', [], ['-wrapper-class' => 'small']);
 *
 *    Form::nextFieldDetails('Phone', false, 'Enter a phone number using the unique UI');
 *    echo Form::fieldPlain('SproutModules\Someone\CustomModule\Helpers\FbHack::phone', 'phone', [], []);
 *
 * @method static void tag(string $name, array $attrs = [], array $params = [])
 * @method static void input(string $name, string $name, array $params = [])
 * @method static void output(string $name, array $attrs = [])
 * @method static void text(string $name, array $attrs = [])
 * @method static void number(string $name, array $attrs = [])
 * @method static void money(string $name, array $attrs = [], array $options = [])
 * @method static void range(string $name, array $attrs = [])
 * @method static void dualRange($unused, array $attrs = [], array $options = [])
 * @method static void password(string $name, array $attrs = [])
 * @method static void upload(string $name, array $attrs = [])
 * @method static void chunkedUpload(string $name, array $attrs = [], array $options = [])
 * @method static void email(string $name, array $attrs = [])
 * @method static void phone(string $name, array $attrs = [],)
 * @method static void lnk(string $name, array $unused = [], array $options = [])
 * @method static void fileSelector(string $name, array $unused = [], array $options = [])
 * @method static void richtext(string $name, array $unused = [], array $items = [])
 * @method static void multiline(string $name, array $unused = [])
 * @method static void dropdown($name, array $attrs = [], array $options = [])
 * @method static void dropdownItems(array $options, $selected = null)
 * @method static void autocomplete($name, array $attrs = [], array $options = [])
 * @method static void autocompleteList($name, array $attrs = [], array $options = [])
 * @method static void multiradio($name, array $attrs = [], array $options = [])
 * @method static void checkboxBoolList($name, array $attrs = [], array $settings = [])
 * @method static void checkboxSet($name, array $attrs = [], array $settings = [])
 * @method static void checkbox($name, $label, $value, $selected, array $attrs = [])
 * @method static void pageDropdown($name, array $attrs = [], array $options = [])
 * @method static void dropdownTree($name, array $attrs = [], array $options = [])
 * @method static void dropdownTreeItem($node, $depth, $selected, $exclude)
 * @method static void datepicker($name, array $attrs = [], array $options = [])
 * @method static void daterangepicker($name, array $attrs = [], array $options = [])
 * @method static void simpledaterangepicker($name, array $attrs = [], array $options = [])
 * @method static void datetimerangepicker($name, array $attrs = [], array $options = [])
 * @method static void timepicker($name, array $attrs = [], array $params = [])
 * @method static void datetimepicker($name, array $attrs = [], array $options = [])
 * @method static void totalselector($name, array $attrs = [], array $options = [])
 * @method static void colorpicker($name, array $attrs = [], array $params = [])
 * @method static void googleMap($name, array $attrs = [], array $params = [])
 * @method static void conditionsList($name, array $attrs = [], array $params = [])
 * @method static void autoCompleteAddress($name, array $attrs = [], array $options = [])
 * @method static void geocodeAddress($name, array $attrs = [], array $options = [])
 * @method static void randomCode($name, array $attrs = [], array $options = [])
 * @method static void multipleFileSelect($name, array $attrs = [], array $options = [])
 */
class Form
{
    static $errors;
    static $next_label = null;
    static $next_required = null;
    static $next_helptext = null;
    static $name_format = "%s";
    static $id_prefix = '';


    /**
     * Gets the form per-field value for a single field
     *
     * As form field datas are stored using the {@see Fb} class, this method just gets the data from there
     *
     * @param string $field The field name
     */
    public static function getData($field)
    {
        return Fb::getData($field);
    }


    /**
     * Set form per-field values for the fields
     *
     * As form fields are rendered using the {@see Fb} class, this method just sets the data there
     *
     * @param array $data In the format
     *        Key: (string)<field name>
     *        Value: (string)<field value>
     */
    public static function setData(array $data)
    {
        Fb::setData($data);
    }


    /**
     * Sets the value for a single field
     *
     * As form fields are rendered using the {@see Fb} class, this method just sets the data there
     *
     * @param array $field Field name, e.g. 'first_name'
     * @param array $value Field value, e.g. 'John'
     * @return void
     */
    public static function setFieldValue($field, $value)
    {
        Fb::setFieldValue($field, $value);
    }


    /**
     * Set per-field error messages to display
     *
     * A given field can have either a single error message or an array of errors
     * The output from the {@see Validator::getFieldErrors} method can be used directly as input to this method
     *
     * @param array $errors In the format
     *        Key: (string)<field name>
     *        Value: (string | array of string)<errors>
     */
    public static function setErrors(array $errors)
    {
        self::$errors = $errors;
    }


    /**
     * Load data and errors from the session, with optional record id validation
     *
     * Expected session keys:
     *     record_id       Checked against $verify_record_id, session data is thrown away in case of mismatch
     *     field_values    Field data, loaded using {@see Form::setData}
     *     field_errors    Field errors, loaded using {@see Form::setErrors}
     *
     * @example
     *     $data = Form::loadFromSession('register');
     *     if (empty($data)) {
     *         $data = $this->add_defaults;
     *         Form::setData($data);
     *     }
     *
     * @param string $key Session key to get values from
     * @param mixed $verify_record_id For edit record verification
     * @return array Loaded session data
     * @return null No session data found
     */
    public static function loadFromSession($key, $verify_record_id = null)
    {
        Session::instance();

        if (!empty($verify_record_id) and !empty($_SESSION[$key]['record_id'])) {
            if ($_SESSION[$key]['record_id'] != $verify_record_id) {
                unset($_SESSION[$key]);
                return null;
            }
        }

        if (!empty($_SESSION[$key]['field_errors'])) {
            self::setErrors($_SESSION[$key]['field_errors']);
        }

        if (!empty($_SESSION[$key]['field_values'])) {
            self::setData($_SESSION[$key]['field_values']);
            return $_SESSION[$key]['field_values'];
        } else {
            return null;
        }
    }


    /**
     * Set a format string which will alter the field name prior to being passed to the underlying render method
     *
     * Formatting is done using {@see sprintf}
     * A single parameter is provided to the sprintf() call, the field name
     * The default format does no transformation, i.e. the string '%s'
     * This parameter persists across multiple form fields
     *
     * @example
     *     Form::setFieldNameFormat('pages[%s]')
     *     Form::text('name')         // field name will be 'pages[text]'
     *
     * @param string $format Format string
     */
    public static function setFieldNameFormat($format)
    {
        self::$name_format = $format;
    }


    /**
     * Sets the prefix for generated IDs
     *
     * @param string $prefix The prefix
     */
    public static function setFieldIdPrefix($prefix)
    {
        static::$id_prefix = $prefix;
        Fb::$id_prefix = $prefix;
    }


    /**
     * Generate a unique id which should be stable across calls to this URL
     * as long as the number and order of fields on the page remains the same
     *
     * @return string 'field?', where ? is an incrementing number starting at zero
     */
    protected static function genId()
    {
        static $inc = 0;
        return static::$id_prefix . 'field' . $inc++;
    }


    /**
     * Reset the state machine for field values
     */
    public static function resetField()
    {
        self::$next_label = null;
        self::$next_required = null;
        self::$next_helptext = null;
    }


    /**
     * Set the details for the next field which will be outputted.
     *
     * After returning a field, these values will be cleared from the state machine
     *
     * Both the label and helptext support a subset of HTML, {@see Text::limitedSubsetHtml} for more details
     *
     * @param string $label Human label for the field (e.g. 'Email address'). Some HTML allowed
     * @param bool $required True if this field is required, false if it's optional
     * @param string $helptext Optional HTML helptext
     */
    public static function nextFieldDetails($label, $required, $helptext = null)
    {
        self::$next_label = Text::limitedSubsetHtml($label);
        self::$next_required = $required;
        self::$next_helptext = Text::limitedSubsetHtml($helptext);
    }


    /**
     * Convert a full method name (e.g. Sprout\Helpers\Fb::text) into a friendly class name
     *
     * The classes {@see Fb} and {@see Form} aren't emitted, but all other class names are
     *
     * @param string $method Full original method name, in namespace\class::method format
     * @return string HTML-safe name for use in a CSS class
     */
    protected static function fieldMethodClass($method)
    {
        $method = str_replace('Sprout\Helpers\Fb::', '', $method);
        $method = str_replace('Sprout\Helpers\Form::', '', $method);
        $method = str_replace('\\', '-', $method);
        $method = str_replace('::', '--', $method);
        return Enc::id(strtolower($method));
    }


    /**
     * Format a field name as per the specification defined by {@see Form::setFieldNameFormat}
     *
     * @param string $name Unformatted field name
     * @return string Formatted field name
     */
    protected static function convertFieldName($name)
    {
        if (strpos($name, ',') === false) {
            return sprintf(self::$name_format, $name);
        }

        // Handle compound fields (e.g. Fb::googleMap)
        $fields = explode(',', $name);
        foreach ($fields as &$f) {
            $f = sprintf(self::$name_format, $f);
        }
        return implode(',', $fields);
    }


    /**
     * Return the errors for a given field
     *
     * Supports nested error arrays; If $field_name is something like member[5][test] then the error
     * will be read from self::$errors['member']['5']['test']
     *
     * @param string $field_name Field to return errors for
     * @return array Error messages, as strings
     * @return NULL if there aren't any error messages
     */
    public static function getFieldErrors($field_name)
    {
        if (strpos($field_name, '[') === false) {
            $val = @self::$errors[$field_name];
        } else {
            // Get a list of keys
            $keys = explode('[', $field_name);
            foreach ($keys as &$k) {
                $k = rtrim($k, ']');
                if ($k == '') return null;      // Anon keys not supported
            }
            unset($k);

            // Loop through the keys till we get the value we want
            $val = self::$errors;
            foreach ($keys as $k) {
                $val = @$val[$k];
            }
        }

        if (empty($val)) {
            return null;
        } else if (is_array($val)) {
            return $val;
        } else {
            return [$val];
        }
    }


    /**
     * Return HTML for a 'plain' field, i.e. one which doesn't require a FIELDSET wrapped around it.
     *
     * The main wrapping DIV will contain additional classes if the field is required, disabled or has an error.
     * A class is also output for the field method name (if the name contains "Sprout\Helpers\Fb::" this is removed)
     * If the field has an explicit ID set, that will be added as a class on the wrapper too.
     *
     * The special attribute "-wrapper-class" can be used to add classes to the wrapper DIV.
     * Multiple classes can be specified, space separated.
     * These classes will be prefixed with "field-element--"
     *
     * @example
     *    echo Form::fieldPlain('Sprout\Helpers\Fb::text', 'first_name', [], []);
     *
     * @example
     *    // Adds the class "field-element--id-first-name" to the wrapper
     *    echo Form::fieldPlain('Sprout\Helpers\Fb::text', 'first_name', ['id' => 'first-name'], []);
     *
     * @example
     *    // Adds the class "field-element--small" to the wrapper
     *    echo Form::fieldPlain('Sprout\Helpers\Fb::text', 'first_name', ['-wrapper-class' => 'small'], []);
     *
     * @param callable $method The actual field rendering method
     * @param string $name The field name - this is passed to the rendering method
     * @param array $attrs The field attrs - this is passed to the rendering method
     * @param array $options The field options - this is passed to the rendering method
     * @return string HTML
     */
    public static function fieldPlain(callable $method, $name, array $attrs = [], array $options = [])
    {
        $name = self::convertFieldName($name);
        $errs = self::getFieldErrors($name);

        $classes = array('field-element');
        $classes[] = 'field-element--' . self::fieldMethodClass($method);
        if (isset($attrs['id'])) {
            $classes[] = 'field-element--id-' . Enc::id($attrs['id']);
        }
        if (self::$next_required) {
            $classes[] = 'field-element--required';
        }
        if (isset($attrs['disabled']) or in_array('disabled', $attrs, true)) {
            $classes[] = 'field-element--disabled';
        }
        if (!empty($errs)) {
            $classes[] = 'field-element--error';
        }
        if (isset($attrs['-wrapper-class'])) {
            if (is_string($attrs['-wrapper-class'])) {
                $attrs['-wrapper-class'] = preg_split('/\s+/', $attrs['-wrapper-class']);
            }
            foreach ($attrs['-wrapper-class'] as $class) {
                $classes[] = 'field-element--' . $class;
            }
            unset($attrs['-wrapper-class']);
        }
        $classes = implode(' ', $classes);
        $out = '<div class="' . Enc::html($classes) . '">';

        if (!isset($attrs['id'])) {
            $attrs['id'] = self::genId();
        }

        $field_html = call_user_func($method, $name, $attrs, $options);

        // It is invalid to output a LABEL without a corresponding element
        // check if the ID exists in the field
        $has_id_attr = (strpos($field_html, 'id="' . $attrs['id'] . '"') !== false);

        // Label section
        if (self::$next_label) {
            $out .= '<div class="field-label">';
            if ($has_id_attr) {
                $out .= '<label for="' . Enc::html($attrs['id']) . '">';
            }
            $out .= self::$next_label;
            if (self::$next_required) {
                $out .= ' <span class="field-label__required">required</span>';
            }
            if ($has_id_attr) {
                $out .= '</label>';
            }
            if (self::$next_helptext) {
                $out .= '<div class="field-helper">' . self::$next_helptext . '</div>';
            }
            $out .= '</div>';
        }

        // Field itself
        $out .= '<div class="field-input">';
        $out .= $field_html;
        $out .= '</div>';

        // Field errors
        if (!empty($errs)) {
            $out .= '<div class="field-error">';
            $out .= '<ul class="field-error__list">';
            foreach ($errs as $err) {
                $out .= '<li class="field-error__list__item">' . Enc::html($err) . '</li>';
            }
            $out .= '</ul>';
            $out .= '</div>';
        }

        $out .= '</div>';
        $out .= PHP_EOL . PHP_EOL;

        self::resetField();

        return $out;
    }


    /**
     * Return HTML for a field wrapped in a FIELDSET
     *
     * The main wrapping DIV will contain additional classes if the field is required, disabled or has an error.
     * A class is also output for hte field method name (if the name contains "Sprout\Helpers\Fb::" this is removed)
     * If the field has an explicit ID set, that will be added as a class on the wrapper too.
     *
     * The special attribute "-wrapper-class" can be used to add classes to the wrapper DIV.
     * Multiple classes can be specified, space separated.
     * These classes will be prefixed with "field-element--"
     *
     * @param callable $method The actual field rendering method
     * @param string $name The field name - this is passed to the rendering method
     * @param array $attrs The field attrs - this is passed to the rendering method
     * @param array $options The field options - this is passed to the rendering method
     * @return string HTML
     */
    public static function fieldFieldset(callable $method, $name, array $attrs = [], array $options = [])
    {
        $name = self::convertFieldName($name);
        $errs = self::getFieldErrors($name);

        $classes = array('field-element');
        $classes[] = 'field-element--' . self::fieldMethodClass($method);
        if (isset($attrs['id'])) {
            $classes[] = 'field-element--id-' . Enc::id($attrs['id']);
        }
        if (self::$next_required) {
            $classes[] = 'field-element--required';
        }
        if (isset($attrs['disabled']) or in_array('disabled', $attrs, true)) {
            $classes[] = 'field-element--disabled';
        }
        if (!empty($errs)) {
            $classes[] = 'field-element--error';
        }
        if (isset($attrs['-wrapper-class'])) {
            if (is_string($attrs['-wrapper-class'])) {
                $attrs['-wrapper-class'] = preg_split('/\s+/', $attrs['-wrapper-class']);
            }
            foreach ($attrs['-wrapper-class'] as $class) {
                $classes[] = 'field-element--' . $class;
            }
            unset($attrs['-wrapper-class']);
        }
        $classes = implode(' ', $classes);


        if (!isset($attrs['id'])) {
            $attrs['id'] = self::genId();
        }

        $out = '<div class="' . Enc::html($classes) . '">';
        $out .= '<fieldset class="fieldset--' . self::fieldMethodClass($method) . '">';

        // Label section
        if (self::$next_label) {
            $out .= '<legend class="fieldset__legend">';
            $out .= self::$next_label;
            if (self::$next_required) {
                $out .= ' <span class="field-label__required">required</span>';
            }
            $out .= '</legend>';

            if (self::$next_helptext) {
                $out .= '<div class="field-helper">' . self::$next_helptext . '</div>';
            }
        }

        // Field itself
        $out .= '<div class="field-element__input-set">';
        $out .= call_user_func($method, $name, $attrs, $options);
        $out .= '</div>';

        $out .= '</fieldset>';

        // Field errors
        if (!empty($errs)) {
            $out .= '<div class="field-error">';
            $out .= '<ul class="field-error__list">';
            foreach ($errs as $err) {
                $out .= '<li class="field-error__list__item">' . Enc::html($err) . '</li>';
            }
            $out .= '</ul>';
            $out .= '</div>';
        }

        $out .= '</div>';
        $out .= PHP_EOL . PHP_EOL;

        self::resetField();

        return $out;
    }


    /**
     * Return HTML for a field, with the wrapping HTML detected automatically.
     *
     * To enable fieldset wrapping, add the docblock tag @wrap-in-fieldset to the field generation method
     *
     * @param callable $method The actual field rendering method
     * @param string $name The field name - this is passed to the rendering method
     * @param array $attrs The field attrs - this is passed to the rendering method
     * @param array $options The field options - this is passed to the rendering method
     * @return string HTML
     */
    public static function fieldAuto(callable $method, $name, array $attrs = [], array $options = [])
    {
        $use_fieldset = false;

        $func = new ReflectionMethod($method);
        $comment = $func->getDocComment();
        if ($comment and strpos($comment, '@wrap-in-fieldset') !== false) {
            $use_fieldset = true;
        }

        if ($use_fieldset) {
            return static::fieldFieldset($method, $name, $attrs, $options);
        } else {
            return static::fieldPlain($method, $name, $attrs, $options);
        }
    }


    /**
     * Auto-wrapper around Fb methods
     *
     * Will wrap the Fb method with the same name as the called method, e.g. Form::datepicker wraps Fb::datepicker
     * Wrapping is done using {@see Form::fieldAuto}
     *
     * @param string $func Method name
     * @param array $args Method arguments
     * @return string HTML
     */
    public static function __callStatic($func, $args)
    {
        if (!isset($args[1])) $args[1] = [];
        if (!isset($args[2])) $args[2] = [];
        return self::fieldAuto('Sprout\Helpers\Fb::' . $func, $args[0], $args[1], $args[2]);
    }


    /**
     * Auto-wrapper around Fb methods
     *
     * Will wrap the Fb method with the same name as the called method, e.g. Form::datepicker wraps Fb::datepicker
     * Wrapping is done using {@see Form::fieldAuto}
     *
     * @param string $func Method name
     * @param array $args Method arguments
     * @return string HTML
     */
    public function __call($func, $args)
    {
        return self::__callStatic($func, $args);
    }


    /**
     * Returns the first argument
     *
     * This hacky little method works around the fact that fieldPlain only accepts a method name
     *
     * @param string $str
     * @return string
     */
    protected static function passString($str) {
        return $str;
    }


    /**
     * Return HTML which has been wrapped in the form field DIVs
     *
     * @param string $html Content to wrap in the field
     * @return string HTML
     */
    public static function html($html)
    {
        return static::fieldPlain('Sprout\Helpers\Form::passString', $html);
    }


    /**
     * Return content which has been HTML-encoded and wrapped in the form field DIVs
     *
     * @param string $plain Plain text to encode and wrap in the field
     * @return string HTML
     **/
    public static function out($plain)
    {
        return static::fieldPlain('Sprout\Helpers\Form::passString', Enc::html($plain));
    }


    /**
     * Returns HTML for a text field, using {@see Fb::text} to generate the field itself
     *
     * @param string $name The name of the input field
     * @param array $attrs Extra attributes for the input field
     * @return string HTML
     */
    public static function text($name, array $attrs = [])
    {
        return static::fieldPlain('Sprout\Helpers\Fb::text', $name, $attrs);
    }


    /**
     * Returns HTML for a number field, using {@see Fb::number} to generate the field itself
     *
     * @param string $name The name of the input field
     * @param array $attrs Extra attributes for the input field
     * @return string HTML
     */
    public static function number($name, array $attrs = [])
    {
        return static::fieldPlain('Sprout\Helpers\Fb::number', $name, $attrs);
    }


    /**
     * Returns HTML for a money field, using {@see Fb::money} to generate the field itself
     *
     * @param string $name The name of the input field
     * @param array $attrs Extra attributes for the input field
     * @return string HTML
     */
    public static function money($name, array $attrs = [], array $options = [])
    {
        return static::fieldPlain('Sprout\Helpers\Fb::money', $name, $attrs, $options);
    }



    /**
     * Returns HTML for a password field, using {@see Fb::password} to generate the field itself
     *
     * @param string $name The name of the input field
     * @param array $attrs Extra attributes for the input field
     * @return string HTML
     */
    public static function password($name, array $attrs = [])
    {
        return static::fieldPlain('Sprout\Helpers\Fb::password', $name, $attrs);
    }


    /**
     * Returns HTML for a bunch of radiobuttons, using {@see Fb::multiradio} to generate the fields
     *
     * @param string $name The name of the input field
     * @param array $attrs Extra attributes for the input field
     * @return string HTML
     */
    public static function multiradio($name, array $attrs = [], array $options = [])
    {
        return static::fieldFieldset('Sprout\Helpers\Fb::multiradio', $name, $attrs, $options);
    }


    /**
     * Returns HTML for a list of checkboxes, applying name conversions along the way
     *
     * Uses {@see Fb::checkboxBoolList} to generate the underlying checkbox list
     *
     * @param array $checkboxes An array of name => label mappings
     * @param array $attrs Extra attributes applied to each checkbox field
     * @return string HTML
     */
    public static function checkboxList(array $checkboxes, array $attrs = [])
    {
        $prefixed_names = [];

        foreach ($checkboxes as $name => $label) {
            $name = static::convertFieldName($name);
            $prefixed_names[$name] = $label;
        }

        return static::fieldFieldset('Sprout\Helpers\Fb::checkboxBoolList', '', $attrs, $prefixed_names);
    }


    /**
     * Returns HTML for an auto-complete list of records
     *
     * The form data for this field should be an array of arrays with at least the following keys:
     * [
     *     'id' => record ID,
     *     'value' => title text visible in the list item,
     *     'orderkey' => ordinal value for record ordering
     * ]
     *
     * @param string $name Field name
     * @param string $attrs Unused
     * @param array $options Options; these are passed to the JS
     *        lookup_url         string    AJAX lookup URL, {@see Fb::autocomplete}; Required
     *        min_term_length    int       Min term length for autocomplete; default = 3
     *        reorder            bool      Default = false
     * @return string HTML
     */
    public static function autofillList($name, array $attrs = [], array $options = [])
    {
        Needs::fileGroup('sprout/autofill_list');

        if (!isset($options['min_term_length'])) $options['min_term_length'] = 3;
        if (!isset($options['reorder'])) $options['reorder'] = false;
        if (!isset($options['single'])) $options['single'] = 'an item';

        $search_label = "Search for {$options['single']} to add it to the list:";
        $search_field_id = Enc::id("autofill-{$name}-search");

        $opts = [
            'name' => $name,
            'lookup_url' => $options['lookup_url'],
            'min_term_length' => $options['min_term_length'],
            'reorder' => $options['reorder'],
            'single' => $options['single'],
        ];

        $data = Fb::getData($name);
        if (empty($data)) $data = [];
        foreach ($data as &$el) {
            if (!is_array($el)) {
                $el = Enc::html($el);
                continue;
            }
            foreach ($el as &$val) {
                $val = Enc::html($val);
            }
        }

        $out = '<div class="autofill-wrap">';
        $out .= '<div class="autofill-search">';
        $out .= '<div class="autofill-heading"><label for="' . $search_field_id . '">' . Enc::html($search_label) . '</label></div>';
        $out .= self::fieldPlain(
            'Sprout\Helpers\Fb::text',
            $name . '_search',
            ['-wrapper-class' => 'white', 'id' => $search_field_id]
        );
        $out .= '</div>';
        $out .= '<script type="application/json" class="autofill-list-opts">' . json_encode($opts) . '</script>';
        $out .= '<script type="application/json" class="autofill-list-data">' . json_encode($data) . '</script>';
        $out .= '<div class="autofill-list"></div>';

        // Field errors
        $errs = self::getFieldErrors($name);
        if (!empty($errs)) {
            $out .= '<div class="field-error">';
            $out .= '<ul class="field-error__list">';
            foreach ($errs as $err) {
                $out .= '<li class="field-error__list__item">' . Enc::html($err) . '</li>';
            }
            $out .= '</ul>';
            $out .= '</div>';
        }

        $out .= '</div>';
        $out .= PHP_EOL . PHP_EOL;

        return $out;
    }
}
