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


use Sprout\Helpers\Form;


class LinkSpecInternal extends LinkSpec
{

    /**
    * Get the URL for a given link
    **/
    public function getUrl($specdata)
    {
        return Sprout::absRoot() . trim($specdata, '/');
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
    * Get the HTML to use for editing a given linkspec
    *
    * The HTML should create a HTML field with the name $field_name
    * If there is a spec currently being edited, the specdata will
    * be provided in $curr_specdata
    **/
    public function getEditForm($field_name, $curr_specdata)
    {
        Form::setData([$field_name => $curr_specdata]);
        Form::nextFieldDetails('Internal URL', true);
        return Form::text($field_name, ['placeholder' => 'e.g. /contact']);
    }


    /**
    * Validate the submission, for instances where certain constraints apply
    **/
    public function isValid($specdata)
    {
        return true;
    }

}
