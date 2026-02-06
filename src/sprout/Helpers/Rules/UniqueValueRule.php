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
 * Checks that a unique value doesn't already exist in the database, e.g. a username or email address.
 * This is to give a friendlier frontend to DB errors pertaining to UNIQUE constraints.
 *
 * N.B. this function uses LIKE for case-insensitive matching, so it's even stricter than a UNIQUE constraint.
 *
 * @package Sprout\Helpers\Rules
 */
class UniqueValueRule extends BaseModelRule
{

    public $message = 'Must be a unique value';


    /** @inheritdoc */
    public function parse(array $ruleset)
    {
        parent::parse($ruleset);

        if ($message = $ruleset['message'] ?? null) {
            $this->message = $message;
        }
    }


    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (!$this->model) {
            return;
        }

        $pdb = $this->model->getConnection();
        $table = $this->model->getTableName();

        $query = $pdb->find($table)
            ->where([[$field, 'LIKE', $value]]);

        if (property_exists($this->model, 'id') && !empty($this->model->id)) {
            $query->andWhere([['id', '!=', $this->model->id]]);
        }

        if ($query->exists()) {
            throw new ValidationException($this->message);
        }
    }
}