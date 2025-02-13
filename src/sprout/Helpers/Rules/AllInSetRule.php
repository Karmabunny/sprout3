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
 * Checks all selected values belong to a database SET definition.
 *
 * @package Sprout\Helpers\Rules
 */
class AllInSetRule extends BaseModelRule
{

    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (!$this->model) {
            return;
        }

        if (!is_array($value)) {
            $value = $value ? [$value] : [];
        }

        $pdb = $this->model->getConnection();
        $table = $this->model->getTableName();
        $set = $pdb->extractEnumArr($table, $field);

        $errors = [];

        foreach ($value as $item) {
            if (!in_array($item, $set)) {
                $errors[] = $item;
            }
        }

        if ($errors) {
            throw new ValidationException('Invalid values: ' . implode(', ', $errors));
        }
    }
}