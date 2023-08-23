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
 *
 */
interface ModerateWithNotesInterface extends ModerateWithExtraDataInterface
{

    /**
     * Update notes stored for the moderation instance
     *
     * @param int $id
     * @param string $notes
     *
     * @return bool
     */
    public function setNotes($id, string $notes): bool;


    /**
     * Return the notes stored for the moderation instance
     *
     * @param int $id
     *
     * @return null|string
     */
    public function getNotes($id): ?string;


    /**
     * Return the HTML for the notes field
     *
     * @param mixed $id
     * @return string
     */
    public function getNotesFieldHtml($id): string;

}
