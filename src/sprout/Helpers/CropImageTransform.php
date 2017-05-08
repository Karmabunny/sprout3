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
* Does image resizing, etc
**/
class CropImageTransform implements ImageTransform
{
    private $width;
    private $height;
    private $vert_pos;
    private $horiz_pos;


    /**
    * Constructor
    *
    * @param int $width The width to crop the image at.
    * @param int $height The height to crop the image at.
    * @param mixed $vert_pos The position of the crop. A pixel value, or one of 'top', 'center' or 'bottom'.
    * @param mixed $horiz_pos The position of the crop. A pixel value, or one of 'left', 'center' or 'right'.
    **/
    public function __construct($width, $height, $vert_pos = 'center', $horiz_pos = 'center')
    {
        $this->width = $width;
        $this->height = $height;
        $this->vert_pos = $vert_pos;
        $this->horiz_pos = $horiz_pos;
    }


    /**
    * Does the actual transform
    *
    * @param Image $img The image to transform
    **/
    public function transform(Image $img)
    {
        $width_bad = $height_bad = false;

        if ($this->width == null or $img->width < $this->width) $width_bad = true;
        if ($this->height == null or $img->height < $this->height) $height_bad = true;

        if ($width_bad and $height_bad) return false;

        $img->crop($this->width, $this->height, $this->vert_pos, $this->horiz_pos);
        return true;
    }


    /**
    * Estimate the RAM requirement to run this transform
    *
    * @return int Bytes
    **/
    function estimateRamRequirement()
    {
        return $this->width * $this->height * 4;
    }

}


