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
 * Base class for modifying values for main_columns of an {@see ItemList},
 * e.g. in {@see ManagedAdminController::_getContents}
 */
abstract class ColModifier {

    /**
    * Modify a column value
    * This value will be html/csv/etc encoded afterwards.
    *
    * @param string $val The incoming value
    * @param string $field_name The name of the field being modified
    * @return string The modified value
    **/
    abstract public function modify($val, $field_name);

}


