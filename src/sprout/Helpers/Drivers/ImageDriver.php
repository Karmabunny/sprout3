<?php
/**
 * Copyright (C) 2017 Karmabunny Pty Ltd.
 *
 * This file is a part of SproutCMS.
 *
 * SproutCMS is free software: you can redistribute it and/or modify it under the terms
 * of the GNU General Public License as published by the Free Software Foundation, either
 * version 2 of the License, or (at your option) any later version.
 *
 * For more information, visit <http://getsproutcms.com>.
 *
 * This class was originally from Kohana 2.3.4
 * Copyright 2007-2008 Kohana Team
 */
namespace Sprout\Helpers\Drivers;


/**
 * Image API driver.
 */
abstract class ImageDriver {

    // Reference to the current image
    protected $image;

    // Reference to the temporary processing image
    protected $tmp_image;

    // Processing errors
    protected $errors = array();

    /**
     * Executes a set of actions, defined in pairs.
     *
     * @param   array    actions
     * @return  boolean
     */
    public function execute($actions)
    {
        foreach ($actions as $func => $args)
        {
            if ( ! $this->$func($args))
                return FALSE;
        }

        return TRUE;
    }

    /**
     * Sanitize and normalize a geometry array based on the temporary image
     * width and height. Valid properties are: width, height, top, left.
     *
     * @param   array  geometry properties
     * @return  void
     */
    protected function sanitizeGeometry( & $geometry)
    {
        list($width, $height) = $this->properties();

        // Turn off error reporting
        $reporting = error_reporting(0);

        // Width and height cannot exceed current image size
        $geometry['width']  = min($geometry['width'], $width);
        $geometry['height'] = min($geometry['height'], $height);

        // Set standard coordinates if given, otherwise use pixel values
        if ($geometry['top'] === 'center')
        {
            $geometry['top'] = floor(($height / 2) - ($geometry['height'] / 2));
        }
        elseif ($geometry['top'] === 'top')
        {
            $geometry['top'] = 0;
        }
        elseif ($geometry['top'] === 'bottom')
        {
            $geometry['top'] = $height - $geometry['height'];
        }

        // Set standard coordinates if given, otherwise use pixel values
        if ($geometry['left'] === 'center')
        {
            $geometry['left'] = floor(($width / 2) - ($geometry['width'] / 2));
        }
        elseif ($geometry['left'] === 'left')
        {
            $geometry['left'] = 0;
        }
        elseif ($geometry['left'] === 'right')
        {
            $geometry['left'] = $width - $geometry['width'];
        }

        // Restore error reporting
        error_reporting($reporting);
    }

    /**
     * Return the current width and height of the temporary image. This is mainly
     * needed for sanitizing the geometry.
     *
     * @return  array  width, height
     */
    abstract protected function properties();

    /**
     * Process an image with a set of actions.
     *
     * @param   string   image filename
     * @param   array    actions to execute
     * @param   string   destination directory path
     * @param   string   destination filename
     * @return  boolean
     */
    abstract public function process($image, $actions, $dir, $file);

    /**
     * Flip an image. Valid directions are horizontal and vertical.
     *
     * @param   integer   direction to flip
     * @return  boolean
     */
    abstract function flip($direction);

    /**
     * Crop an image. Valid properties are: width, height, top, left.
     *
     * @param   array     new properties
     * @return  boolean
     */
    abstract function crop($properties);

    /**
     * Resize an image. Valid properties are: width, height, and master.
     *
     * @param   array     new properties
     * @return  boolean
     */
    abstract public function resize($properties);

    /**
     * Rotate an image. Valid amounts are -180 to 180.
     *
     * @param   integer   amount to rotate
     * @return  boolean
     */
    abstract public function rotate($amount);

    /**
     * Sharpen and image. Valid amounts are 1 to 100.
     *
     * @param   integer  amount to sharpen
     * @return  boolean
     */
    abstract public function sharpen($amount);

} // End Image Driver
