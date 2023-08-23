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
interface ModerateWithDefaultsInterface extends ModerateWithExtraDataInterface
{


    /**
     * Return an array of one or more items which need moderating.
     *
     * The array should have the following format:
     * [] = array('id' => ['config'])
     *      id      record identifier
     *      [
     *           html     record preview html
     *           default  default selected value
     *      ]
     *
     * Return NULL on error
     */
    public function getList(): ?array;
}
