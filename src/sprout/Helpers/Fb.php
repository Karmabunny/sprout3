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

use Exception;
use InvalidArgumentException;

use Sprout\Exceptions\RowMissingException;
use Sprout\Helpers\Locales\LocaleInfo;


/**
* Quick and easy form builder
**/
class Fb
{
    /** ID for use on the field label and input/select/etc. element */
    public static $field_id = '';

    /** Extra class(es) for use on div.field-wrapper, div.label, and div.field to support additional styling.
        N.B. If this is set, all inputs will be wrapper in a div.field */
    public static $include_class = '';

    public static $data = [];
    public static $scope = 'admin';
    public static $dropdown_top = 'Select an option';

    /**
     * @var string A prefix for generated IDs
     */
    public static $id_prefix = '';


    /**
     * Sets the data that is used for form-building
     * This is typically from a database row or saved session data
     * To set a single field, instead use {@see Fb::setFieldValue}
     *
     * @param array $data Field name => value pairs
     * @return void
     */
    public static function setData($data)
    {
        self::$data = $data;
    }

    /**
     * Sets the value for a single field
     * This is the non-array version of {@see Fb::setData}
     *
     * @param array $field Field name, e.g. 'first_name'
     * @param array $value Field value, e.g. 'John'
     * @return void
     */
    public static function setFieldValue($field, $value)
    {
        self::$data[$field] = $value;
    }


    /**
     * Sets the text for the top item of dropdown lists.
     * Set to an empty string to not show the top item.
     * This will be reset to the default value after every call to {@see Fb::dropdown}
     * @deprecated Set the special attribute "-dropdown-top" when calling the dropdowns
     * @param string $label The data to put in the first OPTION, with its value being an empty string
     * @return void
     */
    public static function setDropdownTop($label)
    {
        self::$dropdown_top = $label;
    }


    /**
     * Gets the data for a single field from the data array
     *
     * Properly handles PHP sub-arrays (e.g. 'options[food]'), doesn't handle
     * anon arrays though (e.g. 'options[]').
     * @param string $name The field name
     * @return mixed The value (often a string)
     */
    public static function getData($name)
    {
        if (strpos($name, '[') === false) {
            return @self::$data[$name];
        }

        // Get a list of keys
        $keys = explode('[', $name);
        foreach ($keys as &$key) {
            $key = trim($key, ']');
            if ($key == '') return '';        // anon arrays aren't supported
        }

        // Loop through the keys till we get the value we want
        $v = self::$data;
        foreach ($keys as $k) {
            $v = @$v[$k];
        }

        return $v;
    }


    /**
     * Generates a heading using a H3 tag
     * @param string $heading
     * @return string H3 element
     */
    public static function heading($heading)
    {
        return '<h3>' . Enc::html($heading) . '</h3>';
    }


    /**
     * Generate a unique id
     * @return string 'fb?', where ? is an incrementing number starting at zero
     */
    private static function genId()
    {
        static $inc = 0;

        return static::$id_prefix . 'fb' . $inc++;
    }


    /**
     * Injects the current auto-generated id into an array of attributes.
     * Only applies if an auto-generated id exists and an id isn't already set in the attributes.
     * The auto-generated id is then cleared.
     * @param array $attrs The attributes
     * @return void
     */
    protected static function injectId(array &$attrs)
    {
        if (isset($attrs['id'])) return;
        if (!self::$field_id) return;
        $attrs['id'] = self::$field_id;
        self::$field_id = '';
    }


    /**
     * Adds an HTML attribute to the list of attributes.
     * If the attribute has already been set, it will be left alone.
     * N.B. the 'class' attribute is always appended
     * @param array $attrs The list of attributes to modify
     * @param string $name The name of the attribute, e.g. 'style'
     * @param string $value The value of the attribute, e.g. 'display: none;'
     * @return void
     */
    protected static function addAttr(array &$attrs, $name, $value)
    {
        if (isset($attrs[$name])) {
            if ($name == 'class') {
                if ($attrs['class'] and $value != '') $attrs['class'] .= ' ';
                $attrs['class'] .= $value;
            }
            return;
        }
        $attrs[$name] = $value;
    }


    /**
     * Generates an HTML opening tag, and possibly its closing tag, depending on the params specified
     *
     * You can specify either HTML or plain-text content, but not both
     *
     * @param string $name The name of the tag, e.g. 'textarea'
     * @param array $attrs Attributes for the tag, e.g. ['class' => 'super-input', 'style' => 'font-style: italic']
     * @param string $params Additional options, as follows:
     *        - 'html' (string): Specifies HTML content between the opening and closing tags, which MUST be
     *          properly encoded.
     *        - 'plain' (string): Specifies non-encoded content content between the opening and closing tags.
     * @return string The generated tag
     */
    public static function tag($name, array $attrs = [], array $params = [])
    {
        $tag = '<' . Enc::html($name);
        foreach ($attrs as $attr => $val) {
            // Support boolean attributes
            if (is_int($attr)) $attr = $val;

            $tag .= ' ' . Enc::html($attr) . '="' . Enc::html($val) . '"';
        }
        $tag .= '>';

        $close = false;
        if (array_key_exists('html', $params)) {
            $tag .= $params['html'];
            $close = true;
        } elseif (array_key_exists('plain', $params)) {
            $tag .= Enc::html($params['plain']);
            $close = true;
        }

        if ($close) {
            $tag .= '</' . Enc::html($name) . '>';
        }

        return $tag;
    }


    /**
     * Generates an HTML INPUT tag using {@see Fb::tag}, with auto-determined value
     *
     * @param string $type The type of input, e.g. 'text', 'hidden', ...
     * @param string $name The name of the input
     * @param array $attrs Attributes for the tag
     * @return string INPUT element
     * @throws InvalidArgumentException if $name is empty
     */
    public static function input($type, $name, array $attrs = [])
    {
        if (empty($name)) {
            throw new InvalidArgumentException('An INPUT without a name is invalid');
        }

        $attrs['type'] = $type;
        $attrs['name'] = $name;
        if ($type != 'file') {
            $attrs['value'] = self::getData($name);
        }
        return self::tag('input', $attrs);
    }


    /**
     * Outputs the value of the field directly, in a span
     * @param array $attrs Extra attributes for the input field
     * @return string SPAN element
     */
    public static function output($name, array $attrs = [])
    {
        $value = self::getData($name);
        self::addAttr($attrs, 'class', 'field-output');
        return self::tag('span', $attrs, ['plain' => $value]);
    }


    /**
     * Generates a text field
     * @todo Use a generic method to generate the INPUT tag and its attributes
     * @param string $name The name of the input field
     * @param array $attrs Extra attributes for the input field
     * @return string INPUT element
     */
    public static function text($name, array $attrs = [])
    {
        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox');
        return self::input('text', $name, $attrs);
    }


    /**
     * Shows a HTML5 number field
     *
     * @param string $name The name of the input field
     * @param array $attrs Extra attributes for the input field; 'min' and 'max' being particularly relevant
     * @return string INPUT element
     */
    public static function number($name, array $attrs = [])
    {
        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox');
        return self::input('number', $name, $attrs);
    }


