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

use karmabunny\kb\Shell;
use Kohana_Exception;

use Sprout\Helpers\Image;
use Sprout\Helpers\Drivers\ImageDriver;


/**
 * GraphicsMagick Image Driver.
 */
class GraphicsMagick extends ImageDriver
{

    /** @var string Directory that GM is installed in */
    protected $dir = '';

    /** @var string Command extension (exe for windows) */
    protected $ext = '';

    /** @var string Temporary image filename */
    protected $tmp_image;


    /** @var string[] Processing errors */
    protected $errors = array();

    /**
     * Attempts to detect the GraphicsMagick installation directory.
     *
     * @throws  Kohana_Exception
     * @param   array $config Configuration
     * @return  void
     */
    public function __construct($config)
    {
        if (empty($config['directory']))
        {
            // Attempt to locate GM by using "which" (only works for *nix!)
            if ( ! is_file($path = exec('which gm')))
                throw new Kohana_Exception('image.graphicsmagick.not_found');

            $config['directory'] = dirname($path);
        }

        // Set the command extension
        $this->ext = (PHP_SHLIB_SUFFIX === 'dll') ? '.exe' : '';

        // Check to make sure the provided path is correct
        if ( ! is_file(realpath($config['directory']).'/gm'.$this->ext))
            throw new Kohana_Exception('image.graphicsmagick.not_found', 'gm'.$this->ext);


        // Set the installation directory
        $this->dir = str_replace('\\', '/', realpath($config['directory'])).'/';
    }

    /**
     * Creates a temporary image and executes the given actions. By creating a
     * temporary copy of the image before manipulating it, this process is atomic.
     *
     * @param array $image
     * @param array $actions
     * @param string $dir
     * @param string $file
     * @param bool $render
     * @return bool
     */
    public function process($image, $actions, $dir, $file, $render = FALSE)
    {
        if (empty($image['file']) or !is_string($image['file'])) {
            throw new Kohana_Exception('image.file_not_found', '<missing>');
        }

        // We only need the filename
        $image_file = $image['file'];

        // Unique temporary filename
        $this->tmp_image = $dir.'k2img--'.sha1(time().$dir.$file).substr($file, strrpos($file, '.'));

        // Copy the image to the temporary file
        copy($image_file, $this->tmp_image);

        // Quality change is done last
        $quality = (int) ($actions['quality'] ?? 0);
        unset($actions['quality']);

        // Use 95 for the default quality
        empty($quality) and $quality = 95;

        $new_image = $render ? $this->tmp_image : ($dir . $file);

        if ($status = $this->execute($actions))
        {
            // Use convert to change the image into its final version. This is
            // done to allow the file type to change correctly, and to handle
            // the quality conversion in the most effective way possible.
            if ($error = $this->convert("-quality {quality} {tmp_image} {new_image}", [
                'quality' => $quality,
                'tmp_image' => $this->tmp_image,
                'new_image' => $new_image,
            ])) {
                $this->errors[] = $error;
            }
            else
            {
                // Output the image directly to the browser
                if ($render !== FALSE)
                {
                    $contents = file_get_contents($this->tmp_image);
                    switch (substr($file, strrpos($file, '.') + 1))
                    {
                        case 'jpg':
                        case 'jpeg':
                            header('Content-Type: image/jpeg');
                        break;
                        case 'gif':
                            header('Content-Type: image/gif');
                        break;
                        case 'png':
                            header('Content-Type: image/png');
                        break;
                        case 'webp':
                            header('Content-Type: image/webp');
                        break;
                     }
                    echo $contents;
                }
            }
        }

        // Remove the temporary image
        unlink($this->tmp_image);
        $this->tmp_image = '';

        return $status;
    }

    public function crop($prop)
    {
        // Sanitize and normalize the properties into geometry
        $this->sanitizeGeometry($prop);

        // Set the IM geometry based on the properties
        $geometry = "{$prop['width']}x{$prop['height']}+{$prop['left']}+{$prop['top']}";

        if ($error = $this->convert("-crop {geometry} {image} {image}", [
            'geometry' => $geometry,
            'image' => $this->tmp_image,
        ])) {
            $this->errors[] = $error;
            return FALSE;
        }

        return TRUE;
    }

    public function flip($dir)
    {
        // Convert the direction into a GM command
        $dir = ($dir === Image::HORIZONTAL) ? '-flop' : '-flip';

        if ($error = $this->convert("{dir} {image} {image}", [
            'dir' => $dir,
            'image' => $this->tmp_image,
        ])) {
            $this->errors[] = $error;
            return FALSE;
        }

        return TRUE;
    }

    public function resize($prop)
    {
        $dim = '';
        switch ($prop['master'])
        {
            case Image::WIDTH:  // Wx
                $dim = "{$prop['width']}x";
            break;
            case Image::HEIGHT: // xH
                $dim = "x{$prop['height']}";
            break;
            case Image::AUTO:   // WxH
                $dim = "{$prop['width']}x{$prop['height']}";
            break;
            case Image::NONE:   // WxH!
                $dim = "{$prop['width']}x{$prop['height']}!";
            break;
            default:
                return FALSE;
        }

        // Use "convert" to change the width and height
        if ($error = $this->convert("-resize {dim} {image} {image}", [
            'dim' => $dim,
            'image' => $this->tmp_image,
        ])) {
            $this->errors[] = $error;
            return FALSE;
        }

        return TRUE;
    }

    public function rotate($amt)
    {
        if ($error = $this->convert("-rotate {amt} -background transparent {image} {image}", [
            'amt' => $amt,
            'image' => $this->tmp_image,
        ])) {
            $this->errors[] = $error;
            return FALSE;
        }

        return TRUE;
    }

    public function sharpen($amount)
    {
        // Set the sigma, radius, and amount. The amount formula allows a nice
        // spread between 1 and 100 without pixelizing the image badly.
        $sigma  = 0.5;
        $radius = $sigma * 2;
        $amount = round(($amount / 80) * 3.14, 2);

        if ($error = $this->convert("-unsharp {sharpen} {image} {image}", [
            'sharpen' => "{$radius}x{$sigma}+{$amount}+0",
            'image' => $this->tmp_image,
        ])) {
            $this->errors[] = $error;
            return FALSE;
        }

        return TRUE;
    }


    /**
     * Execute gm convert.
     *
     * @param string $cmd convert command args
     * @param array $args args to interpolate into the command
     * @return string|false
     */
    protected function convert(string $cmd, array $args = [])
    {
        $convert = escapeshellcmd("{$this->dir}gm{$this->ext} convert");
        $cmd = Shell::escape("{$convert} {$cmd}", $args);
        return exec($cmd);
    }


    protected function properties()
    {
        return array_slice(getimagesize($this->tmp_image), 0, 2, FALSE);
    }

} // End Image GraphicsMagick Driver
