<?php
/*
 * Copyright (C) 2021 Karmabunny Pty Ltd.
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

use karmabunny\kb\CachedHelperTrait;
use karmabunny\kb\Collection;
use karmabunny\kb\RulesValidatorTrait;
use karmabunny\kb\Validates;
use karmabunny\pdb\Pdb;
use karmabunny\pdb\PdbModelInterface;
use karmabunny\pdb\PdbModelTrait;


/**
 * Base model class
 *
 * @property int $id
 * @property string $uid
 * @property bool $active
 * @property string $date_added
 * @property string $date_modified
 * @property string $date_deleted
 *
 * @package dashboard\Base
 */
abstract class Model extends Collection implements PdbModelInterface, Validates
{
   use RulesValidatorTrait;
   use CachedHelperTrait;
   use PdbModelTrait
   {
       save as protected _save;
    }


    /** @inheritdoc */
    public static function getConnection(): Pdb
    {
        return \Sprout\Helpers\Pdb::getInstance();
    }


    /** @inheritdoc */
    public function save($validate = true): bool
    {
        if ($validate) $this->validate();
        return $this->_save();
    }


    /** @inheritdoc */
    public function rules(): array
    {
        return [];
    }
}