    /**
     * Shows a HTML5 number field, formatted for dollar prices
     *
     * @param string $name The name of the input field
     * @param array $attrs Extra attributes for the input field; 'min' and 'max' being particularly relevant
     * @return string INPUT element
     */
    public static function money($name, array $attrs = [], array $options = [])
    {
        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox');

        if(!array_key_exists('min', $attrs)){
            $attrs["min"] = "0";
        }
        if(!array_key_exists('step', $attrs)){
            $attrs["step"] = "0.01";
        }

        if (isset($options['locale'])) {
            $locale = LocaleInfo::get($options['locale']);
        } else {
            $locale = LocaleInfo::auto();
        }

        $out = '<div class="money-symbol money-symbol--' . Enc::id(strtolower($locale->getCurrencyName())) . '">';
        $out .= self::input('number', $name, $attrs);
        $out .= '</div>';

        return $out;
    }


    /**
     * Generates a HTML5 range field
     * @param string $name Field name
     * @param array $attrs Extra attributes for the INPUT element. A range element takes these attributes:
     *        'min' The minimum value, default 0, set to NULL for no limit
     *        'max' The maximum value, default 100, set to NULL for no limit
     *        'step' Difference between each grade on the range
     * @param array $options Ignored
     * @return string HTML with elements including INPUT and SCRIPT
     */
    public static function range($name, array $attrs = [], array $options = [])
    {
        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox');
        if (!isset($attrs['min'])) $attrs['min'] = 0;
        if (!isset($attrs['max'])) $attrs['max'] = 100;

        if ($attrs['min'] === null) unset($attrs['min']);
        if ($attrs['max'] === null) unset($attrs['max']);
        if (empty($attrs['step'])) unset($attrs['step']);

        $out = self::input('range', $name, $attrs);

        $id = Enc::id($attrs['id']);
        $value = (float) Fb::getData($name);
        $div = "<div id=\"{$id}-count\">{$value}</div>";
        $out .= "<script type=\"text/javascript\">
        $(document).ready(function() {
            $(\"#{$id}\").after('{$div}');
            $(\"#{$id}-count\").text($(\"#{$id}\").val());
            $(\"#{$id}\").bind('change click', function() {
                $(\"#{$id}-count\").text($(this).val());
            });
        });
        </script>";

        return $out;
    }


    /**
     * Generates a password field
     * @param string $name The name of the input field
     * @param array $attrs Extra attributes for the input field
     * @return string INPUT element
     */
    public static function password($name, $attrs = [])
    {
        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox password');
        return self::input('password', $name, $attrs);
    }


    /**
     * Generates a file upload field
     * @param string $name The name of the input field
     * @param array $attrs Extra attributes for the input field
     * @return string INPUT element
     */
    public static function upload($name, array $attrs = [])
    {
        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'upload');
        return self::input('file', $name, $attrs);
    }


    /**
     * Generates a file upload field with a progress bar
     *
     * To easily save the uploaded files in the form action function, see {@see File::replaceSet}
     *
     * @param string $name
     * @param array $attrs
     * @param array $params Must have 'sess_key' => session key, e.g. 'user-register'.
     *        Data regarding each uploaded file will typically be saved in
     *        $_SESSION['file_uploads'][$params['sess_key']][$name].
     *
     *        May also have 'opts' which can contain any of the following:
     *        - 'begin_url' (string)
     *        - 'form_url' (string)
     *        - 'done_url' (string)
     *        - 'cancel_url' (string)
     *        - 'form_params' (array):
     *            - form_id (string)
     *            - field_name (string)
     *
     *        May also specify 'multiple', which is a positive int (default: 1).
     *        If more than 1, multiple files are allowed, up to the number specified.
     */
    public static function chunkedUpload($name, array $attrs = [], array $params)
    {
        Needs::fileGroup('fb');
        Needs::fileGroup('drag_drop_upload');

        self::injectId($attrs);

        $max_files = (int) @$params['multiple'];
        if ($max_files < 1) $max_files = 1;

        $default_opts = [
            'begin_url' => 'file_upload/upload_begin',
            'form_url' => 'file_upload/upload_form',
            'chunk_url' => 'file_upload/upload_chunk',
            'done_url' => 'file_upload/upload_done',
            'cancel_url' => 'file_upload/upload_cancel',
            'form_params' => [
                'form_id' => $params['sess_key'],
                'field_name' => $name,
            ],
            'max_files' => $max_files,
        ];

        // Override default opts with any specified via $params
        if (!empty($params['opts'])) {
            $opts = $params['opts'];
            foreach ($default_opts as $key => $val) {
                if (empty($opts[$key])) {
                    $opts[$key] = $default_opts[$key];
                }
            }
        } else {
            $opts = $default_opts;
        }

        $out = '<div class="fb-chunked-upload" data-opts="' . Enc::html(json_encode($opts)) . '">';

        $upload_params = ['class' => 'file-upload__input', 'id' => $attrs['id']];
        if ($opts['max_files'] > 1) $upload_params['multiple'] = 'multiple';
        $out .= self::upload($name . '_upload', $upload_params);

        $out .= '<div class="file-upload__area textbox">';

        $files = ($opts['max_files'] == 1 ? 'file' : 'files');
        $out .= '<div class="file-upload__helptext">';
        $out .= "<p>Drop {$files} here ";
        $out .= '<span class="file-upload__helptext__line2">or click to upload</span></p>';
        $out .= '</div>';


        $out .= '<div class="file-upload__uploads">';

        // Show uploaded file(s) if there's uploaded file data in the session
        // Otherwise it just gets thrown away if there's a form error
        $friendly_vals = self::getData($name);
        $temp_vals = self::getData($name . '_temp');

        $files = [];
        if (is_array($friendly_vals) and is_array($temp_vals)) {
            reset($friendly_vals);
            reset($temp_vals);

            while (list($junk, $friendly) = each($friendly_vals) and list($junk, $temp) = each($temp_vals)) {
                $temp = preg_replace('/[^a-z0-9_\-\.]/i', '', $temp);
                if (!$friendly or !$temp) continue;

                $temp_path = APPPATH . 'temp/' . $temp;
                if (!file_exists($temp_path)) continue;

                $temp_parts = explode('-', $temp, 3);
                $files[] = [
                    'original' => $friendly,
                    'temp' => $temp,
                    'code' => preg_replace('/\.dat$/', '', $temp_parts[2]),
                ];
            }
        }

        if (is_string($friendly_vals)) {
            $files[] = $friendly_vals;
            $out .= '<input class="js-delete-notify" type="hidden" name="' . Enc::html($name) . '_deleted">';
        }
        foreach ($files as $file) {
            // Temp uploaded files stored in session
            if (is_array($file)) {
                $temp_path = APPPATH . 'temp/' . $file['temp'];
                $view = new View('sprout/file_confirm');
                $view->orig_file = ['name' => $file['original'], 'size' => filesize($temp_path)];
                $type = File::getType($file['original']);

            // Existing file stored on disk
            } else if ($file) {
                $temp_path = DOCROOT . 'files/' . $file;
                $view = new View('sprout/file_confirm');
                $view->orig_file = ['name' => 'Existing file', 'size' => filesize($temp_path)];
                $type = File::getType($temp_path);
            } else {
                continue;
            }

            if ($type == FileConstants::TYPE_IMAGE) {
                try {
                    $view->shrunk_img = File::base64Thumb($temp_path, 200, 200);
                } catch (Exception $ex) {
                    $view->image_too_large = true;
                }
            }

            $out .= '<div class="file-upload__item"';
            if (!empty($file['code'])) $out .= ' data-code="' . Enc::html($file['code']) . '"';
            $out .= '>';
            $out .= $view->render();
            $out .= '</div>';
        }

        // Don't try and save an existing file which is already on disk
        if (is_string($friendly_vals)) {
            $files = [];
        }

        $out .= '</div>'; // .file-upload__uploads
        $out .= '</div>'; // .file-upload__area

        $out .= '<div class="file-upload__data">';
        foreach ($files as $file) {
            $out .= '<input type="hidden" name="' . Enc::html($name) . '[]" class="original" value="' . Enc::html($file['original']) . '" data-code="' . Enc::html($file['code']) . '">';
            $out .= '<input type="hidden" name="' . Enc::html($name) . '_temp[]" class="temp" value="' . Enc::html($file['temp']) . '" data-code="' . Enc::html($file['code']) . '">';
        }
        $out .= '</div>'; // .file-upload__data

        $out .= '</div>'; // .fb-chunked-upload
        return $out;
    }


    /**
     * Renders a HTML5 email field
     * @param string $name Field name
     * @param array $attrs Extra attributes for the INPUT element
     * @param array $options Ignored
     * @return string
     */
    public static function email($name, array $attrs = [], array $options = [])
    {
        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox email');

        return self::input('email', $name, $attrs);
    }

    /**
     * Renders a HTML5 phone number field (type=tel)
     * @param string $name Field name
     * @param array $attrs Extra attributes for the INPUT element
     * @param array $params Ignored
     * @return string INPUT element
     */
    public static function phone($name, array $attrs = [], array $params = [])
    {
        self::injectId($attrs);
        Fb::addAttr($attrs, 'class', 'textbox phone');

        return self::input('tel', $name, $attrs);
    }

    /**
     * Render the UI for our multi-type link fields
     *
     * @wrap-in-fieldset
     * @param string $name The name of the field
     * @param array $attrs Unused
     * @param array $options Includes the following:
     *        'required': (bool) true if the field is required
     * @return string HTML
     */
    public static function lnk($name, array $attrs = [], array $options = [])
    {
        Needs::module('fb');

        $value = self::getData($name);
        return Lnk::editform($name, $value, !empty($options['required']));
    }


    /**
     * A file selection field, for use in the admin only.
     *
     * @param string $name Field name
     * @param array $attrs Unused.
     * @param array $options Includes the following:
     *        'filter': (int) One of the filters, e.g. {@see FileConstants}::TYPE_IMAGE
     *        'required': (bool) true if the field is required
     * @return string HTML
     */
    public static function fileSelector($name, array $attrs = [], array $options = [])
    {
        Needs::module('fb');

        $value = self::getData($name);

        $options['filter'] = (int) @$options['filter'];
        $options['required'] = (bool) @$options['required'];

        $classes = ['fb-file-selector', 'fs', '-clearfix'];
        if ($value) {
            $classes[] = 'fs-file-selected';
        }
        $classes = implode(' ', $classes);

        $filename = '';
        if (preg_match('/^[0-9]+$/', $value)) {
            try {
                $filename = Pdb::q("SELECT filename FROM ~files WHERE id = ?", [$value], 'val');
            } catch (RowMissingException $ex) {
            }
        }

        $out = '<span class="' . Enc::html($classes) . '" data-filter="' . $options['filter'] . '"';
        $out .= ' data-init="false" data-filename="' . Enc::html($filename) . '">';
        $out .= '<button type="button" class="fs-select-button button button-blue popup-button icon-after icon-insert_drive_file">Select a file</button>';
        $out .= '<input class="fs-hidden" type="hidden" name="' . Enc::html($name) . '" value="' . Enc::html($value) . '">';

        $out .= '<span class="fs-preview-wrapper">';

        if ($options['filter'] == FileConstants::TYPE_IMAGE or strpos(File::mimetype($value), 'image/') === 0) {
            $out .= '<span class="fs-preview">';
            if ($value) {
                $out .= '<img src="' . Enc::html(File::resizeUrl($value, 'c50x50')) . '" alt="">';
            }
            $out .= '</span>';
        }

        $out .= '<span class="fs-filename">';
        $out .= ($value ? Enc::html($value) : 'No file selected');
        $out .= '</span>';

        if (!$options['required']) {
            $out .= '<button class="fs-remove" type="button"><span class="-vis-hidden">Remove</span></button>';
        }

        $out .= '</span>';      // preview wrapper
        $out .= '</span>';      // outer wrap

        return $out;
    }


    /**
     * Generates a richtext field - i.e. TinyMCE
     *
     * @wrap-in-fieldset
     * @param string $name The field name for this richtext field.
     * @param array $attrs Including 'height' and 'width' in pixels
     * @param array $items Specify 'type' for RichText, e.g. 'TinyMCE4', or 'TinyMCE4:Lite'
     * @return string HTML containing a TEXTAREA element and an associated SCRIPT element which to converts it
     *         into a richtext field
     */
    public static function richtext($name, array $attrs = [], array $items = [])
    {
        $value = self::getData($name);

        $width = (int) @$attrs['width'];
        if ($width <= 0) $width = 600;

        $height = (int) @$attrs['height'];
        if ($height <= 0) $height = 400;

        return RichText::draw($name, $value, $width, $height, @$items['type']);
    }

    /**
     * Generates a multiline text field
     * @param string $name The field name for this field.
     * @param array $attrs Extra attributes for the TEXTAREA element
     * @return string TEXTAREA element
     */
    public static function multiline($name, array $attrs = [])
    {
        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox multiline');
        $attrs['name'] = $name;
        return self::tag('textarea', $attrs, ['plain' => self::getData($name)]);
    }


    /**
     * Generates a dropdown selection menu
     * @todo Use a generic method to generate the SELECT tag and its attributes
     * @param string $name The field name
     * @param array $attrs Extra attributes for the SELECT element
     *    The special attribute "-dropdown-top" sets the label for the top item
     *    Use an empty string for no top item
     * @param array $options Data for the OPTION elements in value-label pairs,
     *        e.g. [0 => 'Inactive', 1 => 'Active']
     * @return string
     */
    public static function dropdown($name, array $attrs = [], array $options = [])
    {
        if (isset($attrs['-dropdown-top'])) {
            self::$dropdown_top = $attrs['-dropdown-top'];
            unset($attrs['-dropdown-top']);
        }

        $is_multi = false;
        foreach ($attrs as $key => $val) {
            if (strcasecmp($key, 'multiple') == 0) {
                $is_multi = true;
                break;
            }
            if (is_int($key) and strcasecmp($val, 'multiple') == 0) {
                $is_multi = true;
                break;
            }
        }

        self::injectId($attrs);
        $value = self::getData($name);
        $extra = self::addAttr($attrs, 'class', 'dropdown');

        if ($is_multi and substr($name, -2) != '[]') {
            $name .= '[]';
        }

        $attrs['name'] = $name;
        $field = self::tag('select', $attrs);

        if (self::$dropdown_top and !$is_multi) {
            $field .= '<option value="" class="dropdown-top">';
            $field .= Enc::html(self::$dropdown_top) . '</option>';
        }

        $field .= self::dropdownItems($options, $value);

        $field .= '</select> ';

        // Revert to default top dropdown item
        self::$dropdown_top = 'Select an option';

        return $field;
    }


    /**
     * Returns HTML for a list of OPTIONs, and depending on the input array, OPTGROUP tags.
     * @param array $options The options. Any element that is an array will become an optgroup, with its inner elements
     *        becoming options.
     * @param string|array $selected The value of the selected option
     * @return string
     */
    public static function dropdownItems(array $options, $selected = null)
    {
        $out = '';
        foreach ($options as $val => $label) {
            $val_enc = Enc::html($val);

            if (is_array($label)) {
                $out .= "<optgroup label=\"{$val_enc}\">";
                $out .= self::dropdownItems($label, $selected);
                $out .= "</optgroup>";

            } else {
                $label = Enc::html($label);
                $label = str_replace('     ', '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', $label);

                if ($val == $selected) {
                    $out .= "<option value=\"{$val_enc}\" selected>{$label}</option>";
                } else if (is_array($selected) and in_array($val, $selected)) {
                    $out .= "<option value=\"{$val_enc}\" selected>{$label}</option>";
                } else {
                    $out .= "<option value=\"{$val_enc}\">{$label}</option>";
                }
            }
        }
        return $out;
    }


    /**
     * Returns HTML for an autocomplete selection menu.
     * Expects to be provided AJAX data in a format defined by jQuery UI (see 'url' option below).
     * By default, this expects to stores an ID value for a foreign key. If this isn't the desired
     * behaviour, a plain text value can be stored by setting the 'save_id' option to false.
     *
     * The URL which handles the lookups needs to use the 'term' GET param to find matching values.
     * If 'save_id' is true, then it also needs to accept the 'id' GET param to fetch the label for the relevant id.
     * A call to the URL should return JSON which contains an array of hashes, each with the following keys:
     * - 'value': data for the text input.
     * - 'label': data for display in the drop-down list (if different from value).
     *            This is used as the value if 'value' isn't specified.
     * - 'id': id value to save. This should only be specified if 'save_id' is true; see below.
     *
     * @param string $name The field name
     * @param array $attrs Extra attributes for the INPUT element
     * @param array $options Keys as follows:
     *        'url' (string, required) URL to access when fetching matches via AJAX.
     *        'save_id' (bool, defaults to true) Save the data as an ID value or similar unique key, and look up the
     *            label for display upon page by calling the URL with the 'id' GET param set
     *        'multiline' (bool, defaults to false) Use a textarea to support multiline text
     *        'chars' (int, defaults to 2) Minimum number of characters required before first AJAX lookup fires.
     *            If zero, the lookup will happen on focus.
     * @return string An INPUT element and associated SCRIPT element
     */
    public static function autocomplete($name, array $attrs = [], array $options = [])
    {
        Needs::module('fb');

        if (empty($options['url'])) {
            throw new InvalidArgumentException("\$options['url'] must be specified");
        }
        if (!isset($options['chars']) or !is_numeric($options['chars'])) {
            $chars = 2;
        } else {
            $chars = (int) $options['chars'];
            if ($chars < 0) $chars = 0;
        }

        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox');
        self::addAttr($attrs, 'class', 'autocomplete');
        self::addAttr($attrs, 'data-lookup-url', $options['url']);
        self::addAttr($attrs, 'data-chars', $chars);

        // Automatically add a hidden field with the 'id' value if available. This is probably the most
        // desirable behaviour, as an autocomplete field is usually for a Foreign Key column
        if (!array_key_exists('save_id', $options)) $options['save_id'] = true;
        $attrs['data-save-id'] = (int) (bool) $options['save_id'];

        if (!empty($options['multiline'])) {
            $attrs['name'] = $name;
            $input = self::tag('textarea', $attrs, ['plain' => self::getData($name)]);
        } else {
            $input = self::input('text', $name, $attrs);
        }

        return '<div class="autocomplete-symbol">' . $input . '</div>';
    }


    /**
     * Returns HTML for a bunch of radiobuttons
     *
     * @wrap-in-fieldset
     * @param string $name The field name
     * @param array $attrs Attributes for all input elements, e.g. ['class' => 'super-input', 'style' => 'font-style: italic']
     * @param array $options each is a value => label mapping, e.g. ['A' => 'Apple crumble', 'B' => 'Banana split']
     * @return string HTML containing DIV tags containing INPUT and LABEL tags.
     */
    public static function multiradio($name, array $attrs = [], array $options = [])
    {
        $value = self::getData($name);

        $content = '';
        foreach ($options as $opt_value => $label) {
            $id = self::genId();

            $content .= '<div class="fieldset-input">';
            $input_attrs = [
                'type' => 'radio',
                'id' => $id,
                'name' => $name,
                'value' => $opt_value,
            ];
            if ($opt_value == $value) $input_attrs['checked'] = 'checked';

            $tag_attrs = array_merge($attrs, $input_attrs);
            $content .= self::tag('input', $tag_attrs);

            $content .= "<label for=\"{$id}\">";
            $content .= Enc::html($label);
            $content .= "</label>";
            $content .= "</div>";
        }

        return $content;
    }


    /**
     * Returns HTML containing multiple boolean checkboxes
     *
     * @wrap-in-fieldset
     * @param string $name Ignored; each checkbox specifies its own name in $settings
     * @param array $attrs Unused but remains for compatibility with other methods
     * @param array $settings Keys are the names of the checkbox fields, and values their labels.
     * @return string HTML containing DIV tags containing INPUT and LABEL tags.
     */
    public static function checkboxBoolList($name, array $attrs = [], array $settings = [])
    {
        $out = '';

        foreach ($settings as $name => $label) {
            $selected = !empty(self::getData($name));
            $out .= self::checkbox($name, $label, 1, $selected, $attrs);
        }

        return $out;
    }


    /**
     * Returns HTML containing multiple checkboxes, with values to store in a SET column or similar
     *
     * @wrap-in-fieldset
     * @param string $name Name for each INPUT element. Empty brackets will be appended if not supplied, i.e. [].
     * @param array $attrs Attributes for all input elements, e.g. ['class' => 'super-input', 'style' => 'font-style: italic']
     * @param array $settings Keys are the values available in the set, and values their labels. These can match, and
     *        can be easily filled by a call to {@see Pdb::extractEnumArr}
     * @return string HTML containing DIV tags containing INPUT and LABEL tags.
     *
     * @example
     * Form::nextFieldDetails('Colours to include', false);
     * echo Form::checkboxSet('colours', [], [
     *     'red' => 'Red, the colour of cherries and strawberries',
     *     'green' => 'Green, the colour of leaves',
     *     'blue' => 'Blue, the colour of the sky and ocean',
     * ]);
     */
    public static function checkboxSet($name, array $attrs = [], array $settings = [])
    {
        $out = '';

        $selected = self::getData($name);
        if (!is_array($selected)) $selected = preg_split('/,\s*/', trim($selected));

        if (substr($name, -2) != '[]') $name .= '[]';
        $id = Enc::id($name);
        $name = Enc::html($name);

        foreach ($settings as $value => $label) {
            $is_selected = in_array($value, $selected);
            $out .= static::checkbox($name, $label, $value, $is_selected, $attrs);
        }

        return $out;
    }

    /**
     * Returns the HTML for a single checkbox
     *
     * @note This typically isn't used directly; instead use @see Form::checkboxList,
     *       @see Fb::checkboxBoolList, @see Fb::checkboxSet
     * @param string $name The name of the checkbox
     * @param string $label The label for the checkbox; supports minimal HTML, {@see Text::limitedSubsetHtml}
     * @param int|string $value The checkbox's value
     * @param bool $selected Whether or not the checkbox is ticked
     * @param array $attrs Extra attributes attached to the input tag
     */
    protected static function checkbox($name, $label, $value, $selected, array $attrs = [])
    {
        $out = '';

        $out .= '<div class="fieldset-input">';
        $input_attrs = [
            'type' => 'checkbox',
            'id' => self::genId(),
            'name' => $name,
            'value' => $value,
        ];

        if ($selected) {
            $input_attrs['checked'] = 'checked';
        }

        $tag_attrs = array_merge($attrs, $input_attrs);

        $out .= self::tag('input', $tag_attrs);
        $out .= self::tag('label', ['for' => $input_attrs['id']]);
        $out .= Text::limitedSubsetHtml($label);
        $out .= '</label>';
        $out .= '</div>';

        return $out;
    }

    /**
     * Generates a page dropdown selection menu
     *
     * Just a wrapper for {@see Fb::dropdownTree}
     *
     * @param string $name The field name
     * @param array $attrs Extra attributes for the SELECT element
     * @param array $options Zero or more field options;
     *    'subsite'  int       Subsite ID to load; default is current subsite
     *    'exclude'  array     Node IDs to exclude from rendering
     * @return string HTML
     */
    public static function pageDropdown($name, array $attrs = [], array $options = [])
    {
        if (empty($options['subsite'])) {
            $options['subsite'] = $_SESSION['admin']['active_subsite'];
        }
        $options['root'] = Navigation::loadPageTree($options['subsite'], true, false);
        return self::dropdownTree($name, $attrs, $options);
    }


    /**
     * Generates a dropdown selection menu from a Treenode and its children
     *
     * @param string $name The field name
     * @param array $attrs Extra attributes for the SELECT element
     *    The special attribute "-dropdown-top" sets the label for the top item
     *    Use an empty string for no top item
     * @param array $options One or more field options;
     *    'root'     Treenode  Tree root - Required
     *    'exclude'  array     Node IDs to exclude from rendering
     * @return string HTML
     */
    public static function dropdownTree($name, array $attrs = [], array $options = [])
    {
        if (isset($attrs['-dropdown-top'])) {
            self::$dropdown_top = $attrs['-dropdown-top'];
            unset($attrs['-dropdown-top']);
        }

        $value = self::getData($name);

        if (empty($options['root']) or !($options['root'] instanceof Treenode)) {
            throw new InvalidArgumentException('Option "root" is required and must be a Treenode');
        }
        if (empty($options['exclude'])) {
            $options['exclude'] = [];
        }

        $attrs['name'] = $name;
        $field = self::tag('select', $attrs);

        if (self::$dropdown_top) {
            $field .= '<option value="" class="dropdown-top">';
            $field .= Enc::html(self::$dropdown_top) . '</option>';
        }

        foreach ($options['root']->children as $child) {
            $field .= self::dropdownTreeItem($child, 0, $value, $options['exclude']);
        }

        $field .= '</select>';

        // Revert to default top dropdown item
        self::$dropdown_top = 'Select an option';

        return $field;
    }


    /**
     * Used internally for recursive dropdown generation.
     *
     * @param Pagenode $node The node to display
     * @param int $depth The depth of the node
     * @param int $selected The id of the page to select
     * @param array $exclude Node IDs of the to exclude from the list
     * @return string HTML
     */
    private static function dropdownTreeItem($node, $depth, $selected, $exclude)
    {
        $space = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $depth);
        $name = Enc::html($node['name']);

        if (in_array($node['id'], $exclude)) return '';

        if ($node['id'] == $selected) {
            $out = "<option value=\"{$node['id']}\" selected>{$space}{$name}</option>";
        } else {
            $out = "<option value=\"{$node['id']}\">{$space}{$name}</option>";
        }

        foreach ($node->children as $child) {
            $out .= self::dropdownTreeItem($child, $depth + 1, $selected, $exclude);
        }

        return $out;
    }


