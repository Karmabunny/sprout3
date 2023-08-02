<?php
/*
 * Copyright (C) 2023 Karmabunny Pty Ltd.
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


/**
 * Helper for rendering out array data from a json stored string
 *
 * Does not HTML encode if preformatting.
 *
 * @package Sprout\Helpers
 */
class ColModifierJsonArray extends UnescapedColModifier
{
    private $_preformat;

    /**
     * @param bool $preformat Whether to wrap the content in a <pre> tag. Will escape otherwise
     */
    public function __construct(bool $preformat = true)
    {
        $this->_preformat = $preformat;
    }

    /**
    * Modify a column value
    * This value will be html/csv/etc encoded afterwards.
    *
    * @param mixed $val The incoming value
    * @param string $field_name The name of the field being modified
    * @return string The modified value
    **/
    public function modify($val, $field_name, $row)
    {
        $json_array = [];

        if (!is_array($val)) {
            // See if json..
            $json_array = json_decode($val, true);
            $json_error = json_last_error();

            // If not json, just return the value. No exploding plz.
            if ($json_error !== JSON_ERROR_NONE or !is_array($json_array)) {
                return Enc::html($val);
            }
        }

        $out = '';
        foreach ($json_array as $key => $data) {
            $key = Inflector::title($key);
            $out .= "{$key}: ";
            $out .= (is_array($data)) ? $this->implodeArrayVals($data, 1) : $data;
            $out .= "\n";
        }

        return $this->_preformat ? "<pre>{$out}</pre>" : nl2br(Enc::html($out));
    }


    /**
     * Do something readable with data that is an array
     *
     * @param array $val
     * @return string
     */
    private function implodeArrayVals(array $val, int $depth)
    {
        $out = [];

        foreach ($val as $key => $data) {
            $key = Inflector::title($key);
            $pad_length = $depth * 4;
            $pad = str_pad('', $pad_length, '.', STR_PAD_LEFT);
            $str = is_array($data) ? "\n{$pad}[{$key}]: " : "\n{$pad}{$key}: ";
            $str .= (is_array($data)) ?  $this->implodeArrayVals($data, ++$depth) : $data;

            $out[] = $str;
        }

        return implode(', ', $out);
    }

}


