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

use Kohana;

/**
* Does image resizing, etc
**/
class ResizeImageTransform implements ImageTransform
{
    private $width;
    private $height;
    private $master;
    private $upscale;


    /**
    * Constructor
    *
    * @param int|null $width The width to resize the image to.
    * @param int|null $height The height to resize the image to.
    * @param int|null $master Optional master dimension. Image::WIDTH or Image::HEIGHT
    * @param bool|null $upscale Optional. Null implies config `file.upscale_images`
    **/
    public function __construct($width, $height, $master = null, $upscale = null)
    {
        $this->width = $width;
        $this->height = $height;
        $this->master = $master;

        if ($upscale === null) {
            $upscale = Kohana::config('file.upscale_images');
        }

        $this->upscale = $upscale;
    }


    /**
    * Does the actual transform
    *
    * @param Image $img The image to transform
    * @return bool
    **/
    public function transform(Image $img)
    {
        if (
            !$this->upscale
            and ($this->width == null or $img->width < $this->width)
            and ($this->height == null or $img->height < $this->height)
        ) {
            return false;
        }

        if ($this->width == null and $this->height == null) {
            return false;
        }

        $img->resize($this->width, $this->height, $this->master);
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


    /**
     * Gets the dimensions
     * @return array Keys are width, height, and master; these match the constructor args
     */
    function getDimensions()
    {
        return ['width' => $this->width, 'height' => $this->height, 'master' => $this->master];
    }

}


