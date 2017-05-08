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
* Sets the image quality for JPEGs.
* If you don't use one of these, the default is 95
**/
class QualityImageTransform implements ImageTransform
{
    private $q;


    /**
    * @param int $q The quality to use, 1 = worst, 100 = best.
    **/
    public function __construct($q)
    {
        $this->q = $q;
    }


    /**
    * Does the actual transform
    *
    * @param Image $img The image to transform
    **/
    public function transform(Image $img)
    {
        $img->quality($this->q);
        return true;
    }


    /**
    * Estimate the RAM requirement to run this transform
    *
    * @return int Bytes
    **/
    function estimateRamRequirement()
    {
        return 0;
    }

}


