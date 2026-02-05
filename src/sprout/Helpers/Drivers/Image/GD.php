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
namespace Sprout\Helpers\Drivers\Image;

use Kohana;
use Kohana_Exception;

use Sprout\Helpers\Image;
use Sprout\Helpers\Drivers\ImageDriver;


/**
 * GD Image Driver.
 */
class GD extends ImageDriver
{

    // A transparent PNG as a string
    protected static $blank_png;
    protected static $blank_png_width;
    protected static $blank_png_height;

    public function __construct()
    {
        // Make sure that GD2 is available
        if ( ! function_exists('gd_info'))
            throw new Kohana_Exception('image.gd.requires_v2');

        // Get the GD information
        $info = gd_info();

        // Make sure that the GD2 is installed
        if (strpos($info['GD Version'], '2.') === FALSE)
            throw new Kohana_Exception('image.gd.requires_v2');
    }

    public function process($image, $actions, $dir, $file, $render = FALSE)
    {
        // Set the "create" function
        switch ($image['type'])
        {
            case IMAGETYPE_JPEG:
                $create = 'imagecreatefromjpeg';
            break;
            case IMAGETYPE_GIF:
                $create = 'imagecreatefromgif';
            break;
            case IMAGETYPE_PNG:
                $create = 'imagecreatefrompng';
            break;
            case IMAGETYPE_WEBP:
                $create = 'imagecreatefromwebp';
            break;
        }

        // Set the "save" function
        switch (strtolower(substr(strrchr($file, '.'), 1)))
        {
            case 'jpg':
            case 'jpeg':
                $save = 'imagejpeg';
            break;
            case 'gif':
                $save = 'imagegif';
            break;
            case 'png':
                $save = 'imagepng';
            break;
            case 'webp':
                $save = 'imagewebp';
            break;
        }

        // Make sure the image type is supported for import
        if (empty($create) OR ! function_exists($create))
            throw new Kohana_Exception('image.type_not_allowed', $image['file']);

        // Make sure the image type is supported for saving
        if (empty($save) OR ! function_exists($save))
            throw new Kohana_Exception('image.type_not_allowed', $dir.$file);

        // Load the image
        $this->image = $image;

        // Create the GD image resource
        $this->tmp_image = $create($image['file']);

        // Get the quality setting from the actions
        if (isset($actions['quality'])) {
            $quality = (int) $actions['quality'];
            unset($actions['quality']);
        } else {
            $quality = null;
        }

        if ($status = $this->execute($actions))
        {
            // Prevent the alpha from being lost
            imagealphablending($this->tmp_image, TRUE);
            imagesavealpha($this->tmp_image, TRUE);

            switch ($save)
            {
                case 'imagejpeg':
                    ($quality === NULL) and $quality = (int)Kohana::config('sprout.jpeg_quality');
                    imageinterlace($this->tmp_image, Kohana::config('sprout.jpeg_progressive'));
                break;
                case 'imagegif':
                    // Remove the quality setting, GIF doesn't use it
                    unset($quality);
                break;
                case 'imagepng':
                    // Always use a compression level of 9 for PNGs. This does not
                    // affect quality, it only increases the level of compression!
                    $quality = 9;
                break;
            }

            if ($render === FALSE)
            {
                // Set the status to the save return value, saving with the quality requested
                $status = isset($quality) ? $save($this->tmp_image, $dir.$file, $quality) : $save($this->tmp_image, $dir.$file);
            }
            else
            {
                // Output the image directly to the browser
                switch ($save)
                {
                    case 'imagejpeg':
                        header('Content-Type: image/jpeg');
                    break;
                    case 'imagegif':
                        header('Content-Type: image/gif');
                    break;
                    case 'imagepng':
                        header('Content-Type: image/png');
                    break;
                }

                $status = isset($quality) ? $save($this->tmp_image, NULL, $quality) : $save($this->tmp_image);
            }

            // Destroy the temporary image
            imagedestroy($this->tmp_image);
        }

        return $status;
    }

    public function flip($direction)
    {
        // Get the current width and height
        $width = imagesx($this->tmp_image);
        $height = imagesy($this->tmp_image);

        // Create the flipped image
        $flipped = $this->imagecreatetransparent($width, $height);

        if ($direction === Image::HORIZONTAL)
        {
            for ($x = 0; $x < $width; $x++)
            {
                $status = imagecopy($flipped, $this->tmp_image, $x, 0, $width - $x - 1, 0, 1, $height);
            }
        }
        elseif ($direction === Image::VERTICAL)
        {
            for ($y = 0; $y < $height; $y++)
            {
                $status = imagecopy($flipped, $this->tmp_image, 0, $y, 0, $height - $y - 1, $width, 1);
            }
        }
        else
        {
            // Do nothing
            return TRUE;
        }

        if ($status === TRUE)
        {
            // Swap the new image for the old one
            imagedestroy($this->tmp_image);
            $this->tmp_image = $flipped;
        }

        return $status;
    }

