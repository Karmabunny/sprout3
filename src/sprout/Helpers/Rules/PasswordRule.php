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
namespace Sprout\Helpers\Rules;

use karmabunny\kb\BaseRule;
use karmabunny\kb\ValidationException;
use Kohana;
use Sprout\Helpers\Security;

/**
 * Validate password by length, type of characters, and list of common passwords
 *
 * @package Sprout\Helpers\Rules
 */
class PasswordRule extends BaseRule
{

    public $length = 6;

    public $classes = 2;

    public $bad_list = true;


    public function __construct()
    {
        $this->length = (int) Kohana::config('sprout.password_length');
        $this->length = max(6, $this->length);


        if (is_int($classes = Kohana::config('sprout.password_classes'))) {
            $this->classes = $classes;
        }

        if (is_bool($bad_list = Kohana::config('sprout.password_bad_list'))) {
            $this->bad_list = $bad_list;
        }
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        $errors = Security::passwordComplexity($value, $this->length, $this->classes, $this->bad_list);

        if (count($errors) > 0) {
            throw (new ValidationException)
                ->addErrors($errors);
        }
    }
}