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

use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Exception;

use karmabunny\pdb\Exceptions\RowMissingException;
use Kohana;
use Sprout\Helpers\Aws\S3;

/**
* Backend for the files module which stores files in a local directory
**/
class FilesBackendS3 extends FilesBackend
{

    /** @inheritdoc */
    public function relUrl($id): string
    {
        return $this->absUrl($id);
    }


    /** @inheritdoc */
    public function absUrl($id): string
    {
        $filename = (string) $id;

        if (preg_match('/^[0-9]+$/', $filename)) {
            return 'file/download/' . $id;
        }

        // If it's not publicly accessible using a quick lookup, try a replacement
        if ($this->existsPublic($filename)) {
            return S3::filesBackendUrl($filename);
        }

        try {
            return File::lookupReplacementUrl($filename);

        } catch (RowMissingException $ex) {

            // If it's not where we expect, this may have exploded, so log it!
            // TODO: Move this into the not controller method?
            $url = S3::filesBackendUrl($filename);
            Kohana::logException(new Exception('S3 file not found at expected location: ' . $url));

            // The actual URL can be found with:
            // $this->_client->getObjectUrl($bucket, $filename);
            return 'file/not-found?url=' . $filename;
        }

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
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        return $s3->doesObjectExist($config['bucket'], $filename);
    }


    /**
     * Determine if a file exists from the public side (quickly)
     *
     * @param string $filename The file to check for
     *
     * @return bool Returns TRUE if the file exists, and FALSE if it does not
     */
    public function existsPublic(string $filename): bool
    {
        $url = S3::filesBackendUrl($filename);

        try {
            $headers = get_headers($url, true);
        } catch (Exception $e) {
            return false;
        }

        $status = substr($headers[0], 9, 3);

        return ($status >= 200 && $status < 300 ) ? true : false;
    }


    /** @inheritdoc */
    public function size(string $filename): int
    {
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        try {
            $result = $s3->headObject([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
            ]);

            return $result['ContentLength'];

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return 0;
    }


    /** @inheritdoc */
    public function mtime(string $filename)
    {
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        try {
            $result = $s3->headObject([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
            ]);

            return strtotime($result['LastModified']);

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return false;
    }


    /**
     * Sets access and modification time of file
     *
     * We achieve this in S3 by copying the file on top of itself
     *
     * @param string $filename
     *
     * @return bool
     */
    public function touch(string $filename): bool
    {
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        try {
            $result = $s3->copyObject([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
                'CopySource' => $config['bucket'] . '/' . $filename,
                'ACL' => $config['acl'],
            ]);

            // TODO: Is this too granular?
            if ($result['@metadata']['statusCode'] == 200) {
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return false;
    }


    /**
     * WARNING: SLOW ON S3 | Returns the size of an image, or false on failure.
     *
     * In S3 we can either store image size info as meta data
     *
     * Output format is the same as getimagesize, but will be at a minimum:
     *   [0] => width, [1] => height, [2] => type
     *
     * @param string $filename
     *
     * @return array|false|null depending if found
     */
    public function imageSize(string $filename)
    {
        try {
            return (@getimagesizefromstring($this->getString($filename)));
        } catch (Exception $e) {
            $this->handleException($e);
        }

        return null;
    }


    /** @inheritdoc */
    public function delete(string $filename): bool
    {
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        try {
            $result = $s3->deleteObject([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
            ]);

            if (@$result['@metadata']['statusCode'] == 204) {
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return false;
    }


    /**
     * Returns all files which match the specified mask.
     *
     * @param string $mask The search mask / string
     *
     * @return array An array of results from the lookup
     */
    public function glob(string $mask): array
    {
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        $items = $s3->listObjects([
            'Bucket' => $config['bucket'],
        ]);

        $results = [];
        foreach ($items['Contents'] as $item) {
            if (fnmatch($mask, $item['Key'])) {
                $results[] = $item['Key'];
            }
        }

        return $results;
    }


    /** @inheritdoc */
    public function readfile(string $filename)
    {
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        try {
            $result = $s3->getObject([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
            ]);

            if (@$result['@metadata']['statusCode'] == 200) {
                echo $result['Body'];
                return strlen($result['Body']);
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return false;
    }


    /** @inheritdoc */
    public function getString(string $filename)
    {
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        try {
            $result = $s3->getObject([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
            ]);

            if (@$result['@metadata']['statusCode'] == 200) {
                return $result['Body'];
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return false;
    }


    /** @inheritdoc */
    public function putString(string $filename, string $content): bool
    {
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        // This may well throw an Aws\S3\Exception\S3Exception, in this scenario we want to be elegant about it
        try {
            $result = $s3->putObject(
                [
                    'Bucket' => $config['bucket'],
                    'Key' => $filename,
                    'Body' => $content,
                    'ContentType' => File::mimetype($filename),
                    'ACL' => $config['acl'],
                ]
            );

            if (@$result['@metadata']['statusCode'] == 200) {
                $res = Replication::postFileUpdate($filename);
                if (! $res) {
                    // TODO: Something with the S3 file?
                    return false;
                }

                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return false;
    }


    /** @inheritdoc */
    public function putStream(string $filename, $stream): bool
    {
        return $this->putString($filename, stream_get_contents($stream));
    }


    /** @inheritdoc */
    public function putExisting(string $filename, string $existing): bool
    {
        $string = file_get_contents($existing);
        return $this->putString($filename, $string);
    }


    /** @inheritdoc */
    public function createLocalCopy(string $filename)
    {
        $temp_filename = STORAGE_PATH . 'temp/' . time() . '_' . str_replace('/', '~', $filename);
        @mkdir(STORAGE_PATH . 'temp/');

        // Get the file from S3 and copy it to the files folder
        $res = @file_put_contents($temp_filename, $this->getString($filename));
        chmod($temp_filename, 0777);

        if (! $res) return '';

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
            // Move file symlink points to, rather than symlink itself
            $src = readlink($src);
        }

        // Upload the src file into S3
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        try {
            $result = $s3->putObject([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
                'SourceFile' => $src,
                'ContentType' => File::mimetype($src),
                'ACL' => $config['acl'],
            ]);

            if (@$result['@metadata']['statusCode'] == 200) {
                $res = Replication::postFileUpdate($filename);
                if (! $res) {
                    // TODO: Something with the S3 file?
                    return false;
                }

                unlink($src);
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return false;
    }


    public function makePublic(string $filename)
    {
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        try {
            $result = $s3->putObjectAcl([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
                'ACL' => 'public-read',
            ]);

            if (@$result['@metadata']['statusCode'] == 200) {
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return false;
    }


    public function makePrivate(string $filename)
    {
        $s3 = S3::getClient('files_backend');
        $config = S3::loadConfig('files_backend');

        try {
            $result = $s3->putObjectAcl([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
                'ACL' => 'private',
            ]);

            if (@$result['@metadata']['statusCode'] == 200) {
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return false;
    }


    private function handleException(Exception $e)
    {
        if ($e instanceof S3Exception) {
            Kohana::logException($e);
            // Fall through to failure

        }  elseif ($e instanceof AwsException) {
            Kohana::logException($e);
            // Fall through to failure

        } else {
            throw $e;
        }

        // If we're testing, blow up in any case
        if (defined('PHPUNIT') and PHPUNIT) {
            throw $e;
        }

        return false;
    }

}
