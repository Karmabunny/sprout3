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

namespace Sprout\Helpers;

use karmabunny\pdb\Exceptions\RowMissingException;
use Sprout\Exceptions\ValidationException;


/**
 * Generate and validate slugs - i.e. unique, SEO-friendly URL segments.
 * See e.g. https://en.wikipedia.org/wiki/Semantic_URL#Slug
 */
class Slug
{

    /**
    * Create a slug, and ensure it's unique
    *
    * @param string $name The name of the item
    * @param string $table The table the item is on
    * @return string The generated slug, should always be unique
    **/
    public static function create($table, $name)
    {
        $base = Enc::urlname($name, '-');

        $index = 0;
        do {
            $trial = $base . ($index > 0 ? $index : '');
            try {
                self::get($table, $trial);
            } catch (RowMissingException $exp) {
                // No existing record found with that slug, so it's available
                return $trial;
            }
        } while ($index++ < 100);

        return $base . '-' . Sprout::randStr(20);
    }


    /**
     * Return all columns for a single row of a table (similar to {@see Pdb::get})
     * The row is specified using its slug.
     *
     * @param string $table The table name, not prefixed
     * @param string $slug The slug of the record to fetch
     * @param string $conditions Extra WHERE clause if required, in the format prescribed by {@see Pdb::buildClause}
     * @return array The record data
     * @throws RowMissingException If the record wasn't found
     * @throws QueryException if the query failed
     */
    public static function get($table, $slug, array $conditions = [])
    {
        Pdb::validateIdentifier($table);

        $params = [$slug];
        $q = "SELECT * FROM ~{$table} WHERE slug = ?";
        if (!empty($conditions)) {
            $q .= " AND " . Pdb::buildClause($conditions, $params);
        }
        return Pdb::q($q, $params, 'row');
    }


    /**
     * Verify that a slug contains only valid characters
     *
     * @example
     *      $valid->check('slug', 'Slug::valid');
     *
     * @param string $value The slug
     * @throws ValidationException
     */
    public static function valid($value)
    {
        if (preg_match('/^[a-z0-9\-]+$/', $value) !== 1) {
            throw new ValidationException('contains invalid characters. Slugs may only contain a to z (lower-case), 0 to 9 and hyphens (-)');
        }
    }

    /**
     * Verify that a slug is unique for a given table
     *
     * @example
     *      // Exclude the current record when checking uniqueness
     *      $valid->check('slug', 'Slug::unique', 'pages', [['id', '!=', $page_id]]);
     *
     * @param string $value The slug
     * @param string $table The table to check, which must contain columns 'id', 'name', and 'slug'
     * @param array $conditions Extra conditions to check, in the format prescribed by {@see Pdb::buildClause}
     * @return void
     * @throws ValidationException If the slug isn't unique in the table under the specified conditions
     * @throws InvalidArgumentException If the $table argument is not a valid table name
     */
    public static function unique($value, $table, array $conditions = [])
    {
        Pdb::validateIdentifier($table);

        $conditions['slug'] = $value;

        $params = [];
        $where = Pdb::buildClause($conditions, $params);
        try {
            $q = "SELECT id, name FROM ~{$table} WHERE {$where}";
            $row = Pdb::q($q, $params, 'row');

            $err = 'The slug "' . $value . '" is already in use';
            if (!empty($row['name'])) {
                $err .= ' by the record "' . $row['name'] . '"';
            }

            throw new ValidationException($err);
        } catch (RowMissingException $exp) {
        }
    }
}
