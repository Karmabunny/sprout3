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

use Exception;

use karmabunny\pdb\Exceptions\RowMissingException;
use Kohana;

/**
* Backend for the files module which stores files in a local directory
**/
class FilesBackendDirectory extends FilesBackend
{
    /**
     * Generate server files base directory path
     *
     * @return string
     */
    public function baseDir()
    {
        return WEBROOT . 'files/';
    }


    /**
     * Returns the relative URL for a given file.
     *
     * Use for content areas.
     *
     * @param int|string $id ID of entry in files table, or (deprecated) string: filename
     * @return string e.g. file/download/123
     */
    public function relUrl($id)
    {
        if (preg_match('/^[0-9]+$/', $id)) {
            return 'file/download/' . $id;
        }

        /** @var string $filename */
        $filename = $id;

        if (!$this->exists($filename)) {
            try {
                return File::lookupReplacementUrl($filename);
            } catch (RowMissingException $ex) {
                // No problem, return original (broken) URL
            }
        }

        $path_parts = explode('/', $filename);
        $filename = array_pop($path_parts);
        $filename = Enc::url($filename);
        $path = implode('/', $path_parts);

        if ($path) {
            return 'files/' . $path . '/' . $filename;
        }

        return 'files/' . $filename;
    }


    /**
     * Returns the absolute URL for a given file id, including domain.
     *
     * @param int $id ID of entry in files table, or (deprecated) string: filename
     * @return string e.g. http://example.com/file/download/123
     */
    public function absUrl($id)
    {
        return Sprout::absRoot() . $this->relUrl($id);
    }


    /**
     * Returns the relative URL for a dynamically resized image.
     *
     * Size formatting is as per {@see File::parseSizeString}, e.g. c400x300
     *
     * @param int $id ID or filename from record in files table
     * @param string $size A code as per {@see File::parseSizeString}
     * @return string HTML-safe relative URL, e.g. file/resize/c400x300/123_example.jpg
     */
    public function resizeUrl($id, $size)
    {
        if (empty($id)) {
            return sprintf('file/resize/%s/missing.png', Enc::url($size));
        }

        if (preg_match('/^[0-9]+$/', (string) $id)) {
            try {
                $file_details = File::getDetails($id);
                $signature = Security::serverKeySign(['filename' => $file_details['filename'], 'size' => $size]);
                return sprintf('file/resize/%s/%s?s=%s', Enc::url($size), Enc::url($file_details['filename']), $signature);
            } catch (Exception $ex) {
                // This is doomed to fail
                return sprintf('file/resize/%s/missing.png', Enc::url($size));
            }
        }

        /** @var string $filename */
        $filename = $id;
        $signature = Security::serverKeySign(['filename' => $filename, 'size' => $size]);

        if ($this->exists($filename)) {
            $path_parts = explode('/', $filename);
            $filename = array_pop($path_parts);
            $filename = Enc::url($filename);
            $path = implode('/', $path_parts);

            $signature = Security::serverKeySign(['filename' => $filename, 'size' => $size]);

            if (!empty($path)) {
                return sprintf('file/resize/%s/%s?d=%s&s=%s', Enc::url($size), Enc::url($filename), $path, $signature);
            }

            return sprintf('file/resize/%s/%s?s=%s', Enc::url($size), Enc::url($filename), $signature);
        }

        try {
            $replacement = File::lookupReplacementUrl($filename);

            if (preg_match('#^file/download/([0-9]+)$#', $replacement)) {
                $id = substr($replacement, strlen('file/download/'));
                $file_details = File::getDetails($id);
                if ($this->exists($file_details['filename'])) {
                    return sprintf('file/resize/%s/%s?s=%s', Enc::url($size), Enc::url($file_details['filename']), $signature);
                }
            }
        } catch (Exception $ex) {
        }
        return sprintf('file/resize/%s/missing.png', Enc::url($size));
    }


    /**
    * Returns TRUE if the file exists, and FALSE if it does not
    **/
    public function exists($filename)
    {
        return file_exists(self::baseDir() . $filename);
    }


    /**
    * Returns the size, in bytes, of the specified file
    **/
    public function size($filename)
    {
        return @filesize(self::baseDir() . $filename);
    }


    /**
    * Returns the modified time, in unix timestamp format, of the specified file
    **/
    public function mtime($filename)
    {
        return @filemtime(self::baseDir() . $filename);
    }


    /**
     * Sets access and modification time of file
     * @return bool True if successful
     */
    public function touch($filename)
    {
        return @touch(self::baseDir() . $filename);
    }


    /**
    * Returns the size of an image, or false on failure.
    *
    * Output format is the same as getimagesize, but will be at a minimum:
    *   [0] => width, [1] => height, [2] => type
    **/
    public function imageSize($filename)
    {
        return @getimagesize(self::baseDir() . $filename);
    }


    /**
    * Delete a file
    **/
    public function delete($filename)
    {
        try {
            return @unlink(self::baseDir() . $filename);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            return false;
        }
    }


    /**
    * Delete a directory. Must be empty
    **/
    public function deleteDir($directory)
    {
        try {
            return rmdir(self::baseDir() . $directory);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            return false;
        }
    }


