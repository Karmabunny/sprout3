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
     * @param   array $actions Actions
     * @return  bool
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
     * @param   array $geometry Geometry properties
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
     * @param   mixed $image Image filename
     * @param   array $actions Actions to execute
     * @param   string $dir Destination directory path
     * @param   string $file Destination filename
     * @param   bool $render Whether to render the image immediately
     * @return  bool
     */
    abstract public function process($image, $actions, $dir, $file, $render = FALSE);

    /**
     * Flip an image. Valid directions are horizontal and vertical.
     *
     * @param   int $direction Direction to flip
     * @return  bool
     */
    abstract function flip($direction);

    /**
     * Crop an image. Valid properties are: width, height, top, left.
     *
     * @param   array $properties New properties
     * @return  bool
     */
    abstract function crop($properties);

    /**
     * Resize an image. Valid properties are: width, height, and master.
     *
     * @param   array $properties New properties
     * @return  bool
     */
    abstract public function resize($properties);

    /**
     * Rotate an image. Valid amounts are -180 to 180.
     *
     * @param   int $amount Amount to rotate
     * @return  bool
     */
    abstract public function rotate($amount);

    /**
     * Sharpen and image. Valid amounts are 1 to 100.
     *
     * @param   int $amount Amount to sharpen
     * @return  bool
     */
    abstract public function sharpen($amount);

} // End Image Driver
