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
 * This is a base class for find-replace behaviours, aka. the doomtool.
 *
 * @see FindReplace
 * @see FindReplaceText
 * @see FindReplaceWidget
 */
interface FindReplaceInterface
{

    /**
     * The friendly name for this doomtool shown in the dbtools.
     *
     * This should be unique enough to identify how the tool behaves.
     *
     * @return string
     */
    public function getName(): string;


    /**
     * The unique key for this doomtool.
     *
     * This should represent the class and any instance settings.
     *
     * Wrap this in a UUIDv5 using the `FindReplace::NAMESPACE` to make it
     * smaller and URL safe.
     *
     * @return string
     */
    public function key(): string;


    /**
     * Find all occurrences of the the given string patterns.
     *
     * The result set:
     *
     * - `id`: the record ID, if any
     * - `key`: the replacer instance
     * - `text`: the full text body in which the pattern was found
     * - `url`: the URL to the record, if any
     * - `indexes`: the character indexes of the matches
     * - `count`: number of matches (should match indexes length)
     *
     * The indexes are a list of `[ start, length ]` pairs. These can be used
     * to highlight the matches in the text.
     *
     * @param string[] $finds regex patterns
     * @param array $settings [ ignore_case ]
     * @return iterable<array> [ id, key, text, url, indexes, count ]
     */
    public function find(array $finds, array $settings): iterable;


    /**
     * Find and replace all occurrences of the given string patterns.
     *
     * @param string[] $replaces [ find => replace ] regex + replacements
     * @param array $settings [ ignore_case ]
     * @return int number of replacements
     */
    public function replace(array $replaces, array $settings): int;
}
