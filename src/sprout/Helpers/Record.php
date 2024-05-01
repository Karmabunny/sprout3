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
use karmabunny\kb\Reflect;
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


    /**
     * Data to be inserted or updated.
     *
     * Public, private and protected properties are included.
     * Any properties prefixed with an underscore are ignored.
     *
     * This is a perfect spot to add generated values like audit rows
     * (date_added, date_modified, uid, etc).
     *
     * Override this to implement dirty-property behaviour.
     *
     * @return array [ column => value ]
     */
    public function getSaveData(): array
    {
        $data = Reflect::getProperties($this, null);

        // Unset any data keys prefixed with an underscore
        foreach ($data as $key => $value) {
            if (strpos($key, '_') === 0) {
                unset($data[$key]);
            } else if(is_array($data[$key])) {
                $data[$key] = json_encode($data[$key]);
            }
        }

        unset($data['id']);
        return $data;
    }
}
