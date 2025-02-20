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
use Kohana;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Form;


/**
 * Like LinkSpecDocument but specialised for images with resizing.
 *
 * @package Sprout\Helpers
 */
class LinkSpecImage extends LinkSpec
{

    /**
     * Get the URL for a given link.
     *
     * @param array $specdata [id, size]
     * @return string absolute URL
     */
    public function getUrl($specdata)
    {
        $id = $specdata['id'] ?? 0;
        $size = $specdata['size'] ?? null;

        if (empty($id)) {
            return Sprout::absRoot() . 'files/missing.png';
        }

        try {
            $q = "SELECT filename FROM ~files WHERE id = ?";
            $filename = Pdb::query($q, [$id], 'val');

            if ($size and preg_match('/^[a-z_]+$/', $size)) {
                // Named resize.
                $size_filename = File::getResizeFilename($filename, $size);

                // Ship this off to create the size in async.
                if (!File::exists($size_filename)) {
                    return Sprout::absRoot() . "file/download/{$id}/{$size}";
                }

                return Sprout::absRoot() . File::sizeUrl($filename, $size);

            } else if ($size) {
                // Dynamic resize.
                return Sprout::absRoot() . File::sizeUrl($filename, $size);

            } else {
                // No resize.
                return File::absUrl($filename);
            }

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
            $id = $curr_specdata['id'] ?? 0;
            $size = $curr_specdata['size'] ?? '';
        } else {
            $id = (int) $curr_specdata;
            $size = '';
        }

        Form::setData([
            $field_name => $id,
            '_size' => $size ?? null,
        ]);

        $sizes = Kohana::config('file.image_transformations');
        $sizes = array_keys($sizes);
        $sizes = array_combine($sizes, array_map([Inflector::class, 'title'], $sizes));

        Form::nextFieldDetails('Image', true);
        $out = Form::fileselector($field_name, ['filter' => FileConstants::TYPE_IMAGE]);

        Form::nextFieldDetails('Size', false);
        $out .= Form::dropdown('_size', ['-dropdown-top' => 'Original'], $sizes);
        return $out;
    }


    /**
    * Validate the submission, for instances where certain constraints apply
    **/
    public function isValid($specdata)
    {
        return true;
    }

}
