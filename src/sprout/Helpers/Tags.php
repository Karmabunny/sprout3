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

use karmabunny\pdb\Exceptions\QueryException;


/**
* No description yet.
**/
class Tags
{

    /**
     * Returns all of the tags for a given record
     *
     * @param string $table
     * @param int $record_id
     * @return array Tags
     * @throws QueryException
     */
    public static function byRecord($table, $record_id)
    {
        $q = "SELECT name
            FROM ~tags
            WHERE record_table = ? AND record_id = ?
            GROUP BY name
            ORDER BY name";
        return Pdb::q($q, [$table, $record_id], 'col');
    }


    /**
     * Returns all of the tags for a given table
     *
     * @param string $table
     * @return array Tags
     * @throws QueryException
     */
    public static function byTable($table)
    {
        $q = "SELECT DISTINCT name
            FROM ~tags
            WHERE record_table = ?
            ORDER BY name";
        return Pdb::q($q, [$table], 'col');
    }


    /**
     * Returns all of the records for a given table/tag
     *
     * @param string $table Table name
     * @param string $tag Tag name
     * @return array Record IDs
     * @throws QueryException
     */
    public static function findRecords($table, $tag)
    {
        $q = "SELECT DISTINCT record_id
            FROM ~tags
            WHERE record_table = ? AND name = ?
            ORDER BY name";
        return Pdb::query($q, [$table, $tag], 'col');
    }


    /**
     * Return the tags for a table, along with the number of records for each tag
     *
     * @example
     *     $tags = Tags::recordCounts('blog_posts', 50);
     *     foreach ($tags as $tag => $count) {
     *         echo Enc::html($tag), ' (', $count, ')';
     *     }
     *
     * @param string $table Table to get counts for, e.g 'blog_posts'
     * @param int $limit Maximum number of tags to return. Use 999999 for "unlimited".
     * @return array Keys are tag names, values are counts
     */
    public static function recordCounts($table, $limit)
    {
        Pdb::validateIdentifier($table);
        $limit = (int) $limit;
        $q = "SELECT tag.name, COUNT(item.id) AS num
            FROM ~tags AS tag
            INNER JOIN ~{$table} AS item
                ON tag.record_table = ? AND tag.record_id = item.id
            GROUP BY tag.name
            ORDER BY COUNT(item.id) DESC, tag.name
            LIMIT {$limit}";
        return Pdb::query($q, [$table], 'map');
    }


    /**
     * Return the tags for a table, along with the number of records for each tag
     * Results are filtered by subsite, including records with subsite_id of NULL which is all sites
     *
     * @example
     *     $tags = Tags::recordCountsPerSubsite('blog_posts', 50, SubsiteSelector::$subsite_id);
     *     foreach ($tags as $tag => $count) {
     *         echo Enc::html($tag), ' (', $count, ')';
     *     }
     *
     * @param string $table Table to get counts for, e.g 'blog_posts'
     * @param int $limit Maximum number of tags to return. Use 999999 for "unlimited".
     * @param int $subsite_id Subsite to return record counts for; NULL for current subsite
     * @return array Keys are tag names, values are counts
     */
    public static function recordCountsPerSubsite($table, $limit, $subsite_id = null)
    {
        Pdb::validateIdentifier($table);
        $limit = (int) $limit;
        if ($subsite_id === null) {
            $subsite_id = SubsiteSelector::$subsite_id;
        }

        $q = "SELECT tag.name, COUNT(item.id) AS num
            FROM ~tags AS tag
            INNER JOIN ~{$table} AS item
                ON tag.record_table = ? AND tag.record_id = item.id
            WHERE item.subsite_id = ? OR item.subsite_id IS NULL
            GROUP BY tag.name
            ORDER BY COUNT(item.id) DESC, tag.name
            LIMIT {$limit}";
        return Pdb::query($q, [$table, $subsite_id], 'map');
    }


