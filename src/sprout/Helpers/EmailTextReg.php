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

class EmailTextReg
{
    private $field_defs;
    private $default_html_view;


    /**
    * Register an email text
    *
    * @param array $field_defs An array of name => description field definitions, for the admin
    *        e.g. array('first_name' => 'First name of the new user)
    *
    * @param string $default_html_view The view name for the default text. Must be a .htm view
    *        e.g 'email/user_welcome'
    **/
    public function __construct(array $field_defs, $default_html_view)
    {
        $this->field_defs = $field_defs;
        $this->default_html_view = $default_html_view;
    }


    /**
    * An array of name => description field definitions, for the admin
    **/
    public function getFieldDefs()
    {
        return $this->field_defs;
    }


    /**
    * Returns the HTML for the default content
    * This is loaded from a .htm view
    **/
    public function getDefaultHTML()
    {
        $default = new PhpView($this->default_html_view);
        return $default->render();
    }

}

