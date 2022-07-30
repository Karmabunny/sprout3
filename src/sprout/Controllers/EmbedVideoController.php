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

namespace Sprout\Controllers;

use Exception;

use Kohana;

use Sprout\Helpers\EmbedVideo;
use Sprout\Helpers\File;
use Sprout\Helpers\Image;


/**
* Dynamic cropping for video thumbs
**/
class EmbedVideoController extends Controller
{

    /**
    * Downloads a thumbnail from the video provider
    * Trims black bars from the top and the bottom of the image
    * Resizes and crops to the specified size
    **/
    public function thumb($size, $type, $videoid)
    {
        if ($type == EmbedVideo::TYPE_YOUTUBE) {
            $url = 'http://youtu.be/' . $videoid;
        } else if ($type == EmbedVideo::TYPE_VIMEO) {
            $url = 'http://vimeo.com/' . $videoid;
        } else {
            throw new Exception('Unsupported video type');
        }

        $cache_filename = APPPATH . "cache/video-{$size}-{$type}-{$videoid}.jpg";

        // File doesn't exist in cache or render is forced
        if (!file_exists($cache_filename) or @$_GET['force'] == 1) {
            list($type, $width, $height, $cropX, $cropY, $quality) = File::parseSizeString($size);

            // Download thumbnail image file
            $thumb_url = EmbedVideo::getThumbFilename($url, 2);
            $data = @file_get_contents($thumb_url);
            if (! $data) {
                $thumb_url = EmbedVideo::getThumbFilename($url, 1);
                $data = @file_get_contents($thumb_url);
                if (! $data) {
                    throw new Exception('Unable to download thumbnail image');
                }
            }

            $temp_filename = STORAGE_PATH . 'temp/video-' . time() . mt_rand(0, 999) . '.jpg';
            file_put_contents($temp_filename, $data);

            $img = new Image($temp_filename);

            if ($type == 'm') {
                // Max size
                $file_size = getimagesize($temp_filename);

                if ($width == 0) $width = PHP_INT_MAX;
                if ($height == 0) $height = PHP_INT_MAX;

                if ($file_size[0] > $width or $file_size[1] > $height) {
                    $img->resize($width, $height);
                }

            } else if ($type == 'r') {
                // Resize
                $img->resize($width, $height);

            } else if ($type == 'c') {
                // Crop
                if ($width / $img->width > $height / $img->height) {
                    $master = Image::WIDTH;
                } else {
                    $master = Image::HEIGHT;
                }

                $img->resize($width, $height, $master);
                $img->crop($width, $height, $cropY, $cropX);

            } else {
                // What?
                unlink($temp_filename);
                throw new Exception('Incorrect resize type');
            }

            if ($quality) {
                $img->quality($quality);
            }

            $img->save($cache_filename);
            unlink($temp_filename);
        }

        // Serve the cached file
        header('Content-type: image/jpeg');
        header('Content-length: ' . filesize($cache_filename));
        Kohana::closeBuffers();
        readfile($cache_filename);
        exit(0);
    }


    /**
    * Finds the black edge between $y1 and $y2
    **/
    private function findEdgeY($img, $y1, $y2)
    {
        $black = imagecolorat($img, 1, 1);

        for ($y = $y1; $y < $y2; $y++) {
            $col = imagecolorat($img, 1, $y);
            if ($col != $black) return $y;
        }

        return 1;
    }

}
