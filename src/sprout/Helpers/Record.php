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

use JsonException;
use karmabunny\kb\Collection;
use karmabunny\kb\Reflect;
use karmabunny\pdb\Models\PdbColumn;
use karmabunny\pdb\Pdb as PdbInstance;
use karmabunny\pdb\PdbModelInterface;
use karmabunny\pdb\PdbModelTrait;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;


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

        foreach ($data as $field => &$value) {
            // Exclude any properties prefixed with an underscore
            if (strpos($field, '_') === 0) {
                unset($data[$field]);
                continue;
            }

            if (!is_array($value)) {
                continue;
            }

            // Convert array data to SET or JSON-encoded string
            if (static::fieldIsSet($field)) {
                $value = implode(',', $value);
            } else {
                $value = json_encode($value);
            }
        }

        unset($data['id']);
        return $data;
    }


    /**
     * Get the field definitions for the table that holds this record
     *
     * @return array<string, PdbColumn>
     */
    protected static function fieldList(): array
    {
        static $fieldList = [];

        $table = static::getTableName();
        if (!isset($fieldList[$table])) {
            $pdb = static::getConnection();
            $fieldList[$table] = $pdb->fieldList($table);
        }
        return $fieldList[$table];
    }


    /**
     * Check if a field is of the type SET(...)
     */
    protected static function fieldIsSet(string $field_name): bool
    {
        $fields = static::fieldList();
        $field_defn = $fields[$field_name]['type'] ?? null;
        if ($field_defn === null) {
            return false;
        }
        return strtolower(substr($field_defn, 0, 4) === 'set(');
    }


    /**
     * Convert a property value to an array where expected
     *
     * E.g. from a JSON-encoded string, or a comma-separated list of SET elements
     *
     * @throws JsonException
     */
    protected function convertArrayValue(?ReflectionType $type, string $property, mixed &$value): void
    {
        if (is_array($value) || (!$type instanceof ReflectionNamedType) || $type->getName() !== 'array') {
            return;
        }

        if ($value === '' || $value === null) {
            if ($type->allowsNull()) {
                $value = null;
            } else {
                $value = [];
            }
            return;
        }

        if (static::fieldIsSet($property)) {
            $value = explode(',', $value);
            return;
        }

        // N.B. data source (e.g. MySQL JSON column) should always provide
        // valid JSON, so Json::decode should never throw an exception,
        // outside of memory/depth constraints
        $value = Json::decode($value);

        // Gracefully handle change from single value to multi-value column
        if (is_scalar($value)) {
            $value = [$value];
        }
    }

    /**
     *
     * @param iterable $config
     * @return void
     * @throws JsonException
     */
    public function update($config)
    {
        foreach ($config as $key => &$item) {
            if (!property_exists($this, $key)) {
                continue;
            }

            $type = (new ReflectionProperty($this, $key))->getType();
            $this->convertArrayValue($type, $key, $item);

            if ($item !== null) {
                continue;
            }

            // Prevent setting nulls on properties which don't support them
            // E.g. if a process creates a NULL value in a TEXT field,
            // but the model has a non-nullable string property for that field
            if ($type !== null && !$type->allowsNull()) {
                unset($config[$key]);
            }
        }
        parent::update($config);
    }
}
