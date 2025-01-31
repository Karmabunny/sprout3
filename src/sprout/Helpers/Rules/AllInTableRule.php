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
 * Checks all selected values match IDs in a corresponding table.
 *
 * @package Sprout\Helpers\Rules
 */
class AllInTableRule extends BaseModelRule
{

    /** @inheritdoc */
    public function validateOne(string $field, $value)
    {
        if (!$this->model) {
            return;
        }

        $pdb = $this->model->getConnection();
        $table = $this->model->getTableName();

        // Extract IDs from autofill list submissions
        // This is a stop-gap measure until autofill list is reworked to only submit ID values
        foreach ($value as &$item) {
            if (!is_array($item)) continue;

            if (!isset($item['id'])) {
                throw new ValidationException('Invalid value');
            }

            $item = $item['id'];
        }
        unset($item);

        $value = array_unique($value);

        $found_ids = $pdb->find($table)
            ->where([['id', 'IN', $value]])
            ->select('id')
            ->column();

        if (count($value) != count($found_ids)) {
            throw new ValidationException('Invalid value');
        }
    }
}