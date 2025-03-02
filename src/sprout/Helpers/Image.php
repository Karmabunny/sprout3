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
namespace Sprout\Helpers;

use InvalidArgumentException;

use Kohana;
use Kohana_Exception;

use Sprout\Helpers\Drivers\ImageDriver;


/**
 * Manipulate images using standard methods such as resize, crop, rotate, etc.
 * This class must be re-initialized for every image you wish to manipulate.
 *
 * @property string $file
 * @property int $width
 * @property int $height
 * @property string $type
 * @property string $ext
 * @property string $mime
 */
class Image
{

    // Master Dimension
    const NONE = 1;
    const AUTO = 2;
    const HEIGHT = 3;
    const WIDTH = 4;
    // Flip Directions
    const HORIZONTAL = 5;
    const VERTICAL = 6;

    // Allowed image types
    public static $allowed_types = array
    (
        IMAGETYPE_GIF => 'gif',
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_TIFF_II => 'tiff',
        IMAGETYPE_TIFF_MM => 'tiff',
        IMAGETYPE_WEBP => 'webp',
    );

    // Driver instance
    protected $driver;

    // Driver actions
    protected $actions = array();

    // Reference to the current image filename
    protected $image = '';

    // Current configuration
    protected $config = array();

    /**
     * Creates a new Image instance and returns it.
     *
     * @param string $image filename of image
     * @param array|null $config Config array for  non-default configurations
     * @return Image object
     */
    public static function factory($image, $config = NULL)
    {
        return new Image($image, $config);
    }

    /**
     * Creates a new image editor instance.
     *
     * @throws  Kohana_Exception
     * @param   string $image filename of image
     * @param   array|null  $config non-default configurations
     * @return  void
     */
    public function __construct($image, $config = NULL)
    {
        static $check;

        // Make the check exactly once
        ($check === NULL) and $check = function_exists('getimagesize');

        if ($check === FALSE)
            throw new Kohana_Exception('image.getimagesize_missing');

        // Check to make sure the image exists
        if ( ! is_file($image))
            throw new Kohana_Exception('image.file_not_found', $image);

        // Disable error reporting, to prevent PHP warnings
        $ER = error_reporting(0);

        // Fetch the image size and mime type
        $image_info = getimagesize($image);

        // Turn on error reporting again
        error_reporting($ER);

        // Make sure that the image is readable and valid
        if ( ! is_array($image_info) OR count($image_info) < 3)
            throw new Kohana_Exception('image.file_unreadable', $image);

        // Check to make sure the image type is allowed
        if ( ! isset(Image::$allowed_types[$image_info[2]]))
            throw new Kohana_Exception('image.type_not_allowed', $image);

        // Image has been validated, load it
        $this->image = array
        (
            'file' => str_replace('\\', '/', realpath($image)),
            'width' => $image_info[0],
            'height' => $image_info[1],
            'type' => $image_info[2],
            'ext' => Image::$allowed_types[$image_info[2]],
            'mime' => $image_info['mime']
        );

        // Load configuration
        $this->config = (array) $config + Kohana::config('image');

        // Set driver class name
        $driver = 'Sprout\\Helpers\\Drivers\\Image\\' . ucfirst($this->config['driver']);

        // Load the driver
        if (!class_exists($driver))
            throw new Kohana_Exception('core.driver_not_found', $this->config['driver'], get_class($this));

        // Initialize the driver
        $this->driver = new $driver($this->config['params']);

        // Validate the driver
        if ( ! ($this->driver instanceof ImageDriver))
            throw new Kohana_Exception('core.driver_implements', $this->config['driver'], get_class($this), 'ImageDriver');
    }

    /**
     * Handles retrieval of pre-save image properties
     *
     * @param   string $property name
     * @return  mixed
     */
    public function __get($property)
    {
        if (isset($this->image[$property]))
        {
            return $this->image[$property];
        }
        else
        {
            throw new Kohana_Exception('core.invalid_property', $property, get_class($this));
        }
    }

