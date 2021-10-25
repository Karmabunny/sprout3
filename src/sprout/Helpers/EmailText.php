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

use Kohana;

use karmabunny\pdb\Exceptions\RowMissingException;


class EmailText
{

    /**
    * Return email text HTML
    *
    * @param string $code The email template to use
    * @param array $field_values The field values to replace into the template.
    *        Don't escape these, they'll be escaped for you.
    **/
    public static function getHtml($code, array $field_values)
    {

        // Get record
        $reg = Register::getEmailText($code);
        if (! $reg) {
            throw new Exception('Did not find registration for emailText with code of "' . $code . '"');
        }

        // Fetch text from DB
        $q = "SELECT text FROM ~email_texts WHERE name = ?";
        try {
            $text = Pdb::q($q, [$code], 'val');
        } catch (RowMissingException $ex) {

            // If text was found use it, or use the default
            $text = $reg->getDefaultHTML();
        }



        // Add the default field replacements
        $field_values['site_title'] = Kohana::config('sprout.site_title');
        $field_values['site_url'] = Sprout::absRoot();

        // Perform field replacements
        foreach ($field_values as $key => $val) {
            $text = str_replace('{{' . $key . '}}', Enc::html($val), $text);
        }

        return $text;
    }


    /**
    * Return an array of name => desc for the field definitions for a given email template
    * Used for the admin ui
    **/
    public static function getFieldDefs($code)
    {
        $reg = Register::getEmailText($code);
        if (! $reg) return null;

        return array_merge(
            array(
                'site_title' => 'The name of the website',
                'site_url' => 'The URL for accessing the website',
            ),
            $reg->getFieldDefs()
        );
    }


    /**
    * Return the URL to use for editing a given template
    * Note that this method creates a template record if it doesn't yet exist
    **/
    public static function adminEditUrl($code)
    {

        // Get the registration
        $reg = Register::getEmailText($code);
        if (! $reg) {
            throw new Exception('Did not find registration for emailText with code of "' . $code . '"');
        }

        // Fetch existing record
        $q = "SELECT id FROM ~email_texts WHERE name = ?";
        try {
            $item_id = Pdb::q($q, [$code], 'val');
        } catch (RowMissingException $ex) {
            // If no existing, create one
            $update_fields = array();
            $update_fields['date_added'] = Pdb::now();
            $update_fields['date_modified'] = Pdb::now();
            $update_fields['name'] = $code;
            $update_fields['text'] = $reg->getDefaultHTML();
            $item_id = Pdb::insert('email_texts', $update_fields);
        }

        return 'admin/edit/email_text/' . $item_id;
    }

}