    /**
     * Renders HTML containing a date selection UI. Output field value is in YYYY-MM-DD
     *
     * @todo Mobile to use a "date" field instead, for native UI
     * @throws ValidationException If 'min', 'max' or 'incr' options are invalid
     * @param string $name The field name
     * @param array $attrs Attributes for the input element, e.g. ['id' => 'my-timepicker', class' => 'super-input', 'style' => 'font-style: italic']
     * @param array $options Customisation options
     *      'min' => the earliest selectable date, format YYYY-MM-DD
     *      'max' => the latest selectable date, format YYYY-MM-DD
     *      'dropdowns' => bool, true to include dropdowns for the month and year
     * @return string HTML
     */
    public static function datepicker($name, array $attrs = [], array $options = [])
    {
        Needs::module('moment');
        Needs::module('daterangepicker');
        Needs::module('fb');

        $value = self::getData($name);
        if ($value == '0000-00-00') $value = '';

        if (isset($options['min'])) Validity::dateMySQL($options['min']);
        if (isset($options['max'])) Validity::dateMySQL($options['max']);
        if (!isset($attrs['autocomplete'])) $attrs['autocomplete'] = 'off';

        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox fb-datepicker');
        self::addAttr($attrs, 'type', 'text');

        foreach ($options as $key => $val) {
            $attrs['data-' . $key] = $val;
        }
        $out = '<div class="field-clearable__wrap">';
        $out .= self::tag('input', [
            'name' => $name, 'value' => $value, 'type' => 'hidden', 'class' => 'fb-hidden'
        ]);
        $out .= self::tag('input', $attrs);
        $out .= self::tag('button', [
            'type' => 'button',
            'class' => 'field-clearable__clear fb-clear',
        ]);
        $out .= '</div>';

        return $out;
    }


