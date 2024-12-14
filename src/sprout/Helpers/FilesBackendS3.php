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
 *
 * Helpful examples here:
 * https://github.com/awsdocs/aws-doc-sdk-examples/blob/main/php/example_code/s3/
 */

namespace Sprout\Helpers;

use Aws\Exception\AwsException;
use Aws\S3\Exception\S3Exception;
use Exception;

use Kohana;
use Sprout\Helpers\Aws\S3;

/**
* Backend for the files module which stores files in a local directory
**/
class FilesBackendS3 extends FilesBackend
{

    /**
     * This should match the key in Kohana::config("file.file_backends")
     */
    protected $backend_type = 's3';


    /**
     * Generate server files base directory path
     *
     * @return string
     */
    public function baseDir()
    {
        return '';
    }


    /**
     * Get the backend specific config merge settings.
     *
     * @return array
     */
    public function getAwsConfig()
    {
        $details = $this->getConfig();
        return $details['aws_config'] ?? [];
    }


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

        $backend_settings = File::getBackendSettings();

        $config = $this->getAwsConfig();
        $s3 = S3::getClient($config);

        if (!($backend_settings['require_url_signing'] ?? false)) {
            // Get a URL without presigning
            return (string) $s3->getObjectUrl($config['bucket'], $filename);
        }

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $config['bucket'],
            'Key' => $filename
        ]);

        $validity = $backend_settings['signed_url_validity'] ?? '+30 minutes';
        $request = $s3->createPresignedRequest($cmd, $validity);

        // Get the actual presigned-url
        return (string) $request->getUri();

    }


    /** @inheritdoc */
    public function resizeUrl($id, string $size): string
    {
        $filename = (string) $id;

        if (File::filenameIsId($id)) {
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

        if (File::exists($filename)) {
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
        $config = $this->getAwsConfig();
        $s3 = S3::getClient($config);

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
        $url = $this->absUrl($filename);

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
        $config = $this->getAwsConfig();
        $s3 = S3::getClient($config);

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
        $config = $this->getAwsConfig();
        $s3 = S3::getClient($config);

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
        return $this->copyExisting($filename, $filename);
    }


    /**
     * Copy an existing file (within S3) to a new file in S3
     *
     * @param string $src_filename
     * @param string $target_filename
     */
    public function copyExisting(string $src_filename, string $target_filename)
    {
        $aws_config = $this->getAwsConfig();
        $s3_config = $this->getS3Config();
        $s3 = S3::getClient($aws_config);

        try {
            $request = [
                'Bucket' => $aws_config['bucket'],
                'Key' => $target_filename,
                'CopySource' => $aws_config['bucket'] . '/' . $src_filename,
            ];

            if ($s3_config['public_access'] and !empty($s3_config['default_acl'])) {
                $request['ACL'] = $s3_config['default_acl'];
            }

            $result = $s3->copyObject($request);

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
        $config = $this->getAwsConfig();
        $s3 = S3::getClient($config);

        try {
            $result = $s3->deleteObject([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
            ]);

            if (@$result['@metadata']['statusCode'] == 204) {
                return true;
            }

            // No file, so all good
            if (@$result['@metadata']['statusCode'] == 404) {
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return false;
    }


    /** @inheritdoc */
    public function glob(string $mask, $depth = 0): array
    {
        $config = $this->getAwsConfig();
        $s3 = S3::getClient($config);

        $items = $s3->listObjects([
            'Bucket' => $config['bucket'],
        ]);

        $results = [];

        // Function not available on some systems..
        if (!function_exists('fnmatch')) return $results;

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
        $config = $this->getAwsConfig();
        $s3 = S3::getClient($config);

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
        $config = $this->getAwsConfig();
        $s3 = S3::getClient($config);

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
        $config = $this->getAwsConfig();
        $s3_config = $this->getS3Config();
        $s3 = S3::getClient($config);

        // This may well throw an Aws\S3\Exception\S3Exception, in this scenario we want to be elegant about it
        try {
            $request = [
                'Bucket' => $config['bucket'],
                'Key' => $filename,
                'Body' => $content,
                'ContentType' => File::mimetype($filename),
                // Overwrite if found
                'MetadataDirective' => 'REPLACE',
            ];

            if ($s3_config['public_access'] and !empty($s3_config['default_acl'])) {
                $request['ACL'] = $s3_config['default_acl'];
            }

            $result = $s3->putObject($request);

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
        if (empty($string)) return false;

        return $this->putString($filename, $string);
    }


    /** @inheritdoc */
    public function createLocalCopy(string $filename)
    {
        $dir = STORAGE_PATH . 'temp/';
        @mkdir($dir, 0777, true);

        $temp_filename = $dir . time() . '_' . str_replace('/', '~', $filename);

        // Get the file from S3 and copy it to the files folder
        $res = @file_put_contents($temp_filename, $this->getString($filename));
        chmod($temp_filename, 0777);

        if (! $res) return '';

        return $temp_filename;
    }


    /** @inheritdoc */
    public function cleanupLocalCopy(string $temp_filename): bool
    {
        $res = @unlink($temp_filename);
        if ($res) return true;

        return (bool) @unlink(realpath($temp_filename));
    }


    /** @inheritdoc */
    public function moveUpload(string $src, string $filename): bool
    {
        if (is_link($src)) {
            // Move file symlink points to, rather than symlink itself
            $src = readlink($src);
        }

        // Upload the src file into S3
        $config = $this->getAwsConfig();
        $s3 = S3::getClient($config);

        try {
            $request = [
                'Bucket' => $config['bucket'],
                'Key' => $filename,
                'SourceFile' => $src,
                'ContentType' => File::mimetype($filename),
                // Overwrite if found
                'MetadataDirective' => 'REPLACE',
            ];

            if (!empty($config['acl'])) {
                $request['ACL'] = $config['acl'];
            }

            $result = $s3->putObject($request);

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
        $config = $this->getAwsConfig();
        $s3 = S3::getClient($config);

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
        $config = $this->getAwsConfig();
        $s3 = S3::getClient($config);

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
