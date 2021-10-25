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


/**
* Backend for the files module which stores files in a local directory
**/
class FilesBackendDirectory extends FilesBackend
{

    /**
     * Returns the relative URL for a given file.
     *
     * Use for content areas.
     *
     * @param int $id ID of entry in files table, or (deprecated) string: filename
     * @return string e.g. file/download/123
     */
    public function relUrl($id)
    {
        if (preg_match('/^[0-9]+$/', $id)) {
            return 'file/download/' . $id;
        }

        $filename = $id;
        if (!$this->exists($filename)) {
            try {
                return File::lookupReplacementUrl($filename);
            } catch (RowMissingException $ex) {
                // No problem, return original (broken) URL
            }
        }
        return 'files/' . Enc::url($filename);
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
        if (preg_match('/^[0-9]+$/', $id)) {
            try {
                $file_details = File::getDetails($id);
                $signature = Security::serverKeySign(['filename' => $file_details['filename'], 'size' => $size]);
                return sprintf('file/resize/%s/%s?s=%s', Enc::url($size), Enc::url($file_details['filename']), $signature);
            } catch (Exception $ex) {
                // This is doomed to fail
                return sprintf('file/resize/%s/missing.png', Enc::url($size));
            }
        }

        $filename = $id;
        $signature = Security::serverKeySign(['filename' => $filename, 'size' => $size]);

        if ($this->exists($filename)) {
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
        return file_exists(DOCROOT . 'files/' . $filename);
    }


    /**
    * Returns the size, in bytes, of the specified file
    **/
    public function size($filename)
    {
        return @filesize(DOCROOT . 'files/' . $filename);
    }


    /**
    * Returns the modified time, in unix timestamp format, of the specified file
    **/
    public function mtime($filename)
    {
        return @filemtime(DOCROOT . 'files/' . $filename);
    }


    /**
     * Sets access and modification time of file
     * @return bool True if successful
     */
    public function touch($filename)
    {
        return @touch(DOCROOT . 'files/' . $filename);
    }


    /**
    * Returns the size of an image, or false on failure.
    *
    * Output format is the same as getimagesize, but will be at a minimum:
    *   [0] => width, [1] => height, [2] => type
    **/
    public function imageSize($filename)
    {
        return @getimagesize(DOCROOT . 'files/' . $filename);
    }


    /**
    * Delete a file
    **/
    public function delete($filename)
    {
        return @unlink(DOCROOT . 'files/' . $filename);
    }


    /**
    * Returns all files which match the specified mask.
    * I have a feeling this returns other sizes (e.g. .small) as well - which may not be ideal.
    **/
    public function glob($mask)
    {
        $result = glob(DOCROOT . 'files/' . $mask);
        foreach ($result as &$res) {
            $res = basename($res);
        }
        return $result;
    }


    /**
    * This is the equivalent of the php readfile function
    **/
    public function readfile($filename)
    {
        return readfile(DOCROOT . 'files/' . $filename);
    }


    /**
    * Returns file content as a string. Basically the same as file_get_contents
    **/
    public function getString($filename)
    {
        return file_get_contents(DOCROOT . 'files/' . $filename);
    }


    /**
    * Saves file content as a string. Basically the same as file_put_contents
    **/
    public function putString($filename, $content)
    {
        $res = @file_put_contents(DOCROOT . 'files/' . $filename, $content);
        if (! $res) return false;

        $res = @chmod(DOCROOT . 'files/' . $filename, 0666);
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
        $fp = @fopen(DOCROOT . 'files/' . $filename, 'w');
        if (! $fp) return false;

        $res = @stream_copy_to_stream($stream, $fp);
        if (! $res) return false;

        $res = @fclose($fp);
        if (! $res) return false;

        $res = @chmod(DOCROOT . 'files/' . $filename, 0666);
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
        $res = @copy($existing, DOCROOT . 'files/' . $filename);
        if (! $res) return false;

        if ((fileperms(DOCROOT . 'files/' . $filename) & 0666) != 0666) {
            $res = @chmod(DOCROOT . 'files/' . $filename, 0666);
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
        $temp_filename = APPPATH . 'temp/' . time() . '_' . $filename;

        $res = @copy(DOCROOT . 'files/' . $filename, $temp_filename);
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
            if (realpath(readlink($src)) == realpath(DOCROOT . 'files/' . $filename)) {
                @unlink($src);
                return true;
            }

            // Move file symlink points to, rather than symlink itself
            $src = readlink($src);
        }

        $res = @rename($src, DOCROOT . 'files/' . $filename);
        if (! $res) return false;

        $res = @chmod(DOCROOT . 'files/' . $filename, 0666);
        if (! $res) return false;

        $res = Replication::postFileUpdate($filename);
        if (! $res) return false;

        return true;
    }

}
