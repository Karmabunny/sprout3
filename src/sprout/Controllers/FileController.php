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
use Kohana_404_Exception;

use Sprout\Helpers\AdminAuth;
use Sprout\Helpers\BaseView;
use Sprout\Helpers\File;
use Sprout\Helpers\FileConstants;
use Sprout\Helpers\Image;
use Sprout\Helpers\Json;
use Sprout\Helpers\Pdb;
use Sprout\Helpers\Request;
use Sprout\Helpers\Security;
use Sprout\Helpers\Url;
use Sprout\Helpers\PhpView;
use Sprout\Helpers\Sprout;


/**
 * Provides access to file and image data
 */
class FileController extends Controller
{

    /**
    * On the fly image resizing
    *
    * The size parameter is the new size.
    * The first character is taken to be the resize type, accepts 'r' or 'c' or 'm':
    * Meaning 'r'esize, 'c'rop or 'm'ax resize (do not scale up).
    * The width and height is specified width . 'x' . height (e.g. 200x100)
    **/
    public function resize($size, $filename)
    {
        $filename = str_replace('/', '', $filename);

        $cache_hit = $cache_filename = false;
        if (is_writable(WEBROOT . 'files/resize/') and @$_GET['force'] != 1) {
            $cache_filename = WEBROOT . "files/resize/{$size}/{$filename}";
        }

        // 404
        $modified = File::mtime($filename);
        if ($modified === false) {
            throw new Kohana_404_Exception($filename);
        }

        // Prevent browser using cached image if it has been deleted and needs re-creation
        if (!file_exists($cache_filename)) $modified = PHP_INT_MAX;

        // If-Modified-Since
        $expires = 60 * 60 * 48;
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($modified <= $since) {
                header('HTTP/1.0 304 Not Modified');
                header('Pragma: public');
                header("Cache-Control: store, cache, maxage={$expires}");
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
                return;
            }
        }

        // Look up image in DB and see if it needs author attribution
        $q = "SELECT author, embed_author
            FROM ~files
            WHERE filename = ?
            LIMIT 1";
        $rows = Pdb::q($q, [$filename], 'arr');
        $row = Sprout::iterableFirstValue($rows);
        if (!empty($row['author']) and $row['embed_author']) {
            $embed_text = $row['author'];
        } else {
            $embed_text = false;
        }

        $original = false;
        $temp_filename = false;
        if ($cache_filename and @filemtime($cache_filename) >= $modified) {
            $cache_hit = true;

        } else {
            Security::serverKeyVerify(['filename' => $filename, 'size' => $size], @$_GET['s']);

            $temp_filename = File::createLocalCopy($filename);
            if (! $temp_filename) throw new Exception('Unable to create temporary file');

            // Resizing, etc
            $img = new Image($temp_filename);

            $parsed_size = File::parseSizeString($size);
            if (count($parsed_size) < 5) {
                File::cleanupLocalCopy($temp_filename);
                throw new Exception('Invalid image resize parameters');
            }

            list($type, $width, $height, $crop_x, $crop_y, $quality) = $parsed_size;

            $size_limits = Kohana::config('image.max_size');

            if ($width > $size_limits['width'] or $height > $size_limits['height']) {
                File::cleanupLocalCopy($temp_filename);
                throw new Exception('Image dimensions exceed the maximum limit.');
            }

            if ($type == 'm') {
                // Max size
                $file_size = File::imageSize($filename);

                if ($width == 0) $width = PHP_INT_MAX;
                if ($height == 0) $height = PHP_INT_MAX;

                if ($file_size[0] > $width or $file_size[1] > $height) {
                    $img->resize($width, $height);
                    if ($embed_text) $img->addText($embed_text);
                } else {
                    $original = true;
                }

            } else if ($type == 'r') {
                // Resize
                $img->resize($width, $height);
                $resize_dims = $img->calcResizeDims($width, $height, Image::AUTO);
                if ($embed_text) $img->addText($embed_text);

            } else if ($type == 'c') {
                // Crop
                if ($width / $img->width > $height / $img->height) {
                    $master = Image::WIDTH;
                } else {
                    $master = Image::HEIGHT;
                }

                // Determine orientation (portrait/square/landscape/panorama)
                $ratio = $width / $height;
                $orientation = 'panorama';
                foreach (FileConstants::$image_ratios as $orient_name => $orient_ratio) {
                    if ($ratio <= $orient_ratio) {
                        $orientation = $orient_name;
                        break;
                    }
                }

                // Calculate crop position based on focus, if specified
                $q = "SELECT focal_points
                    FROM ~files
                    WHERE filename = ?
                    LIMIT 1";
                $res = Pdb::q($q, [$filename], 'arr');
                $focal_points = @json_decode($res[0]['focal_points'], true);

                if (isset($focal_points[$orientation])) {
                    $point = $focal_points[$orientation];
                } else {
                    $point = @$focal_points['default'];
                }

                @list($x, $y) = $point;
                if ($x > 0 and $y > 0) {
                    $full_dims = File::imageSize($filename);

                    if ($master == Image::WIDTH) {
                        $scale = $width / $img->width;
                    } else {
                        $scale = $height / $img->height;
                    }

                    $scaled_x = round($x * $scale);
                    $scaled_y = round($y * $scale);

                    // Put focal point as close to center of crop position as possible
                    if ($master == Image::WIDTH) {
                        $crop_y = $scaled_y - round($height / 2);
                        if ($crop_y < 0) $crop_y = 0;

                        if ($crop_y + $height > $img->height * $scale) {
                            $crop_y = floor($img->height * $scale) - $height;
                        }
                    } else {
                        $crop_x = $scaled_x - round($width / 2);
                        if ($crop_x < 0) $crop_x = 0;

                        if ($crop_x + $width > $img->width * $scale) {
                            $crop_x = floor($img->width * $scale) - $width;
                        }
                    }
                }

                $img->resize($width, $height, $master);
                $img->crop($width, $height, $crop_y, $crop_x);
                if ($embed_text) $img->addText($embed_text);

            } else {
                // What?
                File::cleanupLocalCopy($temp_filename);
                throw new Exception('Incorrect resize type');
            }

            if ($quality) {
                $img->quality($quality);
            }

            if ($cache_filename) {
                if ($img->save($cache_filename, 0644, true)) $cache_hit = true;
            }
        }

