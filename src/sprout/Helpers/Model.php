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
use karmabunny\kb\Uuid;
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
   use PdbModelTrait;

   /** @var int */
   public $id = 0;


    /** @inheritdoc */
    public static function getConnection(): Pdb
    {
        return \Sprout\Helpers\Pdb::getInstance();
    }


    /** @inheritdoc */
    public function save($validate = true): bool
    {
        if ($validate) $this->validate();

        $now = Pdb::now();
        $pdb = static::getConnection();
        $table = static::getTableName();
        $data = iterator_to_array($this);

        if ($this->id > 0) {
            if (property_exists($this, 'date_modified')) $data['date_modified'] = $now;

            if (property_exists($this, 'uid')) {
                if ($data['uid'] === Uuid::nil()) $data['uid'] = $this->getUid();
                $this->uid = $data['uid'];
            }

            $pdb->update($table, $data, ['id' => $this->id]);
        }
        else {
            if (property_exists($this, 'date_added')) $data['date_added'] = $now;
            if (property_exists($this, 'date_modified')) $data['date_modified'] = $now;

            $this->id = $pdb->insert($table, $data);

            if (property_exists($this, 'uid')) {
                $this->uid = $this->getUid();

                $pdb->update(
                    $table,
                    ['uid' => $this->uid],
                    ['id' => $this->id]
                );
            }
        }

        return (bool) $this->id;
    }


    /** @inheritdoc */
    public function rules(): array
    {
        return [];
    }


    /**
     * Generate an appropriate UUID.
     *
     * Beware - new records are created with a UUIDv4 while the save() method
     * generates a UUIDv5. Theoretically this shouldn't be externally apparent
     * due to the wrapping transaction.
     *
     * @return string
     * @throws Exception
     */
    protected function getUid()
    {
        // Start out with a v4.
        if ($this->id == 0) return Uuid::uuid4();

        // Upgrade it later with a v5.
        $pdb = static::getConnection();
        return $pdb->generateUid(static::getTableName(), $this->id);
    }
}
