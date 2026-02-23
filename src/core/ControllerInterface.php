<?php
/**
 * Copyright (C) 2026 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 */

namespace Sprout\Core;

/**
 * A controller interface.
 */
interface ControllerInterface
{

    /**
     * The application will invoke this method to invoke an action.
     *
     * If you please, you may wrap this method to create before/after hooks.
     *
     * @param mixed $method
     * @param mixed $args
     * @return mixed
     */
    public function _run($method, $args);
}
