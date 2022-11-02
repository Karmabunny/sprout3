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


    /**
     * Save this model.
     *
     * @param bool|string $validate
     * @return bool
     * @throws ValidationException
     * @throws PDOException
     */
    public function save($validate = true): bool
    {
        // Use the default scenario if 'true'.
        $scenario = is_string($validate) ? $validate : null;

        if ($validate) {
            $this->validate($scenario);
        }

        // Only populate defaults for new models.
        if (!$this->id) {
            $this->populateDefaults();
        }

        $now = Pdb::now();
        $pdb = static::getConnection();
        $table = static::getTableName();
        $data = iterator_to_array($this);

        $transact = false;

        // Start a transaction but only if there isn't one already.
        if (!$pdb->inTransaction()) {
            $pdb->transact();
            $transact = true;
        }

        try {
            // Include the uuid if it's not already set.
            // This _may_ be NIL at this point.
            if (empty($data['uid']) and property_exists($this, 'uid')) {
                $data['uid'] = $pdb->generateUid($table, (int) $this->id);
            }

            if (property_exists($this, 'date_modified')) {
                $data['date_modified'] = $now;
            }

            // Perform edits.
            if ($this->id > 0) {
                $pdb->update($table, $data, ['id' => $this->id]);
            }

            // Perform creates.
            else {
                if (property_exists($this, 'date_added')) {
                    $data['date_added'] = $now;
                }

                $this->id = $pdb->insert($table, $data);

                // Now generate a real uuid.
                if (property_exists($this, 'uid')) {
                    $data['uid'] = $pdb->generateUid($table, $this->id);

                    $pdb->update(
                        $table,
                        ['uid' => $data['uid']],
                        ['id' => $this->id]
                    );
                }
            }

            // Update whatever local properties.

            if (property_exists($this, 'date_modified')) {
                $this->date_modified = $data['date_modified'];
            }

            if (property_exists($this, 'date_added')) {
                $this->date_added = $data['date_added'];
            }

            if (property_exists($this, 'uid')) {
                $this->uid = $data['uid'];
            }

            // Punch it.
            if ($transact and $pdb->inTransaction()) {
                $pdb->commit();
            }
        }
        finally {
            if ($transact and $pdb->inTransaction()) {
                $pdb->rollback();
            }
        }

        return (bool) $this->id;
    }


    /** @inheritdoc */
    public function rules(): array
    {
        return [];
    }
}
