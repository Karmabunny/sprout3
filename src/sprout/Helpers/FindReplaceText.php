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

use karmabunny\kb\Uuid;

/**
 * A doomtool for plaintext database columns.
 *
 * This is configurable during the construct. A set of these are registered
 * in sprout core for core tables.
 */
class FindReplaceText implements FindReplaceInterface
{

    /** @var string */
    public $table;

    /** @var string */
    public $column;


    /** @inheritdoc */
    public function getName(): string
    {
        return "Text: {$this->table}.{$this->column}";
    }


    /** @inheritdoc */
    public function __construct(string $table, string $column)
    {
        $this->table = $table;
        $this->column = $column;
    }


    /** @inheritdoc */
    public function key(): string
    {
        $key = get_class($this) . '.' . $this->table . '.' . $this->column;
        $key = Uuid::uuid5(FindReplace::NAMESPACE, $key);
        return $key;
    }


    /** @inheritdoc */
    public function find(array $finds, array $settings): iterable
    {
        if (!$finds) return;

        $ignore_case = $settings['ignore_case'] ?? true;

        $rows = Pdb::find($this->table)
            ->select([
                'id',
                $this->column,
            ])
            ->where([
                [$this->column, 'IS NOT', null],
                [$this->column, '!=', ''],
            ])
            ->iterator();

        foreach ($rows as $row) {
            $text = $row[$this->column];

            $indexes = FindReplace::findIndexes($text, $finds, $ignore_case);
            if (!$indexes) continue;

            yield [
                'id' => $row['id'],
                'key' => $this->key(),
                'name' => $this->column . ':' . $row['id'],
                'text' => $text,
                'url' => null,
                'indexes' => $indexes,
                'count' => count($indexes),
            ];
        }
    }


    /** @inheritdoc */
    public function replace(array $replaces, array $settings): int
    {
        $ignore_case = $settings['ignore_case'] ?? true;

        $count = 0;

        foreach ($replaces as $find => $replace) {
            $results = $this->find([$find], $settings);

            foreach ($results as $found) {
                $text = $found['text'];

                $pattern = '!' . $find . '!';

                if ($ignore_case) {
                    $pattern .= 'i';
                }

                $text = preg_replace($pattern, $replace, $text);

                $data = [ $this->column => $text ];
                $ok = Pdb::update($this->table, $data, ['id' => $found['id']]);

                if ($ok) {
                    $count += $found['count'];
                }
            }
        }

        return $count;
    }
}
