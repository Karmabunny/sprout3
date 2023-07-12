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


    /** @inheritdoc */
    public function relUrl($id): string
    {
        $filename = (string) $id;

        if (preg_match('/^[0-9]+$/', $filename)) {
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


    /** @inheritdoc */
    public function absUrl($id): string
    {
        return Sprout::absRoot() . $this->relUrl($id);
    }


    /** @inheritdoc */
    public function resizeUrl($id, string $size): string
    {
        if (empty($id)) {
            return sprintf('file/resize/%s/missing.png', Enc::url($size));
        }

        $filename = (string) $id;

        if (preg_match('/^[0-9]+$/', $filename)) {
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
                $id = (int) substr($replacement, strlen('file/download/'));
                $file_details = File::getDetails($id);
                if ($this->exists($file_details['filename'])) {
                    return sprintf('file/resize/%s/%s?s=%s', Enc::url($size), Enc::url($file_details['filename']), $signature);
                }
            }
        } catch (Exception $ex) {
        }
        return sprintf('file/resize/%s/missing.png', Enc::url($size));
    }


    /** @inheritdoc */
    public function exists(string $filename): bool
    {
        return file_exists(self::baseDir() . $filename);
    }


    /** @inheritdoc */
    public function size(string $filename): int
    {
        return @filesize(self::baseDir() . $filename);
    }


    /** @inheritdoc */
    public function mtime(string $filename)
    {
        return @filemtime(self::baseDir() . $filename);
    }


    /** @inheritdoc */
    public function touch(string $filename): bool
    {
        return @touch(self::baseDir() . $filename);
    }


    /** @inheritdoc */
    public function imageSize(string $filename)
    {
        return @getimagesize(self::baseDir() . $filename);
    }


    /** @inheritdoc */
    public function delete(string $filename): bool
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


    /** @inheritdoc */
    public function readfile(string $filename)
    {
        return readfile(self::baseDir() . $filename);
    }


    /** @inheritdoc */
    public function getString(string $filename)
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


    /** @inheritdoc */
    public function putStream(string $filename, $stream): bool
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


    /** @inheritdoc */
    public function putExisting(string $filename, string $existing): bool
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


    /** @inheritdoc */
    public function createLocalCopy($filename)
    {
        $temp_filename = STORAGE_PATH . 'temp/' . time() . '_' . str_replace('/', '~', $filename);

        $res = @copy(self::baseDir() . $filename, $temp_filename);
        if (! $res) return null;

        return $temp_filename;
    }


    /** @inheritdoc */
    public function cleanupLocalCopy(string $temp_filename): bool
    {
        return @unlink($temp_filename);
    }


    /** @inheritdoc */
    public function moveUpload(string $src, string $filename): bool
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
