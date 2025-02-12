<?php
/**
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 *
 * This class was originally from Kohana 2.3.4
 * Copyright 2007-2008 Kohana Team
 */
namespace Sprout\Helpers\Drivers;


/**
 * Cache driver interface.
 */
interface CacheDriver {

    /**
     * Sets a cache item to the given data, tags, and lifetime.
     *
     * @param string $id
     * @param mixed $data
     * @param array $tags
     * @param int $lifetime in seconds
     */
    public function set($id, $data, array $tags = NULL, $lifetime = 0);

    /**
     * Find all of the cache ids for a given tag.
     *
     * @param string $tag
     * @return array [ id => data ]
     */
    public function find($tag);

    /**
     * Get a cache item.
     * Return NULL if the cache item is not found.
     *
     * @param string $id
     * @return mixed
     */
    public function get($id);

    /**
     * Delete cache items by id or tag.
     *
     * @param string|true $id true to delete all items
     * @param bool $tag set true to delete by tag
     * @return bool
     */
    public function delete($id, $tag = FALSE);

    /**
     * Deletes all expired cache items.
     *
     * @return void
     */
    public function deleteExpired();

} // End Cache Driver