    /**
     * Resize an image to a specific width and height. By default, Kohana will
     * maintain the aspect ratio using the width as the master dimension. If you
     * wish to use height as master dim, set $image->master_dim = Image::HEIGHT
     * This method is chainable.
     *
     * @throws  Kohana_Exception
     * @param   integer|null  $width
     * @param   integer|null  $height
     * @param   integer|null $master  one of: Image::NONE, Image::AUTO, Image::WIDTH, Image::HEIGHT
     * @return  object
     */
    public function resize($width, $height, $master = NULL)
    {
        if ( ! $this->validSize('width', $width))
            throw new Kohana_Exception('image.invalid_width', $width);

        if ( ! $this->validSize('height', $height))
            throw new Kohana_Exception('image.invalid_height', $height);

        if (empty($width) AND empty($height))
            throw new Kohana_Exception('image.invalid_dimensions', __FUNCTION__);

        if ($master === NULL)
        {
            // Maintain the aspect ratio by default
            $master = Image::AUTO;
        }
        elseif ( ! $this->validSize('master', $master))
            throw new Kohana_Exception('image.invalid_master');

        $this->actions['resize'] = array
        (
            'width'  => $width,
            'height' => $height,
            'master' => $master,
        );

        return $this;
    }

    /**
     * Crop an image to a specific width and height. You may also set the top
     * and left offset.
     * This method is chainable.
     *
     * @throws  Kohana_Exception
     * @param   integer  $width
     * @param   integer  $height
     * @param   integer|string  $top offset, pixel value or one of: top, center, bottom
     * @param   integer|string  $left offset, pixel value or one of: left, center, right
     * @return  object
     */
    public function crop($width, $height, $top = 'center', $left = 'center')
    {
        if ( ! $this->validSize('width', $width))
            throw new Kohana_Exception('image.invalid_width', $width);

        if ( ! $this->validSize('height', $height))
            throw new Kohana_Exception('image.invalid_height', $height);

        if ( ! $this->validSize('top', $top))
            throw new Kohana_Exception('image.invalid_top', $top);

        if ( ! $this->validSize('left', $left))
            throw new Kohana_Exception('image.invalid_left', $left);

        if (empty($width) AND empty($height))
            throw new Kohana_Exception('image.invalid_dimensions', __FUNCTION__);

        $this->actions['crop'] = array
        (
            'width'  => $width,
            'height' => $height,
            'top'    => $top,
            'left'   => $left,
        );

        return $this;
    }

    /**
     * Allows rotation of an image by 180 degrees clockwise or counter clockwise.
     *
     * @param   integer  $degrees
     * @return  object
     */
    public function rotate($degrees)
    {
        $degrees = (int) $degrees;

        if ($degrees > 180)
        {
            do
            {
                // Keep subtracting full circles until the degrees have normalized
                $degrees -= 360;
            }
            while($degrees > 180);
        }

        if ($degrees < -180)
        {
            do
            {
                // Keep adding full circles until the degrees have normalized
                $degrees += 360;
            }
            while($degrees < -180);
        }

        $this->actions['rotate'] = $degrees;

        return $this;
    }

    /**
     * Flip an image horizontally or vertically.
     *
     * @throws  Kohana_Exception
     * @param   integer $direction direction
     * @return  object
     */
    public function flip($direction)
    {
        if ($direction !== Image::HORIZONTAL AND $direction !== Image::VERTICAL)
            throw new Kohana_Exception('image.invalid_flip');

        $this->actions['flip'] = $direction;

        return $this;
    }

    /**
     * Change the quality of an image.
     *
     * @param   integer $amount quality as a percentage
     * @return  object
     */
    public function quality($amount)
    {
        $this->actions['quality'] = max(1, min($amount, 100));

        return $this;
    }

    /**
     * Sharpen an image.
     *
     * @param   integer $amount amount to sharpen, usually ~20 is ideal
     * @return  object
     */
    public function sharpen($amount)
    {
        $this->actions['sharpen'] = max(1, min($amount, 100));

        return $this;
    }

    /**
     * Save the image to a new image or overwrite this image.
     *
     * @throws  Kohana_Exception
     * @param   string|false $new_image  new image filename
     * @param   integer $chmod  File permissions for new image
     * @param   boolean $keep_actions  keep or discard image process actions
     * @return  bool
     */
    public function save($new_image = FALSE, $chmod = 0644, $keep_actions = FALSE)
    {
        // If no new image is defined, use the current image
        empty($new_image) and $new_image = $this->image['file'];

        // Separate the directory and filename
        $dir  = pathinfo($new_image, PATHINFO_DIRNAME);
        $file = pathinfo($new_image, PATHINFO_BASENAME);

        if (!file_exists($dir)) {
            @mkdir($dir, $chmod | 0111, true);
        }

        // Normalize the path
        $dir = str_replace('\\', '/', realpath($dir)).'/';

        if ( ! is_writable($dir))
            throw new Kohana_Exception('image.directory_unwritable', $dir);

        if ($status = $this->driver->process($this->image, $this->actions, $dir, $file))
        {
            if ($chmod !== FALSE)
            {
                // Set permissions
                @chmod($new_image, $chmod);
            }
        }

        // Reset actions. Subsequent save() or render() will not apply previous actions.
        if ($keep_actions === FALSE)
            $this->actions = array();

        return $status;
    }

