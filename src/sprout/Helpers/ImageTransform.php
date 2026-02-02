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
* Does image resizing, or whatever transforms wanted really.
**/
interface ImageTransform {

    /**
    * Does the actual transform.
    *
    * @param Image $img The image object to transform. This is an object
    * provided by Kohana
    * @return bool True on success, false on error
    *
    * @see http://docs.kohanaphp.com/libraries/image
    **/
    function transform (Image $img);


    /**
    * Estimate the RAM requirement to run this transform
    *
    * @return int Bytes
    **/
    function estimateRamRequirement();

}