    public function crop($properties)
    {
        // Sanitize the cropping settings
        $this->sanitizeGeometry($properties);

        // Get the current width and height
        $width = imagesx($this->tmp_image);
        $height = imagesy($this->tmp_image);

        // Create the temporary image to copy to
        $img = $this->imagecreatetransparent($properties['width'], $properties['height']);

        // Execute the crop
        if ($status = imagecopyresampled($img, $this->tmp_image, 0, 0, $properties['left'], $properties['top'], $width, $height, $width, $height))
        {
            // Swap the new image for the old one
            imagedestroy($this->tmp_image);
            $this->tmp_image = $img;
        }

        return $status;
    }

    public function resize($properties)
    {
        // Get the current width and height
        $width = imagesx($this->tmp_image);
        $height = imagesy($this->tmp_image);

        if (substr($properties['width'] ?? '', -1) === '%')
        {
            // Recalculate the percentage to a pixel size
            $properties['width'] = round($width * (substr($properties['width'], 0, -1) / 100));
        }

        if (substr($properties['height'] ?? '', -1) === '%')
        {
            // Recalculate the percentage to a pixel size
            $properties['height'] = round($height * (substr($properties['height'], 0, -1) / 100));
        }

        // Recalculate the width and height, if they are missing
        empty($properties['width'])  and $properties['width']  = round($width * $properties['height'] / $height);
        empty($properties['height']) and $properties['height'] = round($height * $properties['width'] / $width);

        if ($properties['master'] === Image::AUTO)
        {
            // Change an automatic master dim to the correct type
            $properties['master'] = (($width / $properties['width']) > ($height / $properties['height'])) ? Image::WIDTH : Image::HEIGHT;
        }

        if (empty($properties['height']) OR $properties['master'] === Image::WIDTH)
        {
            // Recalculate the height based on the width
            $properties['height'] = round($height * $properties['width'] / $width);
        }

        if (empty($properties['width']) OR $properties['master'] === Image::HEIGHT)
        {
            // Recalculate the width based on the height
            $properties['width'] = round($width * $properties['height'] / $height);
        }

        // Test if we can do a resize without resampling to speed up the final resize
        if ($properties['width'] > $width / 2 AND $properties['height'] > $height / 2)
        {
            // Presize width and height
            $pre_width = $width;
            $pre_height = $height;

            // The maximum reduction is 10% greater than the final size
            $max_reduction_width  = round($properties['width']  * 1.1);
            $max_reduction_height = round($properties['height'] * 1.1);

            // Reduce the size using an O(2n) algorithm, until it reaches the maximum reduction
            while ($pre_width / 2 > $max_reduction_width AND $pre_height / 2 > $max_reduction_height)
            {
                $pre_width /= 2;
                $pre_height /= 2;
            }

            // Create the temporary image to copy to
            $img = $this->imagecreatetransparent($pre_width, $pre_height);

            if ($status = imagecopyresized($img, $this->tmp_image, 0, 0, 0, 0, $pre_width, $pre_height, $width, $height))
            {
                // Swap the new image for the old one
                imagedestroy($this->tmp_image);
                $this->tmp_image = $img;
            }

            // Set the width and height to the presize
            $width  = $pre_width;
            $height = $pre_height;
        }

        // Create the temporary image to copy to
        $img = $this->imagecreatetransparent($properties['width'], $properties['height']);

        // Execute the resize
        if ($status = imagecopyresampled($img, $this->tmp_image, 0, 0, 0, 0, $properties['width'], $properties['height'], $width, $height))
        {
            // Swap the new image for the old one
            imagedestroy($this->tmp_image);
            $this->tmp_image = $img;
        }

        return $status;
    }

    public function rotate($amount)
    {
        // Use current image to rotate
        $img = $this->tmp_image;

        // White, with an alpha of 0
        $transparent = imagecolorallocatealpha($img, 255, 255, 255, 127);

        // Rotate, setting the transparent color
        if (PHP_VERSION_ID >= 80300) {
            $img = imagerotate($img, 360 - $amount, $transparent);
        } else {
            $img = imagerotate($img, 360 - $amount, $transparent, -1);
        }

        // Fill the background with the transparent "color"
        imagecolortransparent($img, $transparent);

        // Merge the images
        if ($status = imagecopymerge($this->tmp_image, $img, 0, 0, 0, 0, imagesx($this->tmp_image), imagesy($this->tmp_image), 100))
        {
            // Prevent the alpha from being lost
            imagealphablending($img, TRUE);
            imagesavealpha($img, TRUE);

            // Swap the new image for the old one
            imagedestroy($this->tmp_image);
            $this->tmp_image = $img;
        }

        return $status;
    }

    public function sharpen($amount)
    {
        // Make sure that the sharpening function is available
        if ( ! function_exists('imageconvolution'))
            throw new Kohana_Exception('image.unsupported_method', __FUNCTION__);

        // Amount should be in the range of 18-10
        $amount = round(abs(-18 + ($amount * 0.08)), 2);

        // Gaussian blur matrix
        $matrix = array
        (
            array(-1,   -1,    -1),
            array(-1, $amount, -1),
            array(-1,   -1,    -1),
        );

        // Perform the sharpen
        return imageconvolution($this->tmp_image, $matrix, $amount - 8, 0);
    }

