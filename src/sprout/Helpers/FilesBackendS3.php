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
use Aws\S3\S3Client;
use Exception;

use Kohana;
use Kohana_Exception;
use Psr\Http\Message\StreamInterface;
use Sprout\Helpers\Aws\S3;

/**
* Backend for the files module which stores files in a local directory
**/
class FilesBackendS3 extends FilesBackend
{

    const DEFAULT_CONFIG = [
        // Does the bucket policy allow public access?
        'public_access' => false,

        // Not applicable if public_access is false
        'default_acl' => 'public-read',

        // Public
        'require_url_signing' => true,

        // Human readable time modifier e.g. '+1 hour'
        'signed_url_validity' => '+1 hour',

        // Folder prefix for transformed images
        'transform_folder_prefix' => 'transformed/',

        // Time to cache file helpers responses, such as ::exists()
        'default_cache_ttl' => 86400,

        // Chunk size for processing streams
        'stream_chunk_size' => 1024 * 1024,
    ];


    /**
     * This should match the key in Kohana::config("file.file_backends")
     */
    protected $backend_type = 's3';


    /**
     * @var S3Client
     */
    protected $client = null;


    /**
     * Generate server files base directory path
     *
     * @return string
     */
    public function baseDir()
    {
        return '';
    }


    /** @inheritdoc */
    public function getSettings()
    {
        $config = parent::getSettings();
        return $config + self::DEFAULT_CONFIG;
    }


    /**
     * Config for the AWS client.
     *
     * @return array
     * @throws Kohana_Exception
     */
    public function getS3Config(): array
    {
        $config = $this->getConfig();
        return $config['client'] ?? [];
    }