    /**
    * Create an empty directory
    **/
    function mkDir($directory)
    {
        try {
            return mkdir(self::baseDir() . $directory, 0755, true);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            return false;
        }
    }


    /** @inheritdoc */
    public function glob(string $mask, $depth = 0): array
    {
        $output = [];

        // A ref for the recursive function.
        $find = null;

        // If there is a path, explode out the path and the main glob
        $mask_parts = explode('/', $mask);
        $mask = array_pop($mask_parts);

        // This will allow us to look for the post-path glob within the path
        $path  = implode('/', $mask_parts);
        $path = $path ? $path . '/' : '';

        $find = function($base, $depth, $path) use (&$find, &$output, $mask) {
            $files = glob(self::baseDir() . $path . $base . $mask);

            foreach ($files as $file) {
                // Found one.
                if (is_file($file)) {
                    $output[] = str_replace(self::baseDir(), '', $file);
                    continue;
                }

                // Dive in.
                if ($depth > 0 and is_dir($file)) {
                    $output[] = rtrim(str_replace(self::baseDir(), '', $file), '/') .'/';
                    $find($base . basename($file) . '/', $depth - 1, $path);
                }
            }
        };

        // Start.
        $find('', $depth, $path);

        return $output;
    }


    /**
    * This is the equivalent of the php readfile function
    **/
    public function readfile($filename)
    {
        return readfile(self::baseDir() . $filename);
    }


    /**
    * Returns file content as a string. Basically the same as file_get_contents
    **/
    public function getString($filename)
    {
        return file_get_contents(self::baseDir() . $filename);
    }


    /**
     * Ensure that a folder path exists so we can save into a new nested directory
     *
     * @param string $filename
     * @return void
     */
    private function createFolderPath(string $filename)
    {
        $path_parts = explode('/', $filename);
        $path_parts = array_slice($path_parts, 0, -1);
        $path = implode('/', $path_parts);

        if (!empty($path) and !is_dir(self::baseDir() . $path)) {
            @mkdir(self::baseDir() . $path, 0755, true);
        }
    }


    /** @inheritdoc */
    public function putString($filename, $content): bool
    {
        $this->createFolderPath($filename);

        try {
            $res = file_put_contents(self::baseDir() . $filename, $content);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $res = false;
        }
        if (! $res) return false;

        $res = @chmod(self::baseDir() . $filename, 0666);
        if (! $res) return false;

        $res = Replication::postFileUpdate($filename);
        if (! $res) return false;

        return true;
    }


    /**
    * Saves file content from a stream. Basically just fopen/stream_copy_to_stream/fclose
    **/
    public function putStream($filename, $stream)
    {
        $fp = @fopen(self::baseDir() . $filename, 'w');
        if (! $fp) return false;

        $res = @stream_copy_to_stream($stream, $fp);
        if (! $res) return false;

        $res = @fclose($fp);
        if (! $res) return false;

        $res = @chmod(self::baseDir() . $filename, 0666);
        if (! $res) return false;

        $res = Replication::postFileUpdate($filename);
        if (! $res) return false;

        return true;
    }


    /**
    * Saves file content from an existing file
    **/
    public function putExisting($filename, $existing)
    {
        $this->createFolderPath($filename);

        try {
            $res = copy($existing, self::baseDir() . $filename);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $res = false;
        }

        if (! $res) return false;

        if ((fileperms(self::baseDir() . $filename) & 0666) != 0666) {
            $res = @chmod(self::baseDir() . $filename, 0666);
            if (!$res) return false;
        }

        $res = Replication::postFileUpdate($filename);
        if (! $res) return false;

        return true;
    }


    /**
    * Create a copy of the file in a temporary directory.
    * Don't forget to File::destroy_local_copy($temp_filename) when you're done!
    *
    * @param string $filename The file to copy into a temporary location
    * @return string Temp filename or NULL on error
    **/
    public function createLocalCopy($filename)
    {
        $temp_filename = STORAGE_PATH . 'temp/' . time() . '_' . str_replace('/', '~', $filename);

        $res = @copy(self::baseDir() . $filename, $temp_filename);
        if (! $res) return null;

        return $temp_filename;
    }


    /**
    * Remove a local copy of a file
    *
    * @param string $temp_filename The filename returned by createLocalCopy
    **/
    public function cleanupLocalCopy($temp_filename)
    {
        @unlink($temp_filename);
    }


    /**
    * Moves an uploaded file into the repository.
    * Returns TRUE on success, FALSE on failure.
    **/
    public function moveUpload($src, $filename)
    {
        if (is_link($src)) {
            // Don't attempt to move symlink onto itself
            if (realpath(readlink($src)) == realpath(self::baseDir() . $filename)) {
                @unlink($src);
                return true;
            }

            // Move file symlink points to, rather than symlink itself
            $src = readlink($src);
        }

        $res = @rename($src, self::baseDir() . $filename);
        if (! $res) return false;

        $res = @chmod(self::baseDir() . $filename, 0666);
        if (! $res) return false;

        $res = Replication::postFileUpdate($filename);
        if (! $res) return false;

        return true;
    }

}
