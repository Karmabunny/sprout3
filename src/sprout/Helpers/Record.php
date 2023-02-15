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

use karmabunny\kb\Collection;
use karmabunny\pdb\Pdb as PdbInstance;
use karmabunny\pdb\PdbModelInterface;
use karmabunny\pdb\PdbModelTrait;


/**
 * This is a lightweight model.
 *
 * No validation, no caching, no magic fields.
 *
 * @package Sprout\Helpers
 */
abstract class Record extends Collection implements PdbModelInterface
{
    use PdbModelTrait;

    /** @var int */
    public $id = 0;


    /** @inheritdoc */
    public static function getConnection(): PdbInstance
    {
        return Pdb::getInstance();
    }
}
