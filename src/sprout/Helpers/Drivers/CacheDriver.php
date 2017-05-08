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
     * Set a cache item.
     */
    public function set($id, $data, array $tags = NULL, $lifetime);

    /**
     * Find all of the cache ids for a given tag.
     */
    public function find($tag);

    /**
     * Get a cache item.
     * Return NULL if the cache item is not found.
     */
    public function get($id);

    /**
     * Delete cache items by id or tag.
     */
    public function delete($id, $tag = FALSE);

    /**
     * Deletes all expired cache items.
     */
    public function deleteExpired();

} // End Cache Driver
