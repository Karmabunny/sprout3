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

use karmabunny\kb\ValidationException;

/**
 * Checks a value matches an ID in a corresponding table.
 *
 * @package Sprout\Helpers\Rules
 */
class InTableRule extends BaseModelRule
{

    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (!$this->model) {
            return;
        }

        $pdb = $this->model->getConnection();
        $table = $this->model->getTableName();

        $exists = $pdb->find($table)->where(['id' => $value])->exists();

        if (!$exists) {
            throw new ValidationException('Invalid value');
        }
    }
}