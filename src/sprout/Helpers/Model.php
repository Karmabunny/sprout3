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

use Exception;
use karmabunny\kb\CachedHelperTrait;
use karmabunny\kb\EncryptInterface;
use karmabunny\kb\RulesClassValidator;
use karmabunny\kb\RulesStaticValidator;
use karmabunny\kb\RulesValidatorInterface;
use karmabunny\kb\RulesValidatorTrait;
use karmabunny\kb\Validates;
use karmabunny\kb\ValidationException;
use Kohana;
use PDOException;

/**
 * Base model class
 *
 * @package Sprout\Helpers
 */
abstract class Model extends Record implements Validates
{
    use RulesValidatorTrait;
    use CachedHelperTrait;


    /**
     * @inheritdoc
     * @param array $conditions
     * @return ModelQuery
     */
    public static function find(array $conditions = []): ModelQuery
    {
        return (new ModelQuery(static::class))
            ->where($conditions);
    }


    /** @inheritdoc */
    public function getSaveData(): array
    {
        $data = parent::getSaveData();

        $pdb = static::getConnection();
        $table = static::getTableName();
        $now = $pdb->now();

        // Include the uuid if it's not already set.
        // This may return NIL, that's OK - we do an insert + update later.
        if (empty($data['uid']) and property_exists($this, 'uid')) {
            $id = 0;
            if (!empty($this->id)) {
                $id = (int) $this->id;
            }
            $data['uid'] = $pdb->generateUid($table, $id);
        }

        if (property_exists($this, 'date_modified')) {
            $data['date_modified'] = $now;
        }

        if (empty($this->id)) {
            if (property_exists($this, 'date_added')) {
                $data['date_added'] = $now;
            }
        }

        return $data;
    }


    /** @inheritdoc */
    protected function _internalSave(array &$data)
    {
        $pdb = static::getConnection();
        $table = static::getTableName();

        if (!empty($this->id)) {
            $pdb->update($table, $data, [ 'id' => $this->id ]);
        }
        else {
            $data['id'] = $pdb->insert($table, $data);

            // Now generate a real uuid.
            if (property_exists($this, 'uid')) {
                $data['uid'] = $pdb->generateUid($table, $data['id']);

                $pdb->update(
                    $table,
                    [ 'uid' => $data['uid'] ],
                    [ 'id' => $data['id'] ]
                );
            }
        }
    }


    /** @inheritdoc */
    protected function _afterSave(array $data)
    {
        parent::_afterSave($data);

        if (
            property_exists($this, 'date_modified')
            and isset($data['date_modified'])
        ) {
            $this->date_modified = $data['date_modified'];
        }

        if (
            property_exists($this, 'date_added')
            and isset($data['date_added'])
        ) {
            $this->date_added = $data['date_added'];
        }

        if (
            property_exists($this, 'uid')
            and isset($data['uid'])
        ) {
            $this->uid = $data['uid'];
        }
    }


    /**
     * Get the encrypt helper for this model.
     *
     * Default expects a Kohana config entry of `encryption.{table_name}`.
     * Will only be fired if the table has an `encrypt` column.
     *
     * @return EncryptInterface
     * @throws Exception
     */
    protected function _getEncrypt(): EncryptInterface
    {
        return Sprout::getEncrypt($this->getTableName());
    }


    /**
     * Get the validator for this model.
     *
     * This is configured in the 'model' config, unless overridden by the model itself.
     *
     * @return RulesValidatorInterface
     */
    public function getValidator(): RulesValidatorInterface
    {
        $validator = Kohana::config('models.validator');

        if ($validator === 'class') {
            $validator = new RulesClassValidator($this);
            $rules = Kohana::config('models.rules');
            $validator->setValidators($rules);
            return $validator;
        }

        if ($validator === 'static') {
            $validator = new RulesStaticValidator($this);
            $validity = Kohana::config('models.validity');
            $validator->setValidity($validity);
            return $validator;
        }

        throw new Exception("Unknown validator type: {$validator}");
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

        return parent::save();
    }


    /** @inheritdoc */
    public function rules(?string $scenario = null): array
    {
        return [];
    }
}
