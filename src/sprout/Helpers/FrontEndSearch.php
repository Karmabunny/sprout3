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

interface FrontEndSearch {

    /**
    * Process the results of a search.
    *
    * @param int $item_id The id of the record to output
    * @param float $relevancy The relevancy of the chosen item
    * @param array $keywords The keywords that were used to conduct the search
    * @return string|false The result string or false if not found
    **/
    public function frontEndSearch($item_id, $relevancy, $keywords);

}