    /**
     * Output the image to the browser.
     *
     * @param boolean $keep_actions keep or discard image process actions
     * @return object
     */
    public function render($keep_actions = FALSE)
    {
        $new_image = $this->image['file'];

        // Separate the directory and filename
        $dir  = pathinfo($new_image, PATHINFO_DIRNAME);
        $file = pathinfo($new_image, PATHINFO_BASENAME);

        // Normalize the path
        $dir = str_replace('\\', '/', realpath($dir)).'/';

        // Process the image with the driver
        $status = $this->driver->process($this->image, $this->actions, $dir, $file, $render = TRUE);

        // Reset actions. Subsequent save() or render() will not apply previous actions.
        if ($keep_actions === FALSE)
            $this->actions = array();

        return $status;
    }

    /**
     * Sanitize a given value type.
     *
     * @param   string  $type of property
     * @param   mixed   $value property value
     * @return  boolean
     */
    protected function validSize($type, & $value)
    {
        if (is_null($value))
            return TRUE;

        if ( ! is_scalar($value))
            return FALSE;

        switch ($type)
        {
            case 'width':
            case 'height':
                if (is_string($value) AND ! ctype_digit($value))
                {
                    // Only numbers and percent signs
                    if ( ! preg_match('/^[0-9]++%$/D', $value))
                        return FALSE;
                }
                else
                {
                    $value = (int) $value;
                }
            break;
            case 'top':
                if (is_string($value) AND ! ctype_digit($value))
                {
                    if ( ! in_array($value, array('top', 'bottom', 'center')))
                        return FALSE;
                }
                else
                {
                    $value = (int) $value;
                }
            break;
            case 'left':
                if (is_string($value) AND ! ctype_digit($value))
                {
                    if ( ! in_array($value, array('left', 'right', 'center')))
                        return FALSE;
                }
                else
                {
                    $value = (int) $value;
                }
            break;
            case 'master':
                if ($value !== Image::NONE AND
                    $value !== Image::AUTO AND
                    $value !== Image::WIDTH AND
                    $value !== Image::HEIGHT)
                    return FALSE;
            break;
        }

        return TRUE;
    }


    /**
     * Adds text to the base of an image, e.g. for copyright credit
     * @param string $text The text to add to the image
     * @return Image self, for chaining
     */
    public function addText($text)
    {
        $this->actions['addText'] = (string) $text;
        return $this;
    }


    /**
     * Calculates dimensions to be generated by a resize
     * @param int $width Width in pixels
     * @param int $height Height in pixels
     * @param int $master Master dimension, e.g. Image::HEIGHT for a portrait image,
     *        Image::AUTO for any kind of image
     * @return array [0 => width, 1 => height]
     */
    public function calcResizeDims($width, $height, $master)
    {
        if ($master == Image::NONE) return [$width, $height];

        $img_width = $this->image['width'];
        $img_height = $this->image['height'];

        if ($width == 0) $master = Image::HEIGHT;
        if ($height == 0) $master = Image::WIDTH;

        // Determine automatic master dimension
        if ($master == Image::AUTO) {
            if ($img_width / $width > $img_height / $height) {
                $master = Image::WIDTH;
            } else {
                $master = Image::HEIGHT;
            }
        }

        // Calculate appropriate width or height for resize
        if ($master == Image::WIDTH) {
            $height = round($height * $width / $img_width);
        } elseif ($master == Image::HEIGHT) {
            $width = round($width * $height / $img_height);
        } else {
            $err = 'Invalid master dimension; see Image constants';
            throw new InvalidArgumentException($err);
        }

        return [$width, $height];
    }


    /**
     * Generate a base64-encoded PNG image, e.g. for an <img src="data:image/png;base64,..."> tag
     * @param $file_path Path to the original file
     * @param ImageTransform $transform Optional transform to apply to the image
     * @throws Kohana_Exception
     */
    public static function base64($file_path, ?ImageTransform $transform = null)
    {
        $img = new Image($file_path);
        if ($transform) $transform->transform($img);
        $temp_file = STORAGE_PATH . 'temp/shrunk_' . date('ymdHis') . '_' . Sprout::randStr(8) . '.png';
        $img->save($temp_file);

        $base64_img = base64_encode(file_get_contents($temp_file));
        unlink($temp_file);

        return $base64_img;
    }

} // End Image
