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

/**
* Richtext library for media repository sounds, they launch in a popup audio player
**/
class RteLibrarySounds extends RteLibrary
{
    protected $name = 'Media repository - audio';
    private $db;


    public function __construct()
    {
    }


    /**
    * Do a library browse
    *
    * @return array of RteLibContainer and RteLibObject objects
    **/
    public function browse($path)
    {
        $path_parts = explode('/', $path);

        if ($path == '') {
            // Get categories
            $q = "SELECT cat.id, cat.name
                FROM ~files_cat_list AS cat
                INNER JOIN ~files_cat_join AS joiner ON joiner.cat_id = cat.id
                INNER JOIN ~files AS files ON joiner.file_id = files.id
                WHERE files.type = ?
                  AND cat.show_admin = 1
                GROUP BY cat.id
                ORDER BY cat.name";
            $res = Pdb::query($q, [FileConstants::TYPE_SOUND], 'pdo');

            // Convert into 'Container' objects for rendering
            $out = array();
            foreach ($res as $row) {
                $out[] = new RteLibContainer($row['id'], $row['name']);
            }
            $res->closeCursor();

            return $out;

        } else if (count($path_parts) == 1) {
            // file list for files in category
            $cat_id = (int) $path_parts[0];
            $q = "SELECT files.name, files.filename, files.date_modified
                FROM ~files AS files
                INNER JOIN ~files_cat_join AS joiner ON joiner.file_id = files.id
                WHERE joiner.cat_id = ? AND files.type = ?
                ORDER BY files.name";
            $res = Pdb::query($q, [$cat_id, FileConstants::TYPE_SOUND], 'pdo');

            // Convert to 'Object' objects for rendering
            $out = array();
            foreach ($res as $row) {
                $out[] = new RteLibObject(
                    $row['filename'],
                    $row['name'],
                    array(
                        'href' => 'file/play_audio/' . $row['filename'],
                        'title' => $row['name'],
                    ),
                    array(
                        'date' => $row['date_modified'],
                        'size' => File::size($row['filename']),
                    )
                );
            }
            $res->closeCursor();

            return $out;
        }
    }


    /**
    * Do a library search
    *
    * @return array of RteLibContainer and RteLibObject objects
    **/
    public function search($term)
    {
        $conditions = array();
        $conditions[] = ['files.name', 'CONTAINS', $term];
        $conditions[] = ['files.filename', 'CONTAINS', $term];

        $params = [FileConstants::TYPE_SOUND];
        $where = Pdb::buildClause($conditions, $params, 'OR');

        $q = "SELECT files.name, files.filename, files.date_modified
            FROM ~files AS files
            INNER JOIN ~files_cat_join AS joiner ON joiner.file_id = files.id
            WHERE files.type = ?
              AND ({$where})
            ORDER BY files.name";
        $res = Pdb::query($q, $params, 'pdo');

        // Convert to 'Object' objects for rendering
        $out = array();
        foreach ($res as $row) {
            $out[] = new RteLibObject(
                $row['filename'],
                $row['name'],
                array(
                    'href' => 'file/play_audio/' . $row['filename'],
                    'title' => $row['name'],
                ),
                array(
                    'date' => $row['date_modified'],
                    'size' => File::size($row['filename']),
                )
            );
        }
        $res->closeCursor();

        return $out;
    }

}