    /**
     * Renders a date range picker. Output is in the form of two fields (given in name as a comma separated list) e.g.
     * name => date_start, date_end will result in two fields: date_start => YYYY-MM-DD, date_end => YYYY-MM-DD
     *
     * @example
     *     echo Fb::daterangepicker('date_from,date_to', [], ['min' => '2000-01-01']);
     *
     * @param string $name The field name prefix
     * @param array $attrs Attributes for the input element, e.g. ['class' => 'super-input', 'style' => 'font-style: italic']
     * @param array $options Customisation options
     *      'min' => the minimum of this date range.
     *      'max' => the maximum of this date range.
     *      'dropdowns' => display the dropdown date selectors.
     *      'dir' => Either "future" or "past", for the direction of the pre-configured ranges. Default "future"
     * @return string The rendered HTML
     */
    public static function daterangepicker($name, array $attrs = [], array $options = [])
    {
        Needs::module('moment');
        Needs::module('daterangepicker');
        Needs::module('fb');

        $names = explode(',', $name);

        if (count($names) != 2) {
            throw new InvalidArgumentException("daterangepicker expects name ({$name}) to be in the form of two comma-separated identifiers; e.g. 'date_start,date_end'");
        }

        list($name_start, $name_end) = $names;

        if (!isset($options['dir'])) $options['dir'] = 'future';

        if (isset($options['min'])) Validity::dateMySQL($options['min']);
        if (isset($options['max'])) Validity::dateMySQL($options['max']);
        if (!isset($attrs['autocomplete'])) $attrs['autocomplete'] = 'off';

        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox fb-daterangepicker');

        foreach ($options as $key => $val) {
            $attrs['data-' . $key] = $val;
        }

        $out = self::input('hidden', $name_start, ['class' => 'fb-hidden fb-daterangepicker--start']);
        $out .= self::input('hidden', $name_end, ['class' => 'fb-hidden fb-daterangepicker--end']);
        $out .= self::input('text', $name_start . '_to_' . $name_end . '_picker', $attrs);

        return $out;
    }


