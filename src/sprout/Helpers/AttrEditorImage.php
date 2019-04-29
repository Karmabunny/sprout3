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


/**
 * Specialised image selector input field for page attributes
 */
class AttrEditorImage extends AttrEditor
{

    /**
    * Return HTML for editing the attribute.
    * Your attribute needs to return HTML for a single input element.
    * The field name for the element needs to be 'value'.
    *
    * @param string $val The current value of the attribute
    * @param string $attr_name The name of the attribute
    **/
    public function render($val, $attr_name)
    {
        Fb::setData(['value' => $val]);
        return Fb::fileSelector('value', [], ['required' => true, 'filter' => FileConstants::TYPE_IMAGE]);
    }


    /**
    * If your class has script needs, load them in here.
    * This is run on page load, not on ajax request.
    **/
    public function needs()
    {
    }


    /**
    * If you have any custom javascript to run, return it using this method.
    *
    * When your script runs, take a look at the $outer variable.
    **/
    public function javascript($val, $attr_name)
    {
        return 'Fb.initAll($outer);';
    }

}
