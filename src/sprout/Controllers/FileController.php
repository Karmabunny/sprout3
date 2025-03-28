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
use Sprout\Helpers\FilesBackendS3;
use Sprout\Helpers\FileTransform;
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
     * The file/resize combo is looked up in the transforms table
     * If the transform row or the cached file are missing, it is created
     *
     * For files in nested directories, add a param d="the/nested/dir" to the URL.
     *
     * The size parameter is the new size.
     * The first character is taken to be the resize type, accepts 'r' or 'c' or 'm':
     * Meaning 'r'esize, 'c'rop or 'm'ax resize (do not scale up).
     * The width and height is specified width . 'x' . height (e.g. 200x100)
     *
     * @param string $transform_name Size string as per the File::parseSizeString() method.
     * @param string $filename Full (local) file path to the image to be resized
     *
     * @return void
     *
     */
    public function resize(string $transform_name, string $filename)
    {
        // This will either have the file ID or 0 + dummy data
        $details = File::getDetails($filename);
        $filepath = $filename = str_replace('/', '', $filename);

        $_GET['d'] = rtrim($_GET['d'] ?? '', '/');
        if (!empty($_GET['d'])) {
            $filepath = $_GET['d'] . '/' . $filename;
        }

        // Use direct backend calls here to avoid double-work with transforms
        // and whatever other wrapping magic we have in there.
        $backend = File::backend();

        // Check the db tables for an existing cache
        $transform = FileTransform::findByFilename($filename, $transform_name);

        // Clean out old records
        if (@$_GET['force'] == 1) {
            Security::serverKeyVerify(['filename' => $filename, 'size' => $transform_name], @$_GET['s']);

            // Delete all transform info if found, otherwise just try and clobber the file
            if ($transform) {
                FileTransform::deleteById($transform->id);
                $transform = null;
            } else {
                // This will be expensive for remote files.
                $cache_filename = FileTransform::getTransformFilename($filename, $transform_name);
                $backend->delete($cache_filename);
            }
        }

        $file_modified_time = strtotime($details['date_file_modified'] ?: '');

        if (empty($file_modified_time) and $transform) {
            $file_modified_time = strtotime($transform->date_file_modified);
        }

        if (empty($file_modified_time)) {
            $file_modified_time = File::mtime($filepath);

            // 404 if false (doesn't exist)
            if ((int) $file_modified_time == 0) {
                throw new Kohana_404_Exception($filepath);
            }
        }

        // If-Modified-Since. No need to redirect here if it's not changed
        $expires = 60 * 60 * 48;
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
            $since = strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']);
            if ($file_modified_time <= $since) {
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

        // Nested directory files are currently only used by direct/manual storage, so we omit the DB lookup
        if (empty($_GET['d']) and !empty($row['author']) and $row['embed_author']) {
            $embed_text = $row['author'];
        } else {
            $embed_text = false;
        }

        // Handle file exists, but no record
        // For remote backends this may be slow for the first call to gather data
        if (empty($transform)) {
            $cache_filename = FileTransform::getTransformFilename($filename, $transform_name);

            // Cheap check first.
            $filesize = $backend->size($cache_filename);

            if ($filesize > 0) {
                // This is more expensive than file size.
                $imgsize = $backend->imageSize($cache_filename);

                $transform = FileTransform::addTransformRecord($details['id'], $filename, $transform_name, $cache_filename, $imgsize, $filesize);

                // Back-date the file info as best we can
                if ($file_modified_time > 0 and $file_modified_time < PHP_INT_MAX) {
                    $date = date('Y-m-d H:i:s', $file_modified_time);

                    $transform->date_added = $date;
                    $transform->date_modified = $date;
                    $transform->date_file_modified = $date;
                }

                $transform->save();
            }
        }

        // Check for cached file modification
        $cache_expired = $transform
            ? $transform->date_file_modified < $file_modified_time
            : false;

        // If we have a hit, and a transform record ot older than the file, we're good,
        // otherwise perform the transform
        if ($cache_expired || empty($transform)) {
            Security::serverKeyVerify(['filename' => $filename, 'size' => $transform_name], @$_GET['s']);

            $filename = basename($filepath);

            $temp_filename = File::createLocalCopy($filepath);

            try {
                $parsed_size = File::parseSizeString($transform_name);
                if (count($parsed_size) < 5) {
                    throw new Exception('Invalid image resize parameters');
                }

                list($type, $width, $height, $crop_x, $crop_y, $quality) = $parsed_size;

                $focal_points = [];
                if ($type == 'c') {
                    // Calculate crop position based on focus, if specified
                    $q = "SELECT focal_points
                        FROM ~files
                        WHERE filename = ?
                        LIMIT 1";
                    $res = Pdb::q($q, [$filename], 'arr');
                    $focal_points = @json_decode($res[0]['focal_points'], true);
                }

                // If the resize fails, log a helpful exception then throw 404
                $res = FileTransform::resizeImage($temp_filename, $transform_name, $embed_text, $focal_points);
                if (!$res) {
                    $exception = new Exception("Unable to resize temp file '{$temp_filename}' as '{$transform_name}' from original '{$filepath}'");
                    Kohana::logException($exception);

                    throw new Kohana_404_Exception($filepath);
                }

                // If the copy fails, log a helpful exception then throw 404
                $cache_filename = FileTransform::getTransformFilename($filename, $transform_name);
                $res = File::putExisting($cache_filename, $temp_filename);
                if (!$res) {
                    $exception = new Exception("Unable to copy temp file '{$temp_filename}' to files backend as '{$cache_filename}'");
                    Kohana::logException($exception);

                    throw new Kohana_404_Exception($filepath);
                }

                // Add a transform db record
                // Use temp (local) filename in case file is on an external backend
                $imgsize = getimagesize($temp_filename);
                $filesize = filesize($temp_filename);

                $transform = FileTransform::addTransformRecord($details['id'], $filename, $transform_name, $cache_filename, $imgsize, $filesize);

            } finally {
                File::cleanupLocalCopy($temp_filename);
            }
        }

        // Now we can redirect to the transformed image
        Url::redirect($transform->getUrl());
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
     * Redirect to a file.
     *
     * This uses the backend absUrl() to determine the file location.
     *
     * @param string|int $filename_or_id filename or file ID
     */
    public function resolve($filename_or_id)
    {
        $backend = File::backend();

        // Perform ID lookups.
        if (preg_match('/^[0-9]+$/', (string) $filename_or_id)) {
            $details = File::getDetails($filename_or_id, false);
            $filename = $details ? $details['filename'] : null;
        } else {
            $filename = $filename_or_id;
        }

        if (empty($filename)) {
            throw new Kohana_404_Exception();
        }

        // Special handling for S3.
        if ($backend instanceof FilesBackendS3) {
            $url = $backend->absUrl($filename, false);
        } else {
            $url = $backend->absUrl($filename);
        }

        if (str_contains($url, 'file/resolve')) {
            throw new Exception('Cannot resolve filename, detected infinite loop');
        }

        Url::redirect($url);
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

            $resize_filename = FileTransform::getTransformFilename($filename, $size);

            // Use the resize if we're able.
            if (
                !File::exists($resize_filename)
                and File::createDefaultSize($id, $size)
            ) {
                $filename = $resize_filename;
            }
        }

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