    protected function properties()
    {
        return array(imagesx($this->tmp_image), imagesy($this->tmp_image));
    }

    /**
     * Returns an image with a transparent background. Used for rotating to
     * prevent unfilled backgrounds.
     *
     * @param   integer  image width
     * @param   integer  image height
     * @return  resource
     */
    protected function imagecreatetransparent($width, $height)
    {
        if (self::$blank_png === NULL)
        {
            // Decode the blank PNG if it has not been done already
            self::$blank_png = imagecreatefromstring(base64_decode
            (
                'iVBORw0KGgoAAAANSUhEUgAAACgAAAAoCAYAAACM/rhtAAAABGdBTUEAAK/INwWK6QAAABl0RVh0U29'.
                'mdHdhcmUAQWRvYmUgSW1hZ2VSZWFkeXHJZTwAAADqSURBVHjaYvz//z/DYAYAAcTEMMgBQAANegcCBN'.
                'CgdyBAAA16BwIE0KB3IEAADXoHAgTQoHcgQAANegcCBNCgdyBAAA16BwIE0KB3IEAADXoHAgTQoHcgQ'.
                'AANegcCBNCgdyBAAA16BwIE0KB3IEAADXoHAgTQoHcgQAANegcCBNCgdyBAAA16BwIE0KB3IEAADXoH'.
                'AgTQoHcgQAANegcCBNCgdyBAAA16BwIE0KB3IEAADXoHAgTQoHcgQAANegcCBNCgdyBAAA16BwIE0KB'.
                '3IEAADXoHAgTQoHcgQAANegcCBNCgdyBAgAEAMpcDTTQWJVEAAAAASUVORK5CYII='
            ));

            // Set the blank PNG width and height
            self::$blank_png_width = imagesx(self::$blank_png);
            self::$blank_png_height = imagesy(self::$blank_png);
        }

        $img = imagecreatetruecolor($width, $height);

        // Resize the blank image
        imagecopyresized($img, self::$blank_png, 0, 0, 0, 0, $width, $height, self::$blank_png_width, self::$blank_png_height);

        // Prevent the alpha from being lost
        imagealphablending($img, FALSE);
        imagesavealpha($img, TRUE);

        return $img;
    }


    public function addText($text)
    {
        /** Text colour */
        $colour = imagecolorallocate($this->tmp_image, 0xFF, 0xFF, 0xFF);

        /** Fill colour for the transparent rectangle which will surround the text */
        $fill_colour = imagecolorallocate($this->tmp_image, 0x20, 0x20, 0x20);

        /** Width of image which will have text added */
        $w = imagesx($this->tmp_image);

        /** Height of image which will have text added */
        $h = imagesy($this->tmp_image);

        /** Max font size (N.B. in points, not pixels) */
        $max_font_size = 14;

        /** Min font size (N.B. in points, not pixels) */
        $min_font_size = 8;

        /** Text angle, N.B. 0 represents normal left-right text */
        $angle = 0;

        /** Path to .ttf font */
        $font_file = COREPATH . 'media/fonts/DejaVuSans.ttf';

        /** Num pixels surrounding text in transparent rectangle */
        $border = 5;

        /** Opacity between 0 (retain underlying image) and 100 (completely obscure underlying image) */
        $opacity = 60;

        /**
         * Y-point at which text should be rendered inside image.
         *
         * N.B. One would expect the text to start at (image height - text height - border), but the
         * position for imagettftext is based on the font's baseline, which is font dependent but assumed
         * to be about 2/3 of the overall height of the text
         */
        $y = $h;

        // Calculate the width and height of a box to contain the rendered text
        $text_w = PHP_INT_MAX;
        $font_size = $max_font_size + 1;
        do {
            --$font_size;
            $bounds = imagettfbbox($font_size, $angle, $font_file, $text);
            $rect_w = $bounds[2] - $bounds[6] + (2 * $border);
            $rect_h = $bounds[3] - $bounds[7] + (2 * $border);
        } while ($rect_w > $w and $font_size >= $min_font_size);

        // Don't embed text if it can't fit into the image
        if ($font_size < $min_font_size) return true;

        // Draw a rectangle which will contain the text
        $rect = imagecreatetruecolor($rect_w, $rect_h);
        imagefill($rect, 0, 0, $fill_colour);

        // Merge the rectangle into the original image, with some transparency
        imagealphablending($this->tmp_image, TRUE);
        imagecopymerge($this->tmp_image, $rect, 0, $h - $rect_h, 0, 0, $rect_w, $rect_h, $opacity);

        // Add the text inside the transparent rectangle
        $y = (int) floor($h - ($font_size / 3) - $border);
        imagettftext($this->tmp_image, $font_size, $angle, $border, $y, $colour, $font_file, $text);

        return true;
    }

} // End Image GD Driver
