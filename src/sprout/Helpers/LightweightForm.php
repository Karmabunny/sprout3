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



/**
 * A variation of the {@see Form} helper which doesn't output errors, labels or helptext
 * Wraps form fields (e.g. from {@see Fb}) with additional HTML.
 */
class LightweightForm extends Form
{

    /**
     * Return HTML for a 'plain' field, i.e. one which doesn't require a FIELDSET wrapped around it.
     *
     * The main wrapping DIV will contain additional classes if the field is required, disabled or has an error.
     * A class is also output for hte field method name (if the name contains "Sprout\Helpers\Fb::" this is removed)
     * If the field has an explicit ID set, that will be added as a class on the wrapper too.
     *
     * @param callable $method The actual field rendering method
     * @param string $name The field name - this is passed to the rendering method
     * @param array $attrs The field attrs - this is passed to the rendering method
     * @param array $options The field options - this is passed to the rendering method
     * @return string HTML
     */
    public static function fieldPlain(callable $method, $name, array $attrs = [], array $options = [])
    {
        $classes = array('field-element', 'field-element--lightweight');
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
        if (isset(self::$errors[$name])) {
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

        $out = '<div class="' . Enc::html(implode(' ', $classes)) . '">';

        if (!isset($attrs['id'])) {
            $attrs['id'] = self::genId();
        }

        $field_html = call_user_func($method, $name, $attrs, $options);

        // Field itself
        $out .= '<div class="field-input">';
        $out .= $field_html;
        $out .= '</div>';

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
     * @param callable $method The actual field rendering method
     * @param string $name The field name - this is passed to the rendering method
     * @param array $attrs The field attrs - this is passed to the rendering method
     * @param array $options The field options - this is passed to the rendering method
     * @return string HTML
     */
    public static function fieldFieldset(callable $method, $name, array $attrs = [], array $options = [])
    {
        $classes = array('field-element', 'field-element--lightweight');
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
        if (isset(self::$errors[$name])) {
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

        if (!isset($attrs['id'])) {
            $attrs['id'] = self::genId();
        }

        $out = '<div class="' . Enc::html(implode(' ', $classes)) . '">';
        $out .= '<fieldset class="fieldset--' . self::fieldMethodClass($method) . '">';

        // Field itself
        $out .= '<div class="field-element__input-set">';
        $out .= call_user_func($method, $name, $attrs, $options);
        $out .= '</div>';

        $out .= '</fieldset>';

        $out .= '</div>';
        $out .= PHP_EOL . PHP_EOL;

        self::resetField();

        return $out;
    }

}