        // Content-type
        $parts = explode('.', $filename);
        $ext = array_pop($parts);
        $mime = array(
            'gif' => 'image/gif',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        );
        $mime = $mime[$ext];
        if (! $mime) $mime = 'application/octet-stream';

        // Headers
        header('Pragma: public');
        header('Content-type: ' . $mime);
        header("Cache-Control: store, cache, maxage={$expires}");
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
        header('Last-modified: ' . gmdate('D, d M Y H:i:s', $modified) . ' GMT');

        // Image
        if ($original) {
            header('Content-length: ' . File::size($filename));
            File::readfile($filename);

        } else if ($cache_hit) {
            header('Content-length: ' . ((int)@filesize($cache_filename)));
            readfile($cache_filename);

        } else {
            $img->render();
        }

        if ($temp_filename) File::cleanupLocalCopy($temp_filename);
    }


    /**
    * Redirect to the resize url
    * This allows JS code to use a common URL without needing to be aware of which FilesBackend is in use
    *
    * @param string $size The size you want, e.g. 'c100x100'
    * @param string $filename Original file
    **/
    public function redirectResize($size, $filename)
    {
        Url::redirect(str_replace('SITE/', '', File::resizeUrl($filename, $size)));
    }


    /**
    * Outputs an audio player.
    **/
    public function playAudio($filename)
    {
        if (Request::isAjax()) {
            $page_view = BaseView::create('skin/popup');
        } else {
            $page_view = BaseView::create('skin/inner');
        }

        $view = new PhpView('sprout/audio_player');
        $view->filename = File::url($filename);

        $page_view->page_title = 'Audio player';
        $page_view->main_content = $view;
        $page_view->controller = 'file';
        $page_view->controller_name = $this->getCssClassName();
        echo $page_view->render();
    }


    /**
     * Renders file contents for viewing or downloading
     *
     * @param int $id ID value from files table
     * @param string $size One of the 'file.image_transformations' config options, e.g. 'small'
     */
    public function download($id, $size = '')
    {
        $id = (int) $id;

        $q = "SELECT filename FROM ~files WHERE id = ?";
        $filename = Pdb::q($q, [$id], 'val');

        // Incorporate size name if specified, e.g. 'example.jpg' => 'example.small.jpg'
        if ($size != '') {
            if (!preg_match('/^[a-z_]+$/', $size)) {
                throw new Kohana_404_Exception($filename);
            }
            $filename = File::getResizeFilename($filename, $size);
        }

        // TODO not used..?
        $path = WEBROOT . 'files/' . $filename;

        $modified = File::mtime($filename);
        if ($modified === false) {
            throw new Kohana_404_Exception($filename);
        }

        // If-Modified-Since
        $expires = 60 * 60 * 48;
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($modified <= $since) {
                header('HTTP/1.0 304 Not Modified');
                header('Pragma: public');
                header("Cache-Control: store, cache, maxage={$expires}");
                header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
                return;
            }
        }

        $mime_type = File::mimetype($filename);
        header('Pragma: public');
        header('Content-type: ' . $mime_type);
        header("Cache-Control: store, cache, maxage={$expires}");
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $expires) . ' GMT');
        header('Last-modified: ' . gmdate('D, d M Y H:i:s', $modified) . ' GMT');
        header('Content-length: ' . File::size($filename));
        Kohana::closeBuffers();
        File::readfile($filename);
    }


    /**
     * Looks up filenames for a list of file IDs
     *
     * @post string ids Comma-separated list of IDs
     * @return void Outputs JSON array [file id => file name]
     */
    public function nameLookup()
    {
        AdminAuth::checkLogin();

        $ids = preg_split('/, */', $_POST['ids'] ?? '');
        foreach ($ids as $key => &$id) {
            $id = (int) $id;
            if ($id <= 0) unset($ids[$key]);
        }

        if (count($ids) == 0) Json::out([]);

        $params = [];
        $where = Pdb::buildClause([['id', 'IN', $ids]], $params);
        $q = "SELECT id, filename
            FROM ~files
            WHERE {$where}";
        Json::out(Pdb::q($q, $params, 'map'));
    }
}