    /**
     * Returns all tags which begin with a given string
     *
     * @param string $prefix Search prefix
     * @return array Tags
     * @throws QueryException
     */
    public static function beginsWith($prefix)
    {
        $q = "SELECT DISTINCT name
            FROM ~tags
            WHERE name LIKE CONCAT(?, '%');
            ORDER BY name";
        return Pdb::query($q, [Pdb::likeEscape($prefix)], 'col');
    }


    /**
     * Returns common tags. Typically tags are returned from the provided table, but not necessarily.
     *
     * @param string $table
     * @param string $prefix
     * @param int $number
     * @return array Tags
     * @throws QueryException
     */
    public static function suggestTags($table = null, $prefix = '', $number = 10)
    {
        $number = (int) $number;

        $tags = [];
        $where = '1';
        $values = [];

        if ($prefix) {
            $values['prefix'] = Pdb::likeEscape($prefix);
            $where = "name LIKE CONCAT(:prefix, '%')";
        }

        // Load tags from this table
        if ($table) {
            $values['table'] = $table;
            $q = "SELECT name
                FROM ~tags
                WHERE record_table = :table AND {$where}
                GROUP BY name
                ORDER BY COUNT(record_id) DESC, name
                LIMIT {$number}";
            $tags = Pdb::q($q, $values, 'col');

            $where .= " AND record_table != :table";
        }

        // If not enough found, load from all other tables
        if (count($tags) < $number) {
            $q = "SELECT name
                FROM ~tags
                WHERE {$where}
                GROUP BY name
                ORDER BY COUNT(record_id) DESC, name
                LIMIT {$number}";
            $tags = array_merge($tags, Pdb::q($q, $values, 'col'));
        }

        $tags = array_unique($tags);
        $tags = array_slice($tags, 0, $number);

        return $tags;
    }


    /**
     * Updates tags for a given record
     *
     * @param string $table The table name of the record which is being updated
     * @param int $record_id The record to update
     * @param array $new_tags Array of tag names of the new tags
     * @param bool $remove Should removals be processed? Set to FALSE to only add tags. Default true.
     * @return bool True on success, false on failure
     * @throws QueryException
     */
    public static function update($table, $record_id, array $new_tags, $remove = true)
    {
        if ($table == '') return false;
        if ($record_id == 0) return false;

        Pdb::transact();

        try {
            // Determine which tags need adding and removing
            $current_tags = Tags::byRecord($table, $record_id);
            $add_tags = array_diff($new_tags, $current_tags);
            $del_tags = array_diff($current_tags, $new_tags);

            // Add tags that should be added
            foreach ($add_tags as $tag) {
                Pdb::insert('tags', ['record_table' => $table, 'record_id' => $record_id, 'name' => $tag]);
            }

            // Remove tags that should be added
            if ($remove and count($del_tags) > 0) {
                Pdb::delete('tags', ['record_table' => $table, 'record_id' => $record_id, ['name', 'IN', $del_tags]]);
            }

            Pdb::commit();
            return true;

        } catch (QueryException $ex) {
            Pdb::rollback();
            return false;
        }
    }


    /**
     * Converts a comma-separated string into a list of tags
     *
     * @param string $string
     * @return array Tags
     */
    public static function splitupTags($string)
    {
        $string = strtolower($string);
        $string = preg_replace('/[^-a-z0-9 ,]/', '', $string);
        $string = trim($string, ' ,');

        if ($string == '') return array();

        $tags = preg_split('/ *, */', $string);
        $tags = array_filter($tags);
        $tags = array_unique($tags);

        return array_merge($tags);
    }


    /**
     * Get HTML for a tag list (i.e. for tag display on the front-end)
     *
     * @param array $tags
     * @return void HTML
     */
    public static function getList(array $tags = null)
    {
        if (empty($tags)) return '';

        sort($tags);

        $view = new PhpView('sprout/tag_list');
        $view->tags = $tags;
        return $view->render();
    }

}


