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
 * A datepicker widget for the refine bar
 */
class RefineWidgetDatepicker extends RefineWidget
{
    private $options;


    /**
     * @param string $name The field name, e.g. 'name', '_some_foreign_column'
     * @param string $label The label to display on the form, e.g. 'Name'
     * @param array $options Options for the autocomplete field; see $options of {@see Fb::autocomplete}
     *
     */
    public function __construct($name, $label, $options)
    {
        parent::__construct($name, $label);
        $this->options = $options;
    }


    /**
     * Draws the widget.
     * @return string The HTML for the widget.
     */
    public function render()
    {
        Form::setFieldValue($this->name, $this->getValue());

        Form::nextFieldDetails($this->label, false);
        return Form::datepicker($this->name, ['-wrapper-class' => 'white small'], $this->options);
    }

}