    /**
     * The S3 client connection for this backend.
     *
     * @return S3Client
     * @throws Kohana_Exception
     */
    public function getS3Client(): S3Client
    {
        $config = $this->getS3Config();
        $this->client ??= S3::getClient($config);
        return $this->client;
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

        $settings = $this->getSettings();
        $s3 = $this->getS3Client();

        $require_url_signing = $settings['require_url_signing'] ?? self::DEFAULT_CONFIG['require_url_signing'];
        if (!$require_url_signing) {
            // Get a URL without presigning
            return (string) $s3->getObjectUrl($settings['bucket'], $filename);
        }

        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $settings['bucket'],
            'Key' => $filename
        ]);

        $validity = $settings['signed_url_validity'] ?? self::DEFAULT_CONFIG['signed_url_validity'];
        $request = $s3->createPresignedRequest($cmd, $validity);
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
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        $cache_response = $this->getCacheResponse(__FUNCTION__, $filename);
        if ($cache_response !== null) {
            return $cache_response;
        }

        $exists = $s3->doesObjectExist($config['bucket'], $filename);

        if ($exists) {
            $this->setCacheResponse(__FUNCTION__, $filename, true);
            return true;

        } else {
            $this->setCacheResponse(__FUNCTION__, $filename, false, 60);
        }

        return false;
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
        $cache_response = $this->getCacheResponse(__FUNCTION__, $filename);
        if ($cache_response !== null) {
            return $cache_response;
        }

        $url = $this->absUrl($filename);

        try {
            $headers = get_headers($url, true);
        } catch (Exception $e) {
            return false;
        }

        $status = substr($headers[0], 9, 3);

        $response = ($status >= 200 && $status < 300 ) ? true : false;
        $this->setCacheResponse(__FUNCTION__, $filename, $response);

        return $response;
    }


    /** @inheritdoc */
    public function size(string $filename): int|false
    {
        $cache_response = $this->getCacheResponse(__FUNCTION__, $filename);
        if ($cache_response !== null) {
            return $cache_response;
        }

        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        try {
            $result = $s3->headObject([
                'Bucket' => $config['bucket'],
                'Key' => $filename,
            ]);

            $this->setCacheResponse(__FUNCTION__, $filename, $result['ContentLength']);

            return $result['ContentLength'];

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return false;
    }


    /** @inheritdoc */
    public function mtime(string $filename)
    {
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

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
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        try {
            $request = [
                'Bucket' => $config['bucket'],
                'Key' => $target_filename,
                'CopySource' => $config['bucket'] . '/' . $src_filename,
                // Overwrite if found
                'MetadataDirective' => 'REPLACE',
            ];

            if ($config['public_access'] and !empty($config['default_acl'])) {
                $request['ACL'] = $config['default_acl'];
            }

            $result = $s3->copyObject($request);

            // TODO: Is this too granular?
            if ($result['@metadata']['statusCode'] == 200) {
                return true;
            }

        } catch (Exception $e) {
            $this->handleException($e);
        }

        $this->clearCaches($target_filename);

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
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

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

        $this->clearCaches($filename);

        return false;
    }


    /**
    * Delete a directory
    **/
    function deleteDir($directory)
    {
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        $items = $s3->listObjects([
            'Bucket' => $config['bucket'],
            'Prefix' => $directory,
        ]);

        foreach ($items['Contents'] ?? [] as $item) {
            $this->delete($item['Key']);
        }

        return true;
    }


    /**
    * Create an empty directory
    **/
    function mkDir($directory)
    {
        // s3 doesn't have directories, but we'll create an empty key
        return $this->putString($directory . '/', '');
    }


    /** @inheritdoc */
    public function glob(string $mask, $depth = 0): array
    {
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        $items = $s3->listObjects([
            'Bucket' => $config['bucket'],
        ]);

        $results = [];

        // Function not available on some systems..
        if (!function_exists('fnmatch')) return $results;

        foreach ($items['Contents'] ?? [] as $item) {
            if (fnmatch($mask, $item['Key'])) {
                $results[] = $item['Key'];
            }
        }

        return $results;
    }


    /** @inheritdoc */
    public function readfile(string $filename)
    {
        $config = $this->getSettings();
        $s3 = $this->getS3Client();
        $s3->registerStreamWrapperV2();

        try {
            $stream = fopen("s3://{$config['bucket']}/{$filename}", 'r');

            if ($stream === false) {
                return false;
            }

            $output = fopen('php://output', 'w');
            $size = 0;

            do {
                $length = stream_copy_to_stream($stream, $output, $config['stream_chunk_size']);

                if ($length === false) {
                    return false;
                }

                $size += $length;

            } while ($length > 0);

            return $size;

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            if (is_resource($stream ?? false)) {
                @fclose($stream);
            }

            stream_wrapper_unregister('s3');
        }

        return false;
    }


    /** @inheritdoc */
    public function getString(string $filename)
    {
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

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
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

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

            if ($config['public_access'] and !empty($config['default_acl'])) {
                $request['ACL'] = $config['default_acl'];
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

        $this->clearCaches($filename);

        return false;
    }


    /** @inheritdoc */
    public function putStream(string $filename, $stream): bool
    {
        if ($stream instanceof StreamInterface) {
            $stream = $stream->detach();
        }

        if ($stream === null) {
            return false;
        }

        $this->clearCaches($filename);

        $config = $this->getSettings();
        $s3 = $this->getS3Client();
        $s3->registerStreamWrapperV2();

        try {
            $file = fopen("s3://{$config['bucket']}/{$filename}", 'w');

            if ($file === false) {
                return false;
            }

            do {
                $length = stream_copy_to_stream($stream, $file, $config['stream_chunk_size']);

                if ($length === false) {
                    return false;
                }
            } while ($length > 0);

            return true;

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            if (is_resource($file ?? false)) {
                @fclose($file);
            }

            stream_wrapper_unregister('s3');
        }

        return false;
    }


    /** @inheritdoc */
    public function getStream(string $filename): ?StreamInterface
    {
        $settings = $this->getSettings();
        $s3 = $this->getS3Client();

        try {
            $command = $s3->getCommand('GetObject', [
                'Bucket' => $settings['bucket'],
                'Key' => $filename,
            ]);

            $command['@http']['stream'] = true;
            $result = $s3->execute($command);

            if ($result['Body'] instanceof StreamInterface) {
                return $result['Body'];
            }

            throw new Exception('Expected StreamInterface, got: ' . get_debug_type($result['Body']));

        } catch (Exception $e) {
            $this->handleException($e);
        }

        return null;
    }


    /** @inheritdoc */
    public function putExisting(string $filename, string $existing): bool
    {
        $stream = fopen($existing, 'r');

        if (!$stream) {
            return false;
        }

        try {
            $ok = $this->putStream($filename, $stream);
            $this->clearCaches($filename);
            return $ok;

        } finally {
            @fclose($stream);
        }
    }


    /** @inheritdoc */
    public function createLocalCopy(string $filename)
    {
        $dir = STORAGE_PATH . 'temp/';
        @mkdir($dir, 0777, true);

        $temp_filename = $dir . time() . '_' . str_replace('/', '~', $filename);

        $stream = $this->getStream($filename);
        $stream = $stream ? $stream->detach() : null;

        if (!$stream) {
            return null;
        }

        $file = fopen($temp_filename, 'w');

        if (!$file) {
            return null;
        }

        $settings = $this->getSettings();

        try {
            do {
                $length = stream_copy_to_stream($stream, $file, $settings['stream_chunk_size']);

                if ($length === false) {
                    return false;
                }
            } while ($length > 0);

            chmod($temp_filename, 0777);
            return $temp_filename;

        } catch (Exception $e) {
            $this->handleException($e);

        } finally {
            @fclose($stream);
            @fclose($file);
        }

        return null;
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
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

        try {
            $request = [
                'Bucket' => $config['bucket'],
                'Key' => $filename,
                'SourceFile' => $src,
                'ContentType' => File::mimetype($filename),
                // Overwrite if found
                'MetadataDirective' => 'REPLACE',
            ];

            if ($config['public_access'] and !empty($config['default_acl'])) {
                $request['ACL'] = $config['default_acl'];
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

        $this->clearCaches($filename);

        return false;
    }


    /**
     * Make a file public using ACL
     *
     * Will not work if 'block all public access' is enabled on the bucket
     *
     * @param string $filename
     * @return bool
     */
    public function makePublic(string $filename)
    {
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

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

        $this->clearCaches($filename);

        return false;
    }


    /**
     * Make a file private using ACL
     *
     * @param string $filename
     * @return bool
     */
    public function makePrivate(string $filename)
    {
        $config = $this->getSettings();
        $s3 = $this->getS3Client();

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

        $this->clearCaches($filename);

        return false;
    }


    /**
     * Simple exception handling with logging
     *
     * @param Exception $e
     * @return bool
     */
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
