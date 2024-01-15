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

/**
* Abstract class for a backend storage for the database-managed files
**/
abstract class FilesBackend {

    /**
     * Generate server files base directory path
     *
     * @return string
     */
    abstract function baseDir();

    /**
    * Returns the relative public URL for a given file.
    * Doesn't contain ROOT/ or domain. Use for content areas.
    **/
    abstract function relUrl($filename);


    /**
    * Returns the public URL for a given file, including domain.
    **/
    abstract function absUrl($filename);


    /**
    * Returns the URL for a resized image. Does not include domain.
    * Size should be 'rWIDTHxHEIGHT' or 'cWIDTHxHEIGHT'.
    **/
    abstract function resizeUrl($filename, $size);


    /**
    * Returns TRUE if the file exists, and FALSE if it does not
    **/
    abstract function exists($filename);


    /**
    * Returns the size, in bytes, of the specified file
    **/
    abstract function size($filename);


    /**
    * Returns the modified time, in unix timestamp format, of the specified file
    **/
    abstract function mtime($filename);


    /**
     * Sets access and modification time of file
     */
    abstract function touch($filename);


    /**
    * Returns the size of an image, or false on failure.
    *
    * Output format is the same as getimagesize, but will be at a minimum:
    *   [0] => width, [1] => height, [2] => type
    **/
    abstract function imageSize($filename);


    /**
    * Delete a file
    **/
    abstract function delete($filename);


    /**
    * Returns all files which match the specified mask.
    * I have a feeling this returns other sizes (e.g. .small) as well - which may not be ideal.
    **/
    abstract function glob($mask);


    /**
    * This is the equivalent of the php readfile function
    **/
    abstract function readfile($filename);


    /**
    * Returns file content as a string. Basically the same as file_get_contents
    **/
    abstract function getString($filename);


    /**
    * Saves file content as a string. Basically the same as file_put_contents
    **/
    abstract function putString($filename, $content);


    /**
    * Saves file content from a stream. Basically just fopen/stream_copy_to_stream/fclose
    **/
    abstract function putStream($filename, $stream);


    /**
    * Saves file content from an existing file
    **/
    abstract function putExisting($filename, $existing);


    /**
    * Create a copy of the file in a temporary directory.
    * Don't forget to File::destroy_local_copy($temp_filename) when you're done!
    *
    * @param string $filename The file to copy into a temporary location
    * @return string Temp filename or NULL on error
    **/
    abstract function createLocalCopy($filename);


    /**
    * Remove a local copy of a file
    *
    * @param string $temp_filename The filename returned by createLocalCopy
    **/
    abstract function cleanupLocalCopy($temp_filename);


    /**
    * Moves an uploaded file into the repository.
    * Returns TRUE on success, FALSE on failure.
    **/
    abstract function moveUpload($src, $filename);

}