    /**
     * Renders simplified date range picker,
     * Output is in the form of two fields (given in name as a comma separated list) e.g.
     * name => date_start, date_end will result in two fields: date_start => YYYY-MM-DD, date_end => YYYY-MM-DD
     *
     * @param string $name The field name prefix
     * @param array $attrs Attributes for the input element, e.g. ['class' => 'super-input', 'style' => 'font-style: italic']
     *      'data-callback' => 'myCallBack' JS function name to be called upon dates updated
     *      Useage: myCallBack(date_from, date_to) { date_from = date_from.format('YYYY-M-D'); date_to = date_to.format('YYYY-M-D'); }
     * @param array $options Customisation options
     *      'min' => the minimum of this date range.
     *      'max' => the maximum of this date range.
     *
     * @return string The rendered HTML
     */
    public static function simpledaterangepicker($name, array $attrs = [], array $options = [])
    {
        Needs::module('moment');
        Needs::module('simpledaterangepicker');
        Needs::module('fb');

        $names = explode(',', $name);

        if (count($names) != 2) {
            throw new InvalidArgumentException("simpledaterangepicker expects name ({$name}) to be in the form of two comma-separated identifiers; e.g. 'date_start,date_end'");
        }

        list($name_start, $name_end) = $names;

        if (isset($options['min'])) Validity::dateMySQL($options['min']);
        if (isset($options['max'])) Validity::dateMySQL($options['max']);
        if (!isset($attrs['autocomplete'])) $attrs['autocomplete'] = 'off';
        if (!isset($attrs['data-callback'])) $attrs['data-callback'] = '';

        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox fb-simpledaterangepicker');

        foreach ($options as $key => $val) {
            if ($key != 'locale') {
                $attrs['data-' . $key] = $val;
            } else {
                $attrs['data-locale'] = json_encode($options['locale']);
            }
        }

        $out = self::input('hidden', $name_start, ['class' => 'fb-hidden fb-daterangepicker--start']);
        $out .= self::input('hidden', $name_end, ['class' => 'fb-hidden fb-daterangepicker--end']);
        $out .= self::input('text', $name_start . '_to_' . $name_end . '_picker', $attrs);

        return $out;
    }


