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

use karmabunny\pdb\Exceptions\RowMissingException;

/**
 * This is a system of portable, extensible link management.
 *
 * The system is based around the "link specification", which is a string.
 * Link specifications should be stored in a TEXT field.
 *
 * These specs relate to classes which extend {@see LinkSpec}.
 * The class name needs to start with "LinkSpec" too.
 */
class Lnk
{

    /**
    * Some edit forms require additional javascript via the {@see needs} helper
    * Load in all of these requirements for all LinkSpec classes
    **/
    public static function editformNeeds()
    {
        $specs = Register::getLinkspecs();
        foreach ($specs as $class_name => $label) {
            $inst = new $class_name;
            $inst->loadNeeds();
        }
    }


    /**
    * Output html for editing link specifications.
    * This is designed for admin use.
    *
    * @param string $field_name The name to use for the field
    * @param string $curr_spec Current spec data, for existing fields
    * @param bool $required Is it a required field?
    * @return string HTML
    **/
    public static function editform($field_name, $curr_spec = null, $required = false)
    {
        self::editformNeeds();

        $view = new PhpView('sprout/components/lnk_editform');
        $view->field_name = $field_name;
        $view->curr_spec = $curr_spec;
        $view->required = $required;
        return $view->render();
    }


    /**
    * For a given link specification, instance it's class
    *
    * @param string|array $spec
    * @param class-string<LinkSpec>|class-string<LinkSpec>[] $assert must match one of these (not all)
    * @return array [0] => instance, [1] => spec data, [2] => spec label (as registered)
    * @throws InvalidArgumentException If the link specification is malformed (invalid data, missing class)
    **/
    public static function instance($spec, $assert = null)
    {
        $spec = self::parse($spec, true);
        $inst = Sprout::instance($spec['class'], $assert, false);

        return array($inst, $spec['data'], $spec['label']);
    }


    /**
     * Parse a link specification.
     *
     * @param mixed $spec
     * @return array [ class, data, label ]
     * @throws InvalidArgumentException
     */
    public static function parse($spec)
    {
        if (!is_array($spec)) {
            $spec = @json_decode($spec, true);
        }

        if (!is_array($spec)) {
            throw new InvalidArgumentException('Invalid link specification - parse error');
        }

        $class = $spec['class'] ?? null;
        $data = $spec['data'] ?? null;

        if (!$class or $data === null) {
            throw new InvalidArgumentException('Invalid link specification - missing fields');
        }

        $class = '\\' . ltrim($class, '\\');
        $specs = Register::getLinkspecs();

        if (!isset($specs[$class])) {
            $message = 'Link specification refers to non-registered class';

            if (!IN_PRODUCTION) {
                $message .= ': ' . $class;
            }

            throw new InvalidArgumentException($message);
        }

        $label = $specs[$class];

        if (empty($spec['label'])) {
            $spec['label'] = $label;
        }

        $spec['class'] = $class;
        return $spec;
    }


    /**
    * Convert a link specification into a URL.
    *
    * @param string|array $spec A link specification
    * @return string Target URL
    * @throws InvalidArgumentException If the link specification is malformed (invalid data, missing class)
    **/
    public static function url($spec)
    {
        list($inst, $data) = self::instance($spec);
        return $inst->getUrl($data);
    }


    /**
     * Attempts to convert a link specification into a URL
     *
     * This differs in behaviour to `Lnk::url` as it will return null if the spec is
     * empty, but *not* malformed; it still throws an InvalidArgumentException in that case.
     * It will also return null if a RowMissingException is thrown by the link spec instance
     * during processing.
     *
     * Helpful when you wish to avoid breaking pages when someone deletes the linked record, e.g. a blog post,
     * without updating the corresponding link(s).
     *
     * @param string|array $spec A JSON link specification
     * @return string|null The target URL or null if the spec is empty or if a RowMissingException
     *                     is thrown during processing.
     * @throws InvalidArgumentException If the link specification is malformed (invalid data, missing class)
     */
    public static function tryUrl($spec)
    {
        if (empty($spec)) {
            return null;
        }

        try {
            return self::url($spec);
        } catch (RowMissingException $exp) {
            return null;
        }
    }


    /**
    * Return an opening A tag for a link specification.
    *
    * @param string|array $spec A link specification
    * @param array $attributes Additional link attributes
    *        These take precedence over any default attributes
    * @return string HTML for an opening A tag
    * @throws InvalidArgumentException If the link specification is malformed (invalid data, missing class)
    **/
    public static function atag($spec, $attributes = array()) {
        list($inst, $data) = self::instance($spec);

        if (! is_array($attributes)) $attributes = array();
        $attributes = array_filter(array_merge($inst->getAttrs($data), $attributes));
        ksort($attributes);

        $html = '<a href="' . Enc::html($inst->getUrl($data)) . '"';
        foreach ($attributes as $name => $val) {
            $html .= ' ' . Enc::html($name) . '="' . Enc::html($val) . '"';
        }
        $html .= '>';

        return $html;
    }


    /**
    * Output the name of the type of the linkspec.
    *
    * @param string|array $spec A link specification
    * @return string
    * @throws InvalidArgumentException If the link specification is malformed (invalid data, missing class)
    **/
    public static function typename($spec)
    {
        list($inst, $data, $type_label) = self::instance($spec);
        return  $type_label;
    }


    /**
    * Check if the data supplied for a spec is valid.
    *
    * @param string|array $spec A link specification
    * @return bool True if valid, false if invalid
    * @throws InvalidArgumentException If the link specification is malformed (invalid data, missing class)
    **/
    public static function valid($spec)
    {
        list($inst, $data) = self::instance($spec);
        return $inst->isValid($data);
    }

}

