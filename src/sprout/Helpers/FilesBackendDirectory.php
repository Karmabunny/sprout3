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
     * This should match the key in Kohana::config("file.file_backends")
     */
    protected $backend_type = 'local';


    /** @inheritdoc */
    public function relUrl($id): string
    {
        $filename = (string) $id;

        if (preg_match('/^[0-9]+$/', $filename)) {
            return 'file/download/' . $id;
        }

        if (!$this->exists($filename)) {
            try {
                return File::lookupReplacementUrl($filename);
            } catch (RowMissingException $ex) {
                // No problem, return original (broken) URL
            }
        }
        return 'files/' . Enc::url($filename);
    }


    /** @inheritdoc */
    public function absUrl($id): string
    {
        return Sprout::absRoot() . $this->relUrl($id);
    }


    /** @inheritdoc */
    public function resizeUrl($id, string $size): string
    {
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

        $signature = Security::serverKeySign(['filename' => $filename, 'size' => $size]);

        if ($this->exists($filename)) {
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
        return file_exists(WEBROOT . 'files/' . $filename);
    }


    /** @inheritdoc */
    public function size(string $filename): int
    {
        try {
            return filesize(WEBROOT . 'files/' . $filename);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            return false;
        }
    }


    /** @inheritdoc */
    public function mtime(string $filename)
    {
        try {
            return filemtime(WEBROOT . 'files/' . $filename);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            return false;
        }
    }


    /** @inheritdoc */
    public function touch(string $filename): bool
    {
        try {
            return touch(WEBROOT . 'files/' . $filename);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            return false;
        }
    }


    /** @inheritdoc */
    public function imageSize(string $filename)
    {
        try {
            return getimagesize(WEBROOT . 'files/' . $filename);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            return false;
        }
        }


    /** @inheritdoc */
    public function delete(string $filename): bool
    {
        try {
            return unlink(WEBROOT . 'files/' . $filename);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            return false;
        }
    }


    /** @inheritdoc */
    public function glob(string $mask): array
    {
        $result = glob(WEBROOT . 'files/' . $mask);
        foreach ($result as &$res) {
            $res = basename($res);
        }
        return $result;
    }


    /** @inheritdoc */
    public function readfile(string $filename)
    {
        return readfile(WEBROOT . 'files/' . $filename);
    }


    /** @inheritdoc */
    public function getString(string $filename)
    {
        return file_get_contents(WEBROOT . 'files/' . $filename);
    }


    /** @inheritdoc */
    public function putString(string $filename, string $content): bool
    {
        try {
            $res = file_put_contents(WEBROOT . 'files/' . $filename, $content);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $res = false;
        }
        if (! $res) return false;

        try {
            $res = chmod(WEBROOT . 'files/' . $filename, 0666);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $res = false;
        }
        if (! $res) return false;

        $res = Replication::postFileUpdate($filename);
        if (! $res) return false;

        return true;
    }


    /** @inheritdoc */
    public function putStream(string $filename, $stream): bool
    {
        try {
            $fp = fopen(WEBROOT . 'files/' . $filename, 'w');
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $fp = false;
        }
        if (! $fp) return false;

        try {
            $res = stream_copy_to_stream($stream, $fp);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $res = false;
        }
        if (! $res) return false;

        try {
            $res = fclose($fp);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $res = false;
        }
        if (! $res) return false;

        try {
            $res = chmod(WEBROOT . 'files/' . $filename, 0666);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $res = false;
        }
        if (! $res) return false;

        $res = Replication::postFileUpdate($filename);
        if (! $res) return false;

        return true;
    }


    /** @inheritdoc */
    public function putExisting(string $filename, string $existing): bool
    {
        try {
            $res = copy($existing, WEBROOT . 'files/' . $filename);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $res = false;
        }
        if (! $res) return false;

        if ((fileperms(WEBROOT . 'files/' . $filename) & 0666) != 0666) {
            try{
                $res = chmod(WEBROOT . 'files/' . $filename, 0666);
            } catch (Exception $ex) {
                Kohana::logException($ex);
                $res = false;
            }
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

        try {
            $res = copy(WEBROOT . 'files/' . $filename, $temp_filename);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $res = false;
        }

        if (! $res) return null;

        return $temp_filename;
    }


    /** @inheritdoc */
    public function cleanupLocalCopy(string $temp_filename): bool
    {
        try {
            return unlink($temp_filename);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            return false;
        }
    }


    /** @inheritdoc */
    public function moveUpload(string $src, string $filename): bool
    {
        if (is_link($src)) {
            // Don't attempt to move symlink onto itself
            if (realpath(readlink($src)) == realpath(WEBROOT . 'files/' . $filename)) {
                return $this->cleanupLocalCopy($src);
            }

            // Move file symlink points to, rather than symlink itself
            $src = readlink($src);
        }

        try {
            $res = rename($src, WEBROOT . 'files/' . $filename);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $res = false;
        }
        if (! $res) return false;

        try {
            $res = chmod(WEBROOT . 'files/' . $filename, 0666);
        } catch (Exception $ex) {
            Kohana::logException($ex);
            $res = false;
        }
        if (! $res) return false;

        $res = Replication::postFileUpdate($filename);
        if (! $res) return false;

        return true;
    }

}
