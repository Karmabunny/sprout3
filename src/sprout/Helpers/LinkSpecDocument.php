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

use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Form;


class LinkSpecDocument extends LinkSpec
{

    /**
     * Get the URL for a given link.
     *
     * @param int $specdata file ID
     * @return string absolute URL
     */
    public function getUrl($specdata)
    {
        // Compat with LinkSpecImage.
        if (is_array($specdata)) {
            $id = $specdata['id'] ?? 0;
        } else {
            $id = (int) $specdata;
        }

        if (empty($id)) {
            return Sprout::absRoot() . 'files/missing.png';
        }

        try {
            $q = "SELECT filename FROM ~files WHERE id = ?";
            $filename = Pdb::query($q, [$id], 'val');
            return File::absUrl($filename);

        } catch (RowMissingException $e) {
            return Sprout::absRoot() . 'files/missing.png';
        }
    }


    /**
    * Get any extra html attributes to use for a given link
    * @return array
    **/
    public function getAttrs($specdata)
    {
        return array();
    }


    /**
    * If there are any {@see Needs} calls that the edit form requires, they should be loaded here
    **/
    public function loadNeeds()
    {
    }


    /**
    * Get the HTML to use for editing a given linkspec
    *
    * The HTML should create a HTML field with the name $field_name
    * If there is a spec currently being edited, the specdata will
    * be provided in $curr_specdata
    **/
    public function getEditForm($field_name, $curr_specdata)
    {
        if (is_array($curr_specdata)) {
            $curr_specdata = $curr_specdata['id'] ?? 0;
        }

        Form::setData([$field_name => $curr_specdata]);
        Form::nextFieldDetails('Document', true);
        return Form::fileselector($field_name, ['filter' => FileConstants::TYPE_DOCUMENT]);
    }


    /**
    * Validate the submission, for instances where certain constraints apply
    **/
    public function isValid($specdata)
    {
        return true;
    }

}
