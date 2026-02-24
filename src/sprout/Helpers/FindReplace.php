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
 * The tool of doom.
 *
 * Perform find-replace actions across the whole database.
 */
class FindReplace
{

    /**
     * A namespace for mushing keys.
     *
     * Wrap your keys in UUIDv5 with this to make it smaller but also URL safe.
     */
    const NAMESPACE = '691bf093-26a4-4d30-8a63-f7d0d94b700d';


    /**
     * Registered find/replace targets.
     *
     * @var FindReplaceInterface[]
     */
    private static $replacers = [];


    /**
     * Register a doomtool instance.
     *
     * @param FindReplaceInterface $replace
     * @return void
     */
    public static function register(FindReplaceInterface $replace)
    {
        $key = $replace->key();
        self::$replacers[$key] = $replace;
    }


    /**
     * Find a doomtool by it's key.
     *
     * This permits passing instance/configs via GET/POST params.
     *
     * @param string $key
     * @return null|FindReplaceInterface
     */
    public static function getReplacer(string $key): ?FindReplaceInterface
    {
        return self::$replacers[$key] ?? null;
    }


    /**
     * Get all doomtools.
     *
     * Or provide a set of keys.
     *
     * @param string[]|null $keys
     * @return FindReplaceInterface[]
     */
    public static function getReplacers(?array $keys = null): array
    {
        if ($keys === null) {
            return self::$replacers;
        }

        $keys = array_fill_keys($keys, true);
        return array_intersect_key(self::$replacers, $keys);
    }


    /**
     * Create a sample highlight using indexes.
     *
     * Indexes can be sourced from the replacer `find()` results.
     *
     * @see FindReplaceInterface::find()
     * @see findIndexes()
     *
     * @param string $text
     * @param array $index [ start, length ]
     * @param int $padding
     * @return string HTML-safe snippet
     */
    public static function getSample(string $text, array $index, $padding = 20): string
    {
        if (!$text or !$index) {
            return '';
        }

        [$start, $length] = $index;
        $item = substr($text, $start, $length);

        $sample = '<b>' . Enc::html($item) . '</b>';

        if ($start > 0) {
            $sstart = max(0, $start - $padding);
            $slength = $start - $sstart;
            $sample = '...' . Enc::html(substr($text, $sstart, $slength)) . $sample;
        }

        $sample .= Enc::html(substr($text, $start + $length, $padding)) . '...';

        return $sample;
    }


    /**
     * Find indexes for a set of patterns.
     *
     * @param string $text
     * @param array $finds
     * @param bool $ignore_case
     * @return array[] [ start, length ]
     */
    public static function findIndexes(string $text, array $finds, bool $ignore_case = true): array
    {
        if (!$text) {
            return [];
        }

        $indexes = [];

        foreach ($finds as $item) {
            if (!$item) continue;

            $pattern = '!' . $item . '!';

            if ($ignore_case) {
                $pattern .= 'i';
            }

            $offset = 0;

            // Replace loop.
            for (;;) {
                $matches = [];
                if (!preg_match($pattern, $text, $matches, PREG_OFFSET_CAPTURE, $offset)) {
                    break;
                }

                [$match, $index] = $matches[0];
                $length = strlen($match);

                $indexes[] = [$index, $length];
                $offset += $index + $length;
            }
        }

        return $indexes;
    }
}
