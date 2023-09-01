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
 * A moderation component.
 *
 * You'll likely want to extend the {@see Moderate} base class instead.
 */
interface ModerateInterface
{

    /**
     * Approve the specified item.
     *
     * This is called from within a transaction.
     *
     * @param int $id
     * @return bool
     */
    public function approve($id);


    /**
     * Delete the specified item.
     *
     * Usually the best is to use the controller _deleteSave method.
     *
     * This is called from within a transaction.
     *
     * @param int $id
     * @return bool
     */
    public function delete($id);


    /**
     * Record extra data from the moderation form, if any.
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function setData($id, array $data): bool;


    /**
     * Process post actions.
     *
     * @return void
     */
    public function complete();


    /**
     * Render the moderation form.
     *
     * @return string
     */
    public function render(): string;
}