    /**
     * Renders a datetime range picker
     *
     * Output is in the form of two fields (given in name as a comma separated list) e.g.
     * $name = 'date_start,date_end' will result in two fields:
     * date_start => YYYY-MM-DD HH:MM:SS, date_end => YYYY-MM-DD HH:MM:SS
     *
     * @param string $name The field name prefix
     * @param array $attrs Attributes for the input element, e.g. ['class' => 'super-input', 'style' => 'font-style: italic']
     * @param array $options Additional options:
     *     'min' Minimum datetime in YYYY-MM-DD HH:MM:SS format
     *     'max' Maximum datetime in YYYY-MM-DD HH:MM:SS format
     *     'incr' Time increment in minutes. Default 1
     *     'dir' Either "future" or "past", for the direction of the pre-configured ranges. Default "future"
     *     'dropdowns' => display the dropdown date selectors.
     * @return string The rendered HTML
     */
    public static function datetimerangepicker($name, array $attrs = [], array $options = [])
    {
        Needs::module('moment');
        Needs::module('daterangepicker');
        Needs::module('fb');

        $names = explode(',', $name);
        if (count($names) != 2) {
            throw new InvalidArgumentException("datetimerangepicker expects name ({$name}) to be in the form of
                two comma-separated identifiers; e.g. 'datetime_start,datetime_end'");
        }

        list($name_start, $name_end) = $names;

        if (!isset($options['dir'])) $options['dir'] = 'future';

        if (isset($options['min'])) Validity::dateTimeMySQL($options['min']);
        if (isset($options['max'])) Validity::dateTimeMySQL($options['max']);
        if (!isset($attrs['autocomplete'])) $attrs['autocomplete'] = 'off';

        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox fb-datetimerangepicker');

        foreach ($options as $key => $val) {
            $attrs['data-' . $key] = $val;
        }

        $out = self::input('hidden', $name_start, ['class' => 'fb-hidden fb-datetimerangepicker--start']);
        $out .= self::input('hidden', $name_end, ['class' => 'fb-hidden fb-datetimerangepicker--end']);
        $out .= self::input('text', $name_start . '_to_' . $name_end . '_picker', $attrs);

        return $out;
    }


    /**
     * Renders a timepicker field inside a SPAN, which displays a dropdown date selection box when clicked
     *
     * @param string $name The name of the field
     * @param array $attrs Attributes for the input element, e.g. ['id' => 'my-timepicker', class' => 'super-input', 'style' => 'font-style: italic']
     * @param array $params Additional options:
     *        'min' Minimum allowed time in 24-hour format with a colon, e.g. '07:00' for 7am
     *        'max' Maximum allowed time in 24-hour format with a colon, e.g. '20:30' for 8:30pm
     *        'increment' Time increments e.g. 30 is 30 minute increments
     * @return string HTML
     */
    public static function timepicker($name, array $attrs = [], array $params = [])
    {
        $value = self::getData($name);

        if (!isset($params['min'])) $params['min'] = '00:00';
        if (!isset($params['max'])) $params['max'] = '23:59';
        if (!isset($params['increment'])) $params['increment'] = 30;
        if (!isset($attrs['autocomplete'])) $attrs['autocomplete'] = 'off';
        $params['increment'] = (int) $params['increment'];

        self::injectId($attrs);
        $id = Enc::id($attrs['id']);

        Needs::module('fb');
        Needs::module('date');
        Needs::module('jquery.timepicker');

        $out = "<span id=\"{$id}_wrap\" class=\"fb-timepicker\" data-config=\"" . Enc::html(json_encode($params)) . "\">";

        self::addAttr($attrs, 'name', $name . '_widget');
        self::addAttr($attrs, 'type', 'text');
        self::addAttr($attrs, 'class', 'textbox timepicker tm');
        self::addAttr($attrs, 'autocomplete', 'off');

        $out .= self::tag('input', $attrs, []);
        $out .= "<input type=\"hidden\" name=\"{$name}\" value=\"" . Enc::html($value) . "\" class=\"hid\">";
        $out .= "</span>";

        return $out;
    }


    /**
     * Renders HTML containing a date-time selection UI. Output field value is in YYYY-MM-DD HH:MM:SS
     *
     * @todo Mobile to use a "datetime-local" field instead, for native UI
     * @throws ValidationException If 'min', 'max' or 'incr' options are invalid
     * @param string $name The field name
     * @param array $attrs Attributes for the input element, e.g. ['id' => 'my-timepicker', class' => 'super-input', 'style' => 'font-style: italic']
     * @param array $settings Various settings
     *     'min' Minimum datetime in YYYY-MM-DD HH:MM:SS format
     *     'max' Maximum datetime in YYYY-MM-DD HH:MM:SS format
     *     'incr' Time increment in minutes. Default 1
     *     'dropdowns' => display the dropdown date selectors.
     * @return string HTML
     */
    public static function datetimepicker($name, array $attrs = [], array $options = [])
    {
        Needs::module('moment');
        Needs::module('daterangepicker');
        Needs::module('fb');

        if (isset($options['min'])) Validity::datetimeMySQL($options['min']);
        if (isset($options['max'])) Validity::datetimeMySQL($options['max']);
        if (isset($options['incr'])) Validity::range($options['incr'], 1, 59);
        if (!isset($attrs['autocomplete'])) $attrs['autocomplete'] = 'off';

        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox fb-datetimepicker');

        foreach ($options as $key => $val) {
            $attrs['data-' . $key] = $val;
        }

        $out = self::input('hidden', $name, ['class' => 'fb-hidden']);
        $out .= self::input('text', $name . '_picker', $attrs);

        return $out;
    }

    /**
     * Renders HTML containing a total selector UI. Output field value for the total is in
     * a hidden field. The specific counts for each are also available
     *
     * @todo Does this need validation exceptions? I.e. min/max attributes invalid?
     * @param string $name The field name
     * @param array $attrs Attributes for the input element,
     *     e.g. ['id' => 'my-totalselector', class' => 'super-input', 'style' => 'font-style: italic']
     * @param array $options Various options
     *     'singular'                Label for total
     *     'plural'                  Plural label for total
     *     'fields'                  Array of fields that contribute to the total count
     *         'name'                Internal name of field, plaintext
     *         'label'               Field label (Sentence case), plaintext
     *         'helptext'            Additional helptext for the field, optional, limited subset html
     *         'min'                 Minimum allowed value, optional, default 0
     *         'max'                 Maximum allowed value, optional, default unlimited
     * @return string HTML
     */
    public static function totalselector($name, array $attrs = [], array $options = [])
    {
        needs::module('total-selector');

        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox total-selector__output');
        self::addAttr($attrs, 'readonly', true);

        if (isset($options['fields'])) {
            $fields = $options['fields'];
            unset($options['fields']);
        }

        foreach ($options as $key => $val) {
            $attrs['data-' . $key] = $val;
        }

        $out = self::input('text', $name, $attrs) . PHP_EOL;


        $out .= '<div class="field-element--totalselector__fields">' . PHP_EOL;

        foreach ($fields as $val) {
            $sub_attrs = [];
            $sub_attrs['type'] = 'number';
            $sub_attrs['class'] = 'textbox';
            $sub_attrs['id'] = $attrs['id'] . '-' . strtolower($val['name']);
            $sub_attrs['name'] = $val['name'];
            $sub_attrs['value'] = self::getData($val['name']);
            $sub_attrs['min'] = (int) @$val['min'];
            if (isset($val['max'])) {
                $sub_attrs['max'] = (int) @$val['max'];
            }

            $out .= '<div class="field-element field-element--number">' . PHP_EOL;
            $out .= '<div class="field-label">' . PHP_EOL;
            $out .= '<label for="' . Enc::html($sub_attrs['id']) .'">' . Enc::html($val['label']) . '</label>' . PHP_EOL;
            if (!empty($val['helptext'])) {
                $out .= '<div class="field-helper">' . Text::limitedSubsetHtml($val['helptext']) . '</div>' . PHP_EOL;
            }
            $out .= '</div>' . PHP_EOL;
            $out .= '<div class="field-input">' . PHP_EOL;
            $out .= Fb::tag('input', $sub_attrs) . PHP_EOL;
            $out .= '</div>' . PHP_EOL;
            $out .= '</div>' . PHP_EOL;
        }

        $out .= '</div>' . PHP_EOL;


        return $out;
    }


    /**
     * Renders a colour picker
     *
     * Uses the HTML5 'color' input type, and loads a JS fallback (spectrum)
     * http://bgrins.github.io/spectrum/
     * Note that spectrum requires jQuery 1.6 or later
     *
     * @param string $name The name of the input field
     * @param array $attrs Extra attributes for the input field
     * @param array $params Additional options; unused
     * @return string
     */
    public static function colorpicker($name, array $attrs = [], array $params = [])
    {
        Needs::module('spectrum');
        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox colorpicker');
        return self::input('color', $name, $attrs);
    }


    /**
     * Render map location selector
     * Zoom field is optional
     *
     * @wrap-in-fieldset
     * @param string $name Field names, comma separated, latitude,longitude,zoom
     * @param array $attrs Unused
     * @param array $params Unused
     * @return string HTML
     */
    public static function googleMap($name, array $attrs = [], array $params = [])
    {
        Needs::module('fb');

        $view = new View('sprout/components/fb_google_map');
        $view->names = explode(',', $name);
        $view->unique = md5(microtime(true));

        $view->values = [];
        foreach ($view->names as $name) {
            $view->values[] = self::getData($name);
        }

        // Remove zero values to avoid a pin in the middle of the ocean
        if ($view->values[0] == 0 and $view->values[1] == 0) {
            $view->values[0] = '';
            $view->values[1] = '';
        }

        return $view->render();
    }


    /**
     * A conditions list, which is an interface for building rules for
     * use in dynamic IF-statement style systems.
     *
     * Output POST data will be a JSON string of the condition rules,
     * as an array of objects with the keys 'field', 'op', 'val' for
     * each condition.
     *
     * There are two parameters:
     *    fields   array    Available field types, name => label
     *    url      string   AJAX lookup method which returns the
     *                      operator and value lists
     *
     * The lookup url is provided GET params 'field', 'op', 'val' and
     * should output JSON with two keys, 'op' and 'val', which are both
     * strings containing HTML for the fields; the op field should be
     * a SELECT and the val field should be an INPUT or a SELECT.
     *
     * @wrap-in-fieldset
     * @param string $name Field name
     * @param array $attrs Unused
     * @param array $params Array with two params, 'fields' and 'url'
     * @return string HTML
     */
    public static function conditionsList($name, array $attrs = [], array $params = [])
    {
        $data = self::getData($name);
        if (empty($data)) $data = '[]';

        Needs::module('underscore');
        Needs::module('fb');

        $view = new View('sprout/components/fb_conditions_list');
        $view->name = $name;
        $view->params = $params;
        $view->data = $data;
        return $view->render();
    }


    /**
     * Renders google autocomplete address fields
     *
     * @param string $name Field
     * @param array $attrs Attributes for the input element
     * @param array $params Config options
     *     ```js
     *     {
     *        fields: {street: field-name, city: field-name, state: field-name, postcode: field-name, country: field-name},
     *        restrictions: { country: ['AU'] }
     *     }
     *     ```
     *     OR assume $param is just the $fields component (fallback, deprecated)
     *
     *     Note: 'restrictions' are defined here:
     *     https://developers.google.com/maps/documentation/javascript/reference/places-autocomplete-service#ComponentRestrictions
     * @return string HTML
     */
    public static function autoCompleteAddress($name, array $attrs = [], array $options = [])
    {
        Needs::module('fb');
        Needs::googlePlaces();

        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox js-autocomplete-address');
        self::addAttr($attrs, 'autocorrect', 'off');

        if (!isset($options['fields']) and !isset($options['restrictions'])) {
            $options = ['fields' => $options];
        }

        $view = new View('sprout/components/fb_autocomplete_address');
        $view->options = $options;
        $view->form_field = self::input('text', $name, $attrs);

        return $view->render();
    }


    /**
     * Renders place name geocoding fields
     *
     * @param string $name Field
     * @param array $attrs Attributes for the input elements
     * @param array $options Config options
     *     ```js
     *     {
     *        fields: {street: field-name, city: field-name, state: field-name, postcode: field-name, country: field-name},
     *        restrictions: { country: 'AU' }
     *     }
     *     ```
     *     Beware: The restrictions cannot accept a country list like autoCompleteAddress().
     *
     *     Note: 'restrictions are defined here:
     *     https://developers.google.com/maps/documentation/javascript/reference/geocoder#GeocoderComponentRestrictions
     *
     * @return string HTML
     */
    public static function geocodeAddress($name, array $attrs = [], array $options = [])
    {
        Needs::module('fb');
        Needs::googlePlaces();

        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox js-geocode-address');
        self::addAttr($attrs, 'autocorrect', 'off');

        $view = new View('sprout/components/fb_geocode_address');
        $view->options = $options;
        $view->form_field = self::input('text', $name, $attrs);

        return $view->render();
    }

    /**
     * Render a 'generate code' button + text field
     *
     * @param mixed $name Field
     * @param array $attrs Attributes for the input element
     * @param array $options Settings
     * @return void
     */
    public static function randomCode($name, array $attrs = [], array $options = [])
    {
        self::injectId($attrs);
        self::addAttr($attrs, 'class', 'textbox column column-9');
        self::addAttr($attrs, 'autocorrect', 'off');
        self::addAttr($attrs, 'autocomplete', 'off');

        $defaults = [
            'size' => 10,
            'readable' => false,
            'uppercase' => true,
            'lowercase' => true,
            'numbers' => true,
            'symbols' => false,
        ];

        $view = new View('sprout/components/fb_random_code');
        $view->options = array_merge($defaults, $options);
        $view->form_id = $attrs['id'];
        $view->form_field = self::input('text', $name, $attrs);

        return $view->render();
    }



    /**
     * UI for selecting or drag-and-drop uploading one or more files.
     *
     * The field (refrenced by $name) is an array. If it's passed a a string, it will be comma-separated into an array.
     * As JsonForm will auto-convert arrays into comma-separated strings, this field can easily be used with a MySQL
     * field of type TEXT.
     *
     * You cannot have more than one of these on the page at a time
     *
     * This field WILL NOT operate in a non-admin environment
     *
     * @param string $name Field name. If [] is not at the end, this will be appended.
     * @param array $attrs Unused
     * @param array $options Includes the following:
     *        'filter': (int) One of the filters, e.g. {@see FileConstants}::TYPE_IMAGE
     * @return string HTML
     */
    public static function multipleFileSelect($name, array $attrs = [], array $options = [])
    {
        $data = self::getData($name);
        if (empty($data)) $data = [];

        if (is_string($data)) {
            $data = explode(',', $data);
        }

        $ids = [];
        foreach ($data as $id) {
            if (preg_match('/^[0-9]+$/', $id)) $ids[] = (int) $id;
        }

        $filenames = [];
        if (count($ids) > 0) {
            $params = [];
            $where = Pdb::buildClause([['id', 'IN', $ids]], $params);
            $filenames = Pdb::q("SELECT id, filename FROM ~files", $params, 'map');
        }

        if (substr($name, -2) != '[]') $name .= '[]';

        $opts = array();
        $opts['chunk_url'] = 'admin/call/file/ajaxDragdropChunk';
        $opts['done_url'] = 'admin/call/file/ajaxDragdropDone';
        $opts['form_url'] = 'admin/call/file/ajaxDragdropForm';
        $opts['cancel_url'] = 'admin/call/file/ajaxDragdropCancel';
        $opts['form_params'] = [];
        $opts['max_files'] = 100;

        $view = new View('sprout/components/multiple_file_select');
        $view->opts = $opts;
        $view->name = $name;
        $view->data = $data;
        $view->filenames = $filenames;
        $view->filter = (int) @$options['filter'];

        return $view->render();
    }


    /**
     * Generates the title for a field, possibly enclosing it in a label, possibly with a generated ID
     *
     * @deprecated This method is likely to be removed at any given moment.
     *             Please use {@see Form::nextFieldDetails} instead.
     *
     * @param string $title The title of the field
     * @param string|null $id The id to use. Empty string to auto-generate an id; false to disable the enclosing label,
     *        e.g. for a field which needs multiple inputs (such as a datepicker). The id will be used on the next
     *        input to be generated.
     * @return string Possibly a LABEL element, or otherwise HTML text
     */
    public static function title($title, $id = '')
    {
        if ($id === false) return Enc::html($title);

        if ($id) {
            self::$field_id = $id;
        } else {
            self::$field_id = $id = self::genId();
        }
        return '<label for="' . Enc::html(self::$field_id) . '">' . Enc::html($title) . '</label>';
    }


    /**
     * Renders a set of hidden fields
     * @param array $fields Field name-value pairs
     * @return string Several INPUT fields of type hidden
     */
    public static function hiddenFields(array $fields)
    {
        $out = '';
        foreach ($fields as $key => $val) {
            $key = Enc::html($key);
            $val = Enc::html($val);
            $out .= "<input type=\"hidden\" name=\"{$key}\" value=\"{$val}\">\n";
        }
        return $out;
    }

}
